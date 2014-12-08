<?php

  $dbaPassword = $argv[1];

  $db_link = odbc_connect("osf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $changeDavPassword = "user_change_password('dav', 'dav', '$dbaPassword')";
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
