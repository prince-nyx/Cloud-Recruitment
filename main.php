<?php

declare(strict_types = 1);
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

    echo '<div><h1>Connections</h1>';

    foreach ($array_of_sliced_serials as $serial => $value)
    {
        echo "<pre>Serial no.:&#09;$serial<br>Connections:&#09;$value</pre>";
    }
    echo '</div>';
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

function get_spec_related_fields(array $array_of_log_entries_adjusted, array $array_of_serials, int $start, int $end, bool $devices = true, string $type = 'mac'): void
{
    # Produce an array of all serials and the mac's they are used by.

    $array_of_devices_by_serial = (array) [];

    foreach ($array_of_log_entries_adjusted as $entry)
    {
        if (!$devices)
        {
            $first_val = (string) 'specs';
            $second_val = (string) 'serial';

            if (!isset($entry[$first_val], $entry[$second_val])) { continue; }
        }
        else
        {
            $first_val = (string) 'serial';
            $second_val = (string) 'specs';
            
            if (!isset($entry[$second_val], $entry[$first_val])) { continue; }
        }

        if (!$devices)
        {
            $spec = (string) $entry[$first_val]['mac'];
            $serial = (string) $entry[$first_val][$type];
        }
        else
        {
            $serial = (string) $entry[$first_val];
            $spec = (string) $entry[$second_val][$type];
        }

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
    
    $title = (string) 'Devices';
    $description = (string) 'Serial no.';
    if (!$devices)
    {
        $title = $type;
        $description = $type;
        if ($type === 'cpu') { $description = "Processor"; }
    }
    
    echo '<div><h1>' . $title . '</h1>';
    foreach (array_slice($array_of_devices_by_serial, $start, $end) as $serial)
    {
        echo '<pre>' . $description . ': &#09;' . $serial[2] . '<br>Devices:&#09;' . $serial[0] . '</pre><nav><ul><li>' . implode('</li><li>', array_keys($serial[1])) . '</li></ul></nav>';
    }
    echo '</div>';
}


echo '<style>* { font-family: Consolas, monaco, monospace; } nav ul{height:200px; width:80%;} nav ul{overflow:hidden; overflow-y:scroll;} div {display:inline-block; vertical-align:top; padding: 1em; height: 50%; overflow: hidden; overflow-y:scroll;}</style>';

$array_of_log_entries = (array) load_logfile_in_array();
$array_of_log_entries_adjusted = (array) [];

# Merge data of serial and specs into one array.
# Specs will be replaced by the MAC returned by load_specs.
# TODO: adjust if using more fields in future.

for ($i = (int) 0; $i < count($array_of_log_entries) - 1; $i+=2)
{
    $array_of_loaded_specs = (array) load_specs($array_of_log_entries[$i+1][2], $i+1);
    $array_of_specs = (array) [];

    if (count($array_of_loaded_specs) > 1)
    {
        $array_of_specs =
        [
            'mac' => $array_of_loaded_specs['mac'],
            'architecture' => $array_of_loaded_specs['architecture'],
            'machine' => $array_of_loaded_specs['machine'],
            'cpu' => $array_of_loaded_specs['cpu']
        ];
    }
    else
    {
        $array_of_specs =
        [
            'mac' => $array_of_loaded_specs[0],
            'architecture' => $array_of_loaded_specs[0],
            'machine' => $array_of_loaded_specs[0],
            'cpu' => $array_of_loaded_specs[0]
        ];
    }

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

get_spec_related_fields($array_of_log_entries_adjusted, $array_of_specs, 0, 10);
foreach (['cpu', 'machine', 'architecture'] as $type)
{
    get_spec_related_fields($array_of_log_entries_adjusted, $array_of_specs, 0, 10, false, $type);
}

?>