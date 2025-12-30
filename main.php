<?php

declare(strict_types = 1);
const logfile_path = '/var/log/php-error.log';

set_error_handler(function ($severity, $message, $file, $line)
{
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function load_logfile_in_array(): array
{
    # Matches the wanted fields (currently: serial & specs) in the logfile and returns them.
    # Sepcs is used since a few lines have this typo. // most likely a wanted stepping stone :).

    $env_var_logfile = $_GET['path'] ?? null;
    $regex = '/(sepcs|specs|serial)=([^ \t\r\n]+)/';

    try
    {
        $array_of_matches = [];
        if (file_exists($env_var_logfile))
        {
            preg_match_all($regex, file_get_contents($env_var_logfile), $array_of_matches, PREG_SET_ORDER);
            return $array_of_matches;
        }

        return [];
    }
    catch (TypeError $exception)
    {
        error_log($exception->getMessage()."\n", 3, logfile_path);
        return [];
    }
}

function get_top_serial_by_connections(array $array_of_serials, int $start, int $end): void
{
    $array_of_sliced_serials = array_slice($array_of_serials, $start, $end);

    echo '<div><h1>Connections</h1>';

    foreach ($array_of_sliced_serials as $serial => $value)
    {
        echo "<pre>Serial no.:&#09;$serial<br>Connections:&#09;$value</pre>";
    }
    echo '</div>';
}

function load_specs(string $string_to_decrypt, int $index): array
{
    $invalid = ['invalid'];

    $base_decode = base64_decode($string_to_decrypt);
    if ($base_decode === false) { return $invalid; }

    try { $base_extracted = gzdecode($base_decode); }
    catch (ErrorException $exception)
    {
        error_log("$index: ".$exception->getMessage()."\n", 3, logfile_path);
        return $invalid;
    }

    $specs = (array) json_decode($base_extracted, true);
    if (json_last_error() !== JSON_ERROR_NONE) { return $invalid; }

    return $specs;
}

function swap_strings(string &$x, string &$y): void
{
    $temp = $x;
    $x = $y;
    $y = $temp;
}

function get_spec_related_fields(array $array_of_log_entries_adjusted, int $start, int $end, bool $devices = true, string $type = 'mac'): void
{
    # Produce an array of all serials and the type they are used by.

    $array_of_devices_by_serial = [];

    foreach ($array_of_log_entries_adjusted as $entry)
    {
        $first_val = 'serial';
        $second_val = 'specs';

        if (!isset($entry[$first_val], $entry[$second_val])) { continue; }

        $serial = $entry[$first_val];
        $spec = $entry[$second_val][$type];

        if (!$devices) { swap_strings($serial, $spec); }

        # Increment amount of types; avoid null
        $array_of_devices_by_serial[$serial]['vals'][$spec] = ($array_of_devices_by_serial[$serial]['vals'][$spec] ?? 0) + 1;
    }

    # Edit values of $array_of_devices_by_serial by ref.
    foreach ($array_of_devices_by_serial as $serial => &$value)
    {
        $value =
        [
            count($value['vals']),
            $value['vals'],
            $serial
        ];
    }
    # Unset ref.
    unset($value);

    arsort($array_of_devices_by_serial);

    $title = $description_counter = 'Devices';
    $description = 'Serial no.';

    if (!$devices)
    {
        $title = $type;
        $description = $type;
        $description_counter = 'Serials';

        if ($type === 'cpu') { $description = "Processor"; }
    }

    echo '<div><h1>' . $title . '</h1>';
    foreach (array_slice($array_of_devices_by_serial, $start, $end) as $serial)
    {
        echo '<pre>' . $description . ': &#09;' . $serial[2] . '<br>' . $description_counter . ':&#09;' . $serial[0] . '</pre><nav><ul><li>' . implode('</li><li>', array_keys($serial[1])) . '</li></ul></nav>';
    }
    echo '</div>';
}


echo '<style>* { font-family: Consolas, monaco, monospace; } nav ul{height:200px; width:80%;} nav ul{overflow:hidden; overflow-y:scroll;} div {display:inline-block; vertical-align:top; padding: 1em; width: 30%; height: 47%; overflow: hidden; overflow-y:scroll;}</style>';

$array_of_log_entries = load_logfile_in_array();
$array_of_log_entries_adjusted = [];

# Merge data of serial and specs into one array.
# Specs will be replaced by the value for given type returned by get_spec_related_fields.
# TODO: adjust if using more fields in future.

for ($i = 0; $i < count($array_of_log_entries) - 1; $i+=2)
{
    $array_of_loaded_specs = load_specs($array_of_log_entries[$i+1][2], $i+1);
    $array_of_specs = [];

    # Count == 1 = invalid.
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

    $temp_array =
    [
        $array_of_log_entries[$i][1] => $array_of_log_entries[$i][2],
        $array_of_log_entries[$i+1][1] => $array_of_specs
    ];

    $array_of_log_entries_adjusted[] = $temp_array;
}

$array_of_serials = array_count_values(array_column($array_of_log_entries_adjusted, 'serial'));
arsort($array_of_serials);
get_top_serial_by_connections($array_of_serials, 0, 10);

get_spec_related_fields($array_of_log_entries_adjusted, 0, 10);
foreach (['cpu', 'machine', 'architecture'] as $type)
{
    get_spec_related_fields($array_of_log_entries_adjusted, 0, 10, false, $type);
}

?>