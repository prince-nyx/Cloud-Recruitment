<?php

declare(strict_types=0);
const logfile_path = (string) '/var/log/php-error.log';

set_error_handler(function ($severity, $message, $file, $line) 
{
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function load_logfile_in_array(): array | false
{
    # Matches the wanted fields (currently: serial & specs) in the logfile and returns them.

    $env_var_logfile = (string) 'LOGFILE';
    $field_cut_index = (int) 13;
    $regex = (string) '/(sepcs|specs|serial)=([^ \t\r\n]+)/'; 

    try
    {
        $array_of_matches = (array) [];
        preg_match_all($regex, file_get_contents(getenv($env_var_logfile)), $array_of_matches, PREG_SET_ORDER);
        
        return $array_of_matches;
    }
    catch (TypeError $exception)
    { 
        error_log($exception->getMessage()."\n", 3, logfile_path);
        return false; 
    }
}

function get_top_serial_by_connections(array $array_of_serials, int $start, int $end): void
{
    $array_of_sliced_serials = (array) array_slice($array_of_serials, $start, $end);
    
    echo '<h1>Connections</h1>';

    foreach ($array_of_sliced_serials as $serial => $value)
    {
        echo "<pre>Serial no.:&#09;$serial<br>Connections:&#09;$value</pre>";
    }
}

function load_specs(string $string_to_decrypt, int $index): array
{
    $invalid = (array) ['invalid'];

    $base_decode = (string) base64_decode($string_to_decrypt);
    
    try { $base_extracted = (string) gzdecode($base_decode); }
    catch (ErrorException $exception)
    {
        error_log("$index: ".$exception->getMessage()."\n", 3, logfile_path);
        return $invalid;
    }

    $specs = (array) json_decode($base_extracted, true);
    if (json_last_error() !== JSON_ERROR_NONE) { return $invalid; }
    
    return $specs;
}

function get_top_serial_by_devices(array $array_of_log_entries_adjusted, array $array_of_serials, int $start, int $end): void
{
    # Produce an array of all serials and the mac's they are used by.

    $array_of_devices_by_serial = (array) [];

    foreach ($array_of_log_entries_adjusted as $entry)
    {
        if (!isset($entry['serial'], $entry['specs'])) { continue; }

        $serial = (string) $entry['serial'];
        $spec = (string) $entry['specs']['mac'];

        if (!isset($array_of_serials[$serial])) { continue; }

        $array_of_devices_by_serial[$serial]['vals'][$spec] = (int) ($array_of_devices_by_serial[$serial]['vals'][$spec] ?? 0) + 1;
    }

    foreach ($array_of_devices_by_serial as $serial => &$value)
    {
        $value = (array) [
            count($value['vals']),
            $value['vals'],
            $serial
        ];
    }
    unset($value);

    arsort($array_of_devices_by_serial);
    
    echo '<h1>Devices</h1>';

    foreach (array_slice($array_of_devices_by_serial, $start, $end) as $serial)
    {
        echo "<pre>Serial no.:&#09;".$serial[2]."<br>Devices:&#09;".$serial[0]."</pre><pre>&#09;".implode('<br>&#09;', array_keys($serial[1]))."</pre>";
    }
}


echo '<style>* { font-family: Consolas, monaco, monospace; } </style>';

$array_of_log_entries = (array) load_logfile_in_array();
$array_of_log_entries_adjusted = (array) [];

# Merge data of serial and specs into one array.
# Specs will be replaced by the MAC returned by load_specs.
# TODO: adjust if using more fields in future.

for ($i = (int) 0; $i < count($array_of_log_entries) - 1; $i+=2)
{
    $specx = (array) load_specs($array_of_log_entries[$i+1][2], $i+1);

    $array_of_specs = [];

    if (count($specx) > 1)
    { 
        $array_of_specs['mac'] = $specx['mac'];
        $array_of_specs['arch'] = $specx['architecture'];
        $array_of_specs['machine'] = $specx['machine'];
        $array_of_specs['cpu'] = $specx['cpu'];
    }
    else 
    { 
        $array_of_specs['mac'] = $specx[0];
        $array_of_specs['arch'] = $specx[0];
        $array_of_specs['machine'] = $specx[0];
        $array_of_specs['cpu'] = $specx[0]; }
    
    $temp_array = (array)
    [
        $array_of_log_entries[$i][1] => $array_of_log_entries[$i][2],
        $array_of_log_entries[$i+1][1] => $array_of_specs
    ];
    
    $array_of_log_entries_adjusted[] = (array) $temp_array;
}

$array_of_serials = (array) array_count_values(array_column($array_of_log_entries_adjusted, 'serial'));
$array_of_specs = (array) array_column($array_of_log_entries_adjusted, 'specs');
arsort($array_of_serials);

get_top_serial_by_connections($array_of_serials, 0, 10);
get_top_serial_by_devices($array_of_log_entries_adjusted, $array_of_serials, 0, 10);

?>