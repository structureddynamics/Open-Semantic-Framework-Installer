<?php

  $db_link = odbc_connect("structwsf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $exst= 'create procedure exst (in st varchar)
          {
            declare h, dta, mdta any;
            set isolation=\'committed\';

            exec (st, null, null, vector (), 0, mdta, null, h);
            exec_result_names (mdta[0]);

            while (0 = exec_next (h, null, null, dta))
            {
              exec_result (dta);
            }

            exec_close (h);
          }';

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