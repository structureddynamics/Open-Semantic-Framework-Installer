<?php

  $db_link = odbc_connect("structwsf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $log  = 'create table "SD"."WSF"."ws_queries_log"
          (
            "id" INTEGER IDENTITY,
            "requested_Web_service" VARCHAR,
            "requester_ip" VARCHAR,
            "request_parameters" VARCHAR,
            "requested_mime" VARCHAR,
            "request_datetime" DATETIME,
            "request_processing_time" DECIMAL,
            "request_http_response_status" VARCHAR,
            "requester_user_agent" VARCHAR,
            PRIMARY KEY ("id")
          )';
          
  $errors = FALSE;          
          
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_requested_Web_service_index on SD.WSF.ws_queries_log (requested_Web_service)';
  
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_requester_ip_index on SD.WSF.ws_queries_log (requester_ip)';
  
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_requested_mime_index on SD.WSF.ws_queries_log (requested_mime)';
  
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_request_datetime_index on SD.WSF.ws_queries_log (request_datetime)';
  
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_request_http_response_status_index on SD.WSF.ws_queries_log (request_http_response_status)';
  
  if(odbc_exec($db_link, $log) === FALSE)
  {
    $errors = TRUE;
  }
  
  $log  = 'create index sd_wsf_requester_user_agent_index on SD.WSF.ws_queries_log (requester_user_agent)';
  
  if(odbc_exec($db_link, $log) === FALSE)
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