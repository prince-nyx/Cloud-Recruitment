<?php

declare(strict_types=0);
const logfile_path = (string) '/var/log/php-error.log';

function load_logfile(): array | false
{
    # Matches the wanted fields (currently: serial & specs) in the logfile and returns them.

    $env_var_logfile = (string) 'LOGFILE';
    $field_cut_index = (int) 13;
    $regex = (string) '/(specs|serial)=([^ \t\r\n]+)/'; 

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

$array_of_log_entries = (array) load_logfile();
$array_of_log_entries_adjusted = (array) [];

# Merge data of serial and specs into one array.
# TODO: adjust if using more fields in future.
for ($i = 0; $i < count($array_of_log_entries) - 1; $i+=2)
{
    $temp_array = (array) [$array_of_log_entries[$i][1] => $array_of_log_entries[$i][2], $array_of_log_entries[$i+1][1] => $array_of_log_entries[$i+1][2]];
    $array_of_log_entries_adjusted[] = $temp_array;
} 

$array_of_serials = array_column($array_of_log_entries_adjusted, 'serial');

$array_of_serials = (array) array_count_values($array_of_serials);
arsort($array_of_serials);
$array_of_10_serials = (array) array_slice($array_of_serials, 0, 10);

print_r($array_of_10_serials);

?>