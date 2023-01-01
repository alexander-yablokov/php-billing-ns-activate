<?php

function open_log($filename) {
    return fopen('/netup/utm5/scripts/ns_tariff_change/'.$filename, 'a+');
}

function write_log($flog, $message = '')
{
  fwrite($flog, "\n" . date("d-m-Y H:i:s") . " " . $message);
}

function prevent_second_run($flog) {
    if (!flock($flog, LOCK_EX | LOCK_NB)) {
        write_log($flog, 'Процесс уже запущен');
        fclose($flog);
        exit(-1);
    }
}

