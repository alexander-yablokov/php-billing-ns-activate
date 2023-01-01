<?php

  define("SUCCESS", "success");
  define("FAILURE", "failure");

function execute($cmd, $stdin = null, &$stdout, &$stderr, $timeout = false)
{
  global $flog;

  $pipes = array();
  $process = proc_open(
    $cmd,
    array(
      array('pipe', 'r'),
      array('pipe', 'w'),
      array('pipe', 'w')
    ),
    $pipes
  );
  $start = time();
  $stdout = '';
  $stderr = '';

  if (is_resource($process)) {
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
  }

  while (is_resource($process)) {
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);

    if ($timeout !== false && time() - $start > $timeout) {
      proc_terminate($process, 9);
      write_log($flog, "ОШИБКА. Таймаут utm5_urfaclient. Зависании более $timeout
секунд");
      return 44; // timeout
    }

    $status = proc_get_status($process);
    if (!$status['running']) {
      fclose($pipes[1]);
      fclose($pipes[2]);
      proc_close($process);
      return $status['exitcode'];
    }

    usleep(100000);
  }

  write_log($flog, "ОШИБКА. Процесс utm5_urfaclient не запустился.");
  return 55; // process didn't start
}

function urfa_change_unflexible_tariff($filename, $account_id_count) 
{
  global $flog;

  if ($account_id_count==0) {
      exit;
  }
  
  define("SUCCESS", "success");
  define("FAILURE", "failure");
  
  $urfa_command = '/netup/utm5/bin/utm5_urfaclient  -x /netup/utm5/scripts/ns_tariff_change  -a activate -account_id_count ' . $account_id_count . ' -datafile /netup/utm5/scripts/ns_tariff_change/'.$filename;
  // write_log($flog, $urfa_command);

  $exitcode = execute($urfa_command, null, $urfa_xml, $urfa_errors, 30);
  $status = $exitcode ? FAILURE : SUCCESS;
  if ($status === FAILURE) {
    write_log($flog, "exitcode = $exitcode, errors = $urfa_errors");
  }
}



function urfa_change_flexible_tariff($filename) 
{
  global $flog;

  $urfa_command = '/netup/utm5/bin/utm5_urfaclient  -x /netup/utm5/scripts/ns_tariff_change  -a activate_flexible_dp  -datafile /netup/utm5/scripts/ns_tariff_change/'.$filename;
  // write_log($flog, $urfa_command);

  $exitcode = execute($urfa_command, null, $urfa_xml, $urfa_errors, 30);
  $status = $exitcode ? FAILURE : SUCCESS;
  if ($status === FAILURE) {
    write_log($flog, "exitcode = $exitcode, errors = $urfa_errors");
  }
}


function urfa_change_tariff($filename)
{
  global $flog;

  $urfa_command = '/netup/utm5/bin/utm5_urfaclient  -x /netup/utm5/scripts/ns_tariff_change  -a activate_new  -datafile /netup/utm5/scripts/ns_tariff_change/'.$filename;
  // write_log($flog, $urfa_command);

  $exitcode = execute($urfa_command, null, $urfa_xml, $urfa_errors, 30);
  $status = $exitcode ? FAILURE : SUCCESS;
  if ($status === FAILURE) {
    write_log($flog, "exitcode = $exitcode, errors = $urfa_errors");
  }
}