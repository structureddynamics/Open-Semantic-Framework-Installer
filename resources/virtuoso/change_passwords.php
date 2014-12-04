#!/usr/bin/php

<?php

  function __getInput($msg)
  {
    fwrite(STDOUT, $msg);
    return trim(fgets(STDIN));
  }

  exec('/etc/init.d/virtuoso stop');
  sleep(10);
  exec('/etc/init.d/virtuoso start');
  sleep(20);
//  $dbaPassword = $argv[1];
  $adminPassword = __getInput("Enter a password to use with the Virtuoso administrator 'dba' user: ");

  $db_link = odbc_connect("osf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $changeDavPassword = "user_change_password('dav', 'dav', '$adminPassword')";
  $changeDbaPassword = "user_change_password('dba', 'dba', '$adminPassword')";
  
  $errors = FALSE;
  
  if(odbc_exec($db_link, $changeDavPassword) === FALSE)
  {
    $errors = TRUE;
  }
  
  if(odbc_exec($db_link, $changeDbaPassword) === FALSE)
  {
    $errors = TRUE;
  }
  
  odbc_close($db_link);
  
  if($errors)
  {
    echo "errors";
  }
  else
  {
    echo "ok";
  }

?>
