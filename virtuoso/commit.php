<?php

  $db_link = odbc_connect("structwsf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $exst= 'exec(\'checkpoint\')';
  
  $errors = FALSE;
          
  if(odbc_exec($db_link, $exst) === FALSE)
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