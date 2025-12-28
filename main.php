<?php

declare(strict_types=1);
const logfile_path = (string) '/var/log/php-error.log';

function load_logfile(): array | false
{
    # Splits each entry of the logfile into an array (each field gets single index)
    # 0 - IP        3 - Protocol    6 - proxy    9 - version    12 - remaining_days
    # 1 - Server    4 - Status      7 - rt      10 - specs
    # 2 - Time      5 - Size        8 - serial  11 - not_after

    $env_var_logfile = (string) 'LOGFIL';
    $field_cut_index = (int) 13;
    $regex_split = (string) '/ ([^ ]*?)=|(?= )((?! \/).)(?!HTTP)/';
    $regex_replace = (string) '/"/';
    
    try
    {
        return $array_of_log_entries = (array) array_chunk(preg_replace($regex_replace, '', preg_split($regex_split, file_get_contents(getenv($env_var_logfile)))), $field_cut_index);
    }
    catch (TypeError $exception)
    { 
        error_log($exception->getMessage()."\n", 3, logfile_path);
        return false; 
    }
}

$array_of_log_entries = (array) load_logfile();

if (gettype($array_of_log_entries) == 'Array') 
{
    foreach ($array_of_log_entries[0] as $val)
    {
        echo $val . "<br>";
    }
}

?>