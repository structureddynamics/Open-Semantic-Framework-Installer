<?php

  $dbaPassword = $argv[1];
  $davPassword = $argv[2];

  $db_link = odbc_connect("structwsf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $changeDavPassword = "user_change_password('dav', 'dav', '$davPassword')";
  $changeDbaPassword = "user_change_password('dba', 'dba', '$dbaPassword')";
  
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