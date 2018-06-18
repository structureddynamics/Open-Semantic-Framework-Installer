<?php

  include_once('inc/CommandlineTool.php');

  class OSFConfigurator extends CommandlineTool
  {
    /* Parsed intaller.ini configuration file */
    protected $config;

    /* OSF Installer Version */
    public $installer_version = "3.4.0";

    /* OSF Installation status */
    public $installer_osf_configured = FALSE;

    /* OSF Drupal Installation status */
    public $installer_osf_drupal_configured = FALSE;

    /* Upgrade the Linux distro with the latest updates */
    protected $upgrade_distro = TRUE;

    /* OSF Common */
    protected $application_id = 'administer';
    protected $api_key = 'some-key';
    protected $data_folder = "/data";
    protected $logging_folder = "/tmp";

    /* Namespace extension of the OSF Web Services folder. This is where the code resides */
    protected $osf_web_services_ns = "StructuredDynamics/osf/ws";

    /* OSF Web Services */
    protected $osf_web_services_version = "3.4.0";
    protected $osf_web_services_folder = "/usr/share/osf";
    protected $osf_web_services_domain = "localhost";

    /* OSF WS-PHP-API */
    protected $osf_ws_php_api_version = "3.1.4";
    protected $osf_ws_php_api_folder = "StructuredDynamics/osf";

    /* OSF Tests Suites */
    protected $osf_tests_suites_version = "3.4.0";
    protected $osf_tests_suites_folder = "StructuredDynamics/osf/tests";

    /* OSF Data Validator Tool */
    protected $data_validator_tool_version = "3.1.0";
    protected $data_validator_tool_folder = "StructuredDynamics/osf/validator";

    /* OSF Permissions Management Tool */
    protected $permissions_management_tool_version = "3.1.0";
    protected $permissions_management_tool_folder = "/usr/share/permissions-management-tool";

    /* OSF Datasets Management Tool */
    protected $datasets_management_tool_version = "3.4.0";
    protected $datasets_management_tool_folder = "/usr/share/datasets-management-tool";

    /* OSF Ontologies Management Tool */
    protected $ontologies_management_tool_version = "3.1.1";
    protected $ontologies_management_tool_folder = "/usr/share/ontologies-management-tool";

    /* Drupal framework */
    protected $drupal_version = "7.41";
    protected $drupal_folder = "/usr/share/drupal";
    protected $drupal_domain = "localhost";
    protected $drupal_admin_username = "admin";
    protected $drupal_admin_password = "admin";

    /* SQL dependency */
    protected $sql_server = "mysql";
    protected $sql_host = "localhost";
    protected $sql_port = "3306";
    protected $sql_root_username = "root";
    protected $sql_root_password = "root";
    protected $sql_app_username = "drupal";
    protected $sql_app_password = "drupal";
    protected $sql_app_database = "drupal";
    protected $sql_app_engine = "innodb";
    protected $sql_app_collation = "utf8_general_ci";

    /* SPARQL dependency */
    protected $sparql_server = "virtuoso";
    protected $sparql_channel = "odbc";
    protected $sparql_dsn = "OSF-triples-store";
    protected $sparql_host = "localhost";
    protected $sparql_port = "8890";
    protected $sparql_url = "sparql";
    protected $sparql_graph_url = "sparql-graph-crud-auth";
    protected $sparql_username = "dba";
    protected $sparql_password = "dba";

    /* Keycache dependency */
    protected $keycache_enabled = "true";
    protected $keycache_server = "memcached";
    protected $keycache_host = "localhost";
    protected $keycache_port = "11211";
    protected $keycache_ui_password = "admin";

    /* Solr dependency */
    protected $solr_host = "localhost";
    protected $solr_port = "8983";
    protected $solr_core = "";

    /* OWL dependency */
    protected $owl_host = "localhost";
    protected $owl_port = "8080";

    /* Scones dependency */
    protected $scones_host = "localhost";
    protected $scones_port = "8080";
    protected $scones_url = "scones";

    /**
     *  Construct class by loading configuration
     */
    function __construct($configFile)
    {
      parent::__construct();

      // Load the installer configuration file
      $this->config = parse_ini_file($configFile, TRUE);

      if (!$this->config) {
        $this->span("An error occured when we tried to parse the {$configFile} file. Make sure it is parseable and try again", 'error');
        exit(1);
      }

      /**
       *  OSF Installation version
       */
      if (isset($this->config['installer']['version'])) {
        $input = $this->config['installer']['version'];
        if (!empty($input)) {
          if ($this->isVersion($input)) {
            $this->installer_version = $input;
          }
        }
      }

      /**
       *  OSF Installation status
       */
      if (isset($this->config['installer']['osf-configured'])) {
        $input = $this->config['installer']['osf-configured'];
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->installer_osf_configured = $this->getBoolean($input);
          }
        }
      }

      /**
       *  OSF Drupal Installation status
       */
      if (isset($this->config['installer']['osf-drupal-configured'])) {
        $input = $this->config['installer']['osf-drupal-configured'];
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->installer_osf_drupal_configured = $this->getBoolean($input);
          }
        }
      }

      if (isset($this->config['installer']['auto-deploy'])) {
        $input = $this->config['installer']['auto-deploy'];
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->auto_deploy = $this->getBoolean($input);
          }
        }
      }

      /**
       * Distro Upgrade
       */
      if (isset($this->config['installer']['upgrade-distro'])) {
        $input = $this->config['installer']['upgrade-distro'];
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->upgrade_distro = $this->getBoolean($input);
          }
        }
      }

      /**
       *  OSF Common
       */
      if (isset($this->config['osf']['application-id'])) {
        $input = $this->config['osf']['application-id'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->application_id = $input;
          }
        }
      }
      if (isset($this->config['osf']['api-key'])) {
        $input = $this->config['osf']['api-key'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->api_key = $input;
          }
        }
      }
      if (isset($this->config['osf']['data-folder'])) {
        $input = $this->config['osf']['data-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->data_folder = $this->getPath($input);
          }
        }
      }
      if (isset($this->config['osf']['logging-folder'])) {
        $input = $this->config['osf']['logging-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->logging_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Web Services
       */
      if (isset($this->config['osf-web-services']['osf-web-services-version'])) {
        $input = $this->config['osf-web-services']['osf-web-services-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_web_services_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->osf_web_services_version = $input;
          }
        }
      }
      if (isset($this->config['osf-web-services']['osf-web-services-folder'])) {
        $input = $this->config['osf-web-services']['osf-web-services-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->osf_web_services_folder = $this->getPath($input);
          }
        }
      }
      if (isset($this->config['osf-web-services']['osf-web-services-domain'])) {
        $input = $this->config['osf-web-services']['osf-web-services-domain'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->osf_web_services_domain = $input;
          }
        }
      }

      /**
       *  OSF WS-PHP-API
       */
      if (isset($this->config['osf-components']['osf-ws-php-api-version'])) {
        $input = $this->config['osf-components']['osf-ws-php-api-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_ws_php_api_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->osf_ws_php_api_version = $input;
          }
        }
      }
      if (isset($this->config['osf-components']['osf-ws-php-api-folder'])) {
        $input = $this->config['osf-components']['osf-ws-php-api-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->osf_ws_php_api_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Tests Suites
       */
      if (isset($this->config['osf-components']['osf-tests-suites-version'])) {
        $input = $this->config['osf-components']['osf-tests-suites-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_tests_suites_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->osf_tests_suites_version = $input;
          }
        }
      }
      if (isset($this->config['osf-components']['osf-tests-suites-folder'])) {
        $input = $this->config['osf-components']['osf-tests-suites-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->osf_tests_suites_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Data Validator Tool
       */
      if (isset($this->config['osf-components']['data-validator-tool-version'])) {
        $input = $this->config['osf-components']['data-validator-tool-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->data_validator_tool_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->data_validator_tool_version = $input;
          }
        }
      }
      if (isset($this->config['osf-components']['data-validator-tool-folder'])) {
        $input = $this->config['osf-components']['data-validator-tool-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->data_validator_tool_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Permissions Management Tool
       */
      if (isset($this->config['osf-tools']['permissions-management-tool-version'])) {
        $input = $this->config['osf-tools']['permissions-management-tool-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->permissions_management_tool_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->permissions_management_tool_version = $input;
          }
        }
      }
      if (isset($this->config['osf-tools']['permissions-management-tool-folder'])) {
        $input = $this->config['osf-tools']['permissions-management-tool-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->permissions_management_tool_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Datasets Management Tool
       */
      if (isset($this->config['osf-tools']['datasets-management-tool-version'])) {
        $input = $this->config['osf-tools']['datasets-management-tool-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->datasets_management_tool_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->datasets_management_tool_version = $input;
          }
        }
      }
      if (isset($this->config['osf-tools']['datasets-management-tool-folder'])) {
        $input = $this->config['osf-tools']['datasets-management-tool-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->datasets_management_tool_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  OSF Ontologies Management Tool
       */
      if (isset($this->config['osf-tools']['ontologies-management-tool-version'])) {
        $input = $this->config['osf-tools']['ontologies-management-tool-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->ontologies_management_tool_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->ontologies_management_tool_version = $input;
          }
        }
      }
      if (isset($this->config['osf-tools']['ontologies-management-tool-folder'])) {
        $input = $this->config['osf-tools']['ontologies-management-tool-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->ontologies_management_tool_folder = $this->getPath($input);
          }
        }
      }

      /**
       *  Drupal framework
       */
      if (isset($this->config['osf-drupal']['drupal-version'])) {
        $input = $this->config['osf-drupal']['drupal-version'];
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->drupal_version = 'master';
          } elseif ($this->isVersion($input)) {
            $this->drupal_version = $input;
          }
        }
      }
      if (isset($this->config['osf-drupal']['drupal-folder'])) {
        $input = $this->config['osf-drupal']['drupal-folder'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->drupal_folder = $this->getPath($input);
          }
        }
      }
      if (isset($this->config['osf-drupal']['drupal-domain'])) {
        $input = $this->config['osf-drupal']['drupal-domain'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->drupal_domain = $input;
          }
        }
      }
      if (isset($this->config['osf-drupal']['drupal-admin-username'])) {
        $input = $this->config['osf-drupal']['drupal-admin-username'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->drupal_admin_username = $input;
          }
        }
      }
      if (isset($this->config['osf-drupal']['drupal-admin-password'])) {
        $input = $this->config['osf-drupal']['drupal-admin-password'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->drupal_admin_password = $input;
          }
        }
      }

      /**
       *  SQL dependency
       */
      if (isset($this->config['sql']['sql-server'])) {
        $input = $this->config['sql']['sql-server'];
        if (!empty($input)) {
          if ($input == 'mysql') {
            $this->sql_server = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-host'])) {
        $input = $this->config['sql']['sql-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->sql_host = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-port'])) {
        $input = $this->config['sql']['sql-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->sql_port = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-root-username'])) {
        $input = $this->config['sql']['sql-root-username'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_root_username = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-root-password'])) {
        $input = $this->config['sql']['sql-root-password'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_root_password = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-app-username'])) {
        $input = $this->config['sql']['sql-app-username'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_app_username = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-app-password'])) {
        $input = $this->config['sql']['sql-app-password'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_app_password = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-app-database'])) {
        $input = $this->config['sql']['sql-app-database'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_app_database = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-app-engine'])) {
        $input = $this->config['sql']['sql-app-engine'];
        if (!empty($input)) {
          if ($input == 'innodb' || $input == 'xtradb') {
            $this->sql_app_engine = $input;
          }
        }
      }
      if (isset($this->config['sql']['sql-app-collation'])) {
        $input = $this->config['sql']['sql-app-collation'];
        if (!empty($input)) {
          if ($input == 'utf8_general_ci') {
            $this->sql_app_collation = $input;
          }
        }
      }

      /**
       *  SPARQL dependency
       */
      if (isset($this->config['sparql']['sparql-server'])) {
        $input = $this->config['sparql']['sparql-server'];
        if (!empty($input)) {
          if ($input == 'virtuoso') {
            $this->sparql_server = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-channel'])) {
        $input = $this->config['sparql']['sparql-channel'];
        if (!empty($input)) {
          if ($input == 'odbc' || $input == 'http') {
            $this->sparql_channel = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-dsn'])) {
        $input = $this->config['sparql']['sparql-dsn'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sparql_dsn = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-host'])) {
        $input = $this->config['sparql']['sparql-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->sparql_host = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-port'])) {
        $input = $this->config['sparql']['sparql-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->sparql_port = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-url'])) {
        $input = $this->config['sparql']['sparql-url'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->sparql_url = $this->getPath($input);
          }
        }
      }
      if (isset($this->config['sparql']['sparql-graph-url'])) {
        $input = $this->config['sparql']['sparql-graph-url'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->sparql_graph_url = $this->getPath($input);
          }
        }
      }
      if (isset($this->config['sparql']['sparql-username'])) {
        $input = $this->config['sparql']['sparql-username'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sparql_username = $input;
          }
        }
      }
      if (isset($this->config['sparql']['sparql-password'])) {
        $input = $this->config['sparql']['sparql-password'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sparql_password = $input;
          }
        }
      }

      /**
       *  Keycache dependency
       */
      if (isset($this->config['keycache']['keycache-enabled'])) {
        $input = $this->config['keycache']['keycache-enabled'];
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->keycache_enabled = $this->getBoolean($input);
          }
        }
      }
      if (isset($this->config['keycache']['keycache-server'])) {
        $input = $this->config['keycache']['keycache-server'];
        if (!empty($input)) {
          if ($input == 'memcached') {
            $this->keycache_server = $input;
          }
        }
      }
      if (isset($this->config['keycache']['keycache-host'])) {
        $input = $this->config['keycache']['keycache-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->keycache_host = $input;
          }
        }
      }
      if (isset($this->config['keycache']['keycache-port'])) {
        $input = $this->config['keycache']['keycache-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->keycache_port = $input;
          }
        }
      }
      if (isset($this->config['keycache']['keycache-ui-password'])) {
        $input = $this->config['keycache']['keycache-ui-password'];
        if (!empty($input)) {
          $this->keycache_ui_password = $input;
        }
      }

      /**
       *  Solr dependency
       */
      if (isset($this->config['solr']['solr-host'])) {
        $input = $this->config['solr']['solr-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->solr_host = $input;
          }
        }
      }
      if (isset($this->config['solr']['solr-port'])) {
        $input = $this->config['solr']['solr-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->solr_port = $input;
          }
        }
      }
      if (isset($this->config['solr']['solr-core'])) {
        $input = $this->config['solr']['solr-core'];
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->solr_core = $input;
          }
        }
      }

      /**
       *  OWL dependency
       */
      if (isset($this->config['owl']['owl-host'])) {
        $input = $this->config['owl']['owl-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->owl_host = $input;
          }
        }
      }
      if (isset($this->config['owl']['owl-port'])) {
        $input = $this->config['owl']['owl-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->owl_port = $input;
          }
        }
      }

      /**
       *  Scones dependency
       */
      if (isset($this->config['scones']['scones-host'])) {
        $input = $this->config['scones']['scones-host'];
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->scones_host = $input;
          }
        }
      }
      if (isset($this->config['scones']['scones-port'])) {
        $input = $this->config['scones']['scones-port'];
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->scones_port = $input;
          }
        }
      }
      if (isset($this->config['scones']['scones-url'])) {
        $input = $this->config['scones']['scones-url'];
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->scones_url = $this->getPath($input);
          }
        }
      }

      // Prepare log file
      exec("mkdir -p {$this->logging_folder}");
      date_default_timezone_set('UTC');
      $this->log_file = $this->logging_folder . '/osf-install-' . date('Y-m-d_H:i:s') . '.log';
      exec("touch {$this->log_file}");
    }


    public function runOSFTestsSuites($installationFolder = '')
    {
      if ($installationFolder == '') {
        $installationFolder = $this->osf_web_services_folder;
      }

      $this->chdir($installationFolder.'/StructuredDynamics/osf/tests/');

      passthru('phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml');
      $this->chdir($this->currentWorkingDirectory);
    }

    /**
     * Upgrade OSF Tests suites
     */
    public function upgrade_OSF_TestsSuites($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";
      $bckPath = "/tmp/osf/tests-" . date('Y-m-d_H-i-s');

      $this->span("Backup of the previous version is available here: {$bckPath}");

      // Get previous settings in Config.php
      $configFile = file_get_contents("{$installPath}/Config.php");

      preg_match('/this-\>osfInstanceFolder = "(.*)"/', $configFile, $matches);
      $osfInstanceFolderExtracted = $matches[1];
      preg_match('/this-\>endpointUrl = "(.*)"/', $configFile, $matches);
      $endpointUrlExtracted = $matches[1];
      preg_match('/this-\>endpointUri = "(.*)"/', $configFile, $matches);
      $endpointUriExtracted = $matches[1];
      preg_match('/this-\>userID = \'(.*)\'/', $configFile, $matches);
      $userIDExtracted = $matches[1];
      preg_match('/this-\>adminGroup = \'(.*)\'/', $configFile, $matches);
      $adminGroupExtracted = $matches[1];
      preg_match('/this-\>testGroup = "(.*)"/', $configFile, $matches);
      $testGroupExtracted = $matches[1];
      preg_match('/this-\>testUser = "(.*)"/', $configFile, $matches);
      $testUserExtracted = $matches[1];
      preg_match('/this-\>applicationID = \'(.*)\'/', $configFile, $matches);
      $applicationIDExtracted = $matches[1];
      preg_match('/this-\>apiKey = \'(.*)\'/', $configFile, $matches);
      $apiKeyExtracted = $matches[1];

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/", "{$bckPath}/");

      // Install
      $this->install_OSF_TestsSuites($pkgVersion);

      // Restore
      $this->span("Restoring settings...", 'info');
      $this->sed('REPLACEME', $this->osf_web_services_folder.'/StructuredDynamics/osf', "{$installPath}/phpunit.xml");

      // Apply existing settings to new Config.php file
      $this->sed('$this-\>osfInstanceFolder = \".*\";', '$this-\>osfInstanceFolder = \"'.$osfInstanceFolderExtracted.'\";', "{$installPath}/Config.php");
      $this->sed('$this-\>endpointUrl = \".*\";', '$this-\>endpointUrl = \"'.$endpointUrlExtracted.'\";', "{$installPath}/Config.php");
      $this->sed('$this-\>endpointUri = \".*\";', '$this-\>endpointUri = \"'.$endpointUriExtracted.'\";', "{$installPath}/Config.php");
      $this->sed('$this-\>userID = \'.*\';', '$this-\>userID = \''.$userIDExtracted.'\';', "{$installPath}/Config.php");
      $this->sed('$this-\>adminGroup = \'.*\';', '$this-\>adminGroup = \''.$adminGroupExtracted.'\';', "{$installPath}/Config.php");
      $this->sed('$this-\>testGroup = \".*\";', '$this-\>testGroup = \"'.$testGroupExtracted.'\";', "{$installPath}/Config.php");
      $this->sed('$this-\>testUser = \".*\";', '$this-\>testUser = \"'.$testUserExtracted.'\";', "{$installPath}/Config.php");
      $this->sed('$this-\>applicationID = \'.*\';', '$this-\>applicationID = \''.$applicationIDExtracted.'\';', "{$installPath}/Config.php");
      $this->sed('$this-\>apiKey = \'.*\';', '$this-\>apiKey = \''.$apiKeyExtracted.'\';', "{$installPath}/Config.php");

    }

    /**
     * Install OSF Tests suites
     */
    public function install_OSF_TestsSuites($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";
      $tmpPath = "/tmp/osf/tests";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Tests-Suites/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Tests-Suites-{$pkgVersion}/StructuredDynamics/osf/tests/.", "{$installPath}/", TRUE);

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }



    /**
     *  Ask a series of questions to the user to configure the installer
     *  software related to OSF Web Services
     */
    public function configureInstallerOSF()
    {
      $this->h2("Configure the OSF-Installer Tool for OSF deployment");
      $this->span("Note: if you want to use the default value, you simply have to press Enter on your keyboard.", 'info');

      /**
       *  Linux Distro
       */
      $this->h3("Linux Distribution Configuration");
      do {
        $input = $this->getInput("Would you like to upgrade the softwares of the distribution to their latest version? (current: {$this->upgrade_distro}, valid: true or false)");
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->upgrade_distro = $this->getBoolean($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       * Installation Configuration
       */
      $this->h3("Installation Configuration");
      do {
        $input = $this->getInput("Is the installation performed by an automatic deployment process? (current: {$this->auto_deploy}, valid: true or false)");
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->auto_deploy = $this->getBoolean($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Common
       */
      $this->h3("OSF Common configuration");
      do {
        $input = $this->getInput("First Application ID used by the core OSF: (current: {$this->application_id})");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->application_id = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("API Key associated to the Application ID: (current: {$this->api_key})");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->api_key = $input;
            break;
          }
        } elseif (empty($input) && $this->api_key == "some-key") {
          $this->span("Generating a API Key...", 'info');
          $this->api_key = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));
          $this->span("The generated API Key is {$this->api_key}", 'info');
          break;
        } else {
          $this->span("The already configured API Key is {$this->api_key}", 'info');
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Path to the main OSF folder where data and configuration files will be located: (current: {$this->data_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->data_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Path where the installation logging files will be located: (current: {$this->logging_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->logging_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Web Services
       */
      $this->h3("OSF Web Services configuration");
      do {
        $input = $this->getInput("OSF version to install: (current: " . ($this->osf_web_services_version == 'master' ? 'dev' : $this->osf_web_services_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_web_services_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->osf_web_services_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("OSF installation path: (current: {$this->osf_web_services_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->osf_web_services_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Domain or IP where OSF web services will be accessible: (current: {$this->osf_web_services_domain})");
        if (!empty($input)) {
          if ($this->isDomain($input) || $this->isIP($input)) {
            $this->osf_web_services_domain = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF WS-PHP-API
       */
      $this->h3("OSF WS-PHP-API configuration");
      do {
        $input = $this->getInput("OSF-PHP-API version to install: (current: " . ($this->osf_ws_php_api_version == 'master' ? 'dev' : $this->osf_ws_php_api_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_ws_php_api_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->osf_ws_php_api_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("OSF-PHP-API installation path: (current: {$this->osf_ws_php_api_folder})");
        if (!empty($input)) {
          if ($this->isPath($input, FALSE)) {
            $this->osf_ws_php_api_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Tests Suites
       */
      $this->h3("OSF Tests Suites configuration");
      do {
        $input = $this->getInput("OSF Tests Suites version to install: (current: " . ($this->osf_tests_suites_version == 'master' ? 'dev' : $this->osf_tests_suites_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->osf_tests_suites_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->osf_tests_suites_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("OSF Tests Suites installation path: (current: {$this->osf_tests_suites_folder})");
        if (!empty($input)) {
          if ($this->isPath($input, FALSE)) {
            $this->osf_tests_suites_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Data Validator Tool
       */
      $this->h3("OSF Data Validator Tool configuration");
      do {
        $input = $this->getInput("DVT version to install: (current: " . ($this->data_validator_tool_version == 'master' ? 'dev' : $this->data_validator_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->data_validator_tool_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->data_validator_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("DVT installation path: (current: {$this->data_validator_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath($input, FALSE)) {
            $this->data_validator_tool_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Permissions Management Tool
       */
      $this->h3("OSF Permissions Management Tool configuration");
      do {
        $input = $this->getInput("PMT version to install: (current: " . ($this->permissions_management_tool_version == 'master' ? 'dev' : $this->permissions_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->permissions_management_tool_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->permissions_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("PMT installation path: (current: {$this->permissions_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->permissions_management_tool_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Datasets Management Tool
       */
      $this->h3("OSF Datasets Management Tool configuration");
      do {
        $input = $this->getInput("DMT version to install: (current: " . ($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->datasets_management_tool_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->datasets_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("DMT installation path: (current: {$this->datasets_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->datasets_management_tool_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Ontologies Management Tool
       */
      $this->h3("OSF Ontologies Management Tool configuration");
      do {
        $input = $this->getInput("OMT version to install: (current: " . ($this->ontologies_management_tool_version == 'master' ? 'dev' : $this->ontologies_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->ontologies_management_tool_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->ontologies_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("OMT installation path: (current: {$this->ontologies_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->ontologies_management_tool_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  SPARQL dependency
       */
      $this->h3("SPARQL dependency configuration");
      do {
        $input = $this->getInput("SPARQL server type: (current: {$this->sparql_server}, valid: virtuoso)");
        if (!empty($input)) {
          if ($input == 'virtuoso') {
            $this->sparql_server = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Communication channel: (current: {$this->sparql_channel}, valid: odbc or http)");
        if (!empty($input)) {
          if ($input == 'odbc' || $input == 'http') {
            $this->sparql_channel = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      if($this->sparql_channel == 'odbc')
      {
        do {
          $input = $this->getInput("SPARQL server DSN: (current: {$this->sparql_dsn}, valid: <dsn>)");
          if (!empty($input)) {
            if ($this->isAlphaNumeric($input)) {
              $this->sparql_dsn = $input;
              break;
            }
          } else {
            break;
          }
        } while (1);
      }
      do {
        $input = $this->getInput("SPARQL server host: (current: {$this->sparql_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->sparql_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SPARQL server port: (current: {$this->sparql_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->sparql_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SPARQL endpoint URI ending: (current: {$this->sparql_url}, valid: <url>)");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->sparql_url = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SPARQL graph crud auth endpoint URI ending: (current: {$this->sparql_graph_url}, valid: <url>)");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->sparql_graph_url = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SPARQL server username: (current: {$this->sparql_username}, valid: <username>)");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sparql_username = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SPARQL server password: (current: {$this->sparql_password}, valid: <password>)");
        if (!empty($input)) {
          $this->sparql_password = $input;
          break;
        } else {
          break;
        }
      } while (1);

      /**
       *  Keycache dependency
       */
      $this->h3("Keycache dependency configuration");
      do {
        $input = $this->getInput("Should the caching server be enabled? (current: {$this->keycache_enabled}, valid: true or false)");
        if (!empty($input)) {
          if ($this->isBoolean($input)) {
            $this->keycache_enabled = $this->getBoolean($input);
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Caching server type: (current: {$this->keycache_server}, valid: memcached)");
        if (!empty($input)) {
          if ($input == 'memcached') {
            $this->keycache_server = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Caching server host: (current: {$this->keycache_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->keycache_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Caching server port: (current: {$this->keycache_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->keycache_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Caching server user interface password for admin user: (current: {$this->keycache_ui_password}, valid: <password>)");
        if (!empty($input)) {
          $this->keycache_ui_password = $input;
          break;
        } else {
          break;
        }
      } while (1);

      /**
       *  Solr dependency
       */
      $this->h3("Solr dependency configuration");
      do {
        $input = $this->getInput("Solr server host: (current: {$this->solr_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->solr_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Solr server port: (current: {$this->solr_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->solr_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Solr server core: (current: {$this->solr_core}, valid: <core>)");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->solr_core = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OWL dependency
       */
      $this->h3("OWL dependency configuration");
      do {
        $input = $this->getInput("Tomcat6 host: (current: {$this->owl_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->owl_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Tomcat6 port: (current: {$this->owl_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->owl_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  Scones dependency
       */
      $this->h3("Scones dependency configuration");
      do {
        $input = $this->getInput("Scones host: (current: {$this->scones_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->scones_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Scones port: (current: {$this->scones_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->scones_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Scones url (without the protocol): (current: {$this->scones_url}, valid: <url>)");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->scones_url = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Installation status
       */
      $this->installer_osf_configured = TRUE;

      $this->saveConfigurations();
    }

    /**
     *  Ask a series of questions to the user to configure the installer
     *  software related to OSF Drupal
     */
    public function configureInstallerOSFDrupal()
    {
      $this->h2("Configure the OSF-Installer Tool for OSF Drupal deployment");
      $this->span("Note: if you want to use the default value, you simply have to press Enter on your keyboard.", 'info');

      /**
       *  Drupal framework
       */
      $this->h3("Drupal framework configuration");
      do {
        $input = $this->getInput("Drupal version (only 7.x are supported): (current: " . ($this->drupal_version == 'master' ? 'dev' : $this->drupal_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if (strtolower($input) == 'dev' || strtolower($input) == 'master') {
            $this->drupal_version = 'dev';
            break;
          } elseif ($this->isVersion($input)) {
            $this->drupal_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Drupal installation path: (current: {$this->drupal_folder})");
        if (!empty($input)) {
          if ($this->isPath($input)) {
            $this->drupal_folder = $this->getPath($input);
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Drupal's instance's domain or IP: (current: {$this->drupal_domain})");
        if (!empty($input)) {
          if ($this->isDomain($input) || $this->isIP($input)) {
            $this->drupal_domain = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Drupal admin username: (current: {$this->drupal_admin_username})");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->drupal_admin_username = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Drupal admin password: (current: {$this->drupal_admin_password})");
        if (!empty($input)) {
          $this->drupal_admin_password = $input;
          break;
        } else {
          break;
        }
      } while (1);

      /**
       *  SQL dependency
       */
      $this->h3("SQL dependency configuration");
      do {
        $input = $this->getInput("Drupal server type: (current: {$this->sql_server}, valid: mysql)");
        if (!empty($input)) {
          if ($input == 'mysql') {
            $this->sql_server = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server host: (current: {$this->sql_host}, valid: <host>)");
        if (!empty($input)) {
          if ($this->isIP($input) || $this->isDomain($input)) {
            $this->sql_host = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server port: (current: {$this->sql_port}, valid: <port>)");
        if (!empty($input)) {
          if ($this->isPort($input)) {
            $this->sql_port = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server root username: (current: {$this->sql_root_username}, valid: <username>)");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_root_username = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server root password: (current: {$this->sql_root_password}, valid: <password>)");
        if (!empty($input)) {
          $this->sql_root_password = $input;
          break;
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server application username: (current: {$this->sql_app_username}, valid: <username>)");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_app_username = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server application password: (current: {$this->sql_app_password}, valid: <password>)");
        if (!empty($input)) {
          $this->sql_app_password = $input;
          break;
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server application database: (current: {$this->sql_app_database}, valid: <database>)");
        if (!empty($input)) {
          if ($this->isAlphaNumeric($input)) {
            $this->sql_app_database = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server application engine: (current: {$this->sql_app_engine}, valid: innodb or xtradb)");
        if (!empty($input)) {
          if ($input == 'innodb' || $input == 'xtradb') {
            $this->sql_app_engine = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("SQL server application colation: (current: {$this->sql_app_collation}, valid: utf8_general_ci)");
        if (!empty($input)) {
          if ($input == 'utf8_general_ci') {
            $this->sql_app_collation = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);

      /**
       *  OSF Drupal Installation status
       */
      $this->installer_osf_drupal_configured = TRUE;

      $this->saveConfigurations();
    }

    /**
     *  Saves the installer configuration to installer.ini file
     */
    private function saveConfigurations()
    {
      $ini  = "[installer]\n";
      $ini .= "version = \"{$this->installer_version}\"\n";
      $ini .= "osf-configured = \"" . ($this->installer_osf_configured ? 'true' : 'false') . "\"\n";
      $ini .= "osf-drupal-configured = \"" . ($this->installer_osf_drupal_configured ? 'true' : 'false') . "\"\n";
      $ini .= "upgrade-distro = \"" . ($this->upgrade_distro ? 'true' : 'false') . "\"\n";
      $ini .= "auto-deploy = \"" . ($this->auto_deploy ? 'true' : 'false') . "\"\n";
      $ini .= "\n";
      $ini .= "[osf]\n";
      $ini .= "application-id = \"{$this->application_id}\"\n";
      $ini .= "api-key = \"{$this->api_key}\"\n";
      $ini .= "data-folder = \"{$this->data_folder}\"\n";
      $ini .= "logging-folder = \"{$this->logging_folder}\"\n";
      $ini .= "\n";
      $ini .= "[osf-web-services]\n";
      $ini .= "osf-web-services-version = \"{$this->osf_web_services_version}\"\n";
      $ini .= "osf-web-services-folder = \"{$this->osf_web_services_folder}\"\n";
      $ini .= "osf-web-services-domain = \"{$this->osf_web_services_domain}\"\n";
      $ini .= "\n";
      $ini .= "[osf-components]\n";
      $ini .= "osf-ws-php-api-version = \"{$this->osf_ws_php_api_version}\"\n";
      $ini .= "osf-ws-php-api-folder = \"{$this->osf_ws_php_api_folder}\"\n";
      $ini .= "osf-tests-suites-version = \"{$this->osf_tests_suites_version}\"\n";
      $ini .= "osf-tests-suites-folder = \"{$this->osf_tests_suites_folder}\"\n";
      $ini .= "data-validator-tool-version = \"{$this->data_validator_tool_version}\"\n";
      $ini .= "data-validator-tool-folder = \"{$this->data_validator_tool_folder}\"\n";
      $ini .= "\n";
      $ini .= "[osf-tools]\n";
      $ini .= "permissions-management-tool-version = \"{$this->permissions_management_tool_version}\"\n";
      $ini .= "permissions-management-tool-folder = \"{$this->permissions_management_tool_folder}\"\n";
      $ini .= "datasets-management-tool-version = \"{$this->datasets_management_tool_version}\"\n";
      $ini .= "datasets-management-tool-folder = \"{$this->datasets_management_tool_folder}\"\n";
      $ini .= "ontologies-management-tool-version = \"{$this->ontologies_management_tool_version}\"\n";
      $ini .= "ontologies-management-tool-folder = \"{$this->ontologies_management_tool_folder}\"\n";
      $ini .= "\n";
      $ini .= "[osf-drupal]\n";
      $ini .= "drupal-version = \"{$this->drupal_version}\"\n";
      $ini .= "drupal-folder = \"{$this->drupal_folder}\"\n";
      $ini .= "drupal-domain = \"{$this->drupal_domain}\"\n";
      $ini .= "drupal-admin-username = \"{$this->drupal_admin_username}\"\n";
      $ini .= "drupal-admin-password = \"{$this->drupal_admin_password}\"\n";
      $ini .= "\n";
      $ini .= "[sql]\n";
      $ini .= "sql-server = \"{$this->sql_server}\"\n";
      $ini .= "sql-host = \"{$this->sql_host}\"\n";
      $ini .= "sql-port = \"{$this->sql_port}\"\n";
      $ini .= "sql-root-username = \"{$this->sql_root_username}\"\n";
      $ini .= "sql-root-password = \"{$this->sql_root_password}\"\n";
      $ini .= "sql-app-username = \"{$this->sql_app_username}\"\n";
      $ini .= "sql-app-password = \"{$this->sql_app_password}\"\n";
      $ini .= "sql-app-database = \"{$this->sql_app_database}\"\n";
      $ini .= "sql-app-engine = \"{$this->sql_app_engine}\"\n";
      $ini .= "sql-app-collation = \"{$this->sql_app_collation}\"\n";
      $ini .= "\n";
      $ini .= "[sparql]\n";
      $ini .= "sparql-server = \"{$this->sparql_server}\"\n";
      $ini .= "sparql-channel = \"{$this->sparql_channel}\"\n";
      $ini .= "sparql-dsn = \"{$this->sparql_dsn}\"\n";
      $ini .= "sparql-host = \"{$this->sparql_host}\"\n";
      $ini .= "sparql-port = \"{$this->sparql_port}\"\n";
      $ini .= "sparql-url = \"{$this->sparql_url}\"\n";
      $ini .= "sparql-graph-url = \"{$this->sparql_graph_url}\"\n";
      $ini .= "sparql-username = \"{$this->sparql_username}\"\n";
      $ini .= "sparql-password = \"{$this->sparql_password}\"\n";
      $ini .= "\n";
      $ini .= "[keycache]\n";
      $ini .= "keycache-enabled = \"{$this->keycache_enabled}\"\n";
      $ini .= "keycache-server = \"{$this->keycache_server}\"\n";
      $ini .= "keycache-host = \"{$this->keycache_host}\"\n";
      $ini .= "keycache-port = \"{$this->keycache_port}\"\n";
      $ini .= "keycache-ui-password = \"{$this->keycache_ui_password}\"\n";
      $ini .= "\n";
      $ini .= "[solr]\n";
      $ini .= "solr-host = \"{$this->solr_host}\"\n";
      $ini .= "solr-port = \"{$this->solr_port}\"\n";
      $ini .= "solr-core = \"{$this->solr_core}\"\n";
      $ini .= "\n";
      $ini .= "[owl]\n";
      $ini .= "owl-host = \"{$this->owl_host}\"\n";
      $ini .= "owl-port = \"{$this->owl_port}\"\n";
      $ini .= "\n";
      $ini .= "[scones]\n";
      $ini .= "scones-host = \"{$this->scones_host}\"\n";
      $ini .= "scones-port = \"{$this->scones_port}\"\n";
      $ini .= "scones-url = \"{$this->scones_url}\"\n";
      $ini .= "\n";

      file_put_contents('installer.ini', $ini);
    }

    /**
    * List current configuration settings
    */
    public function listConfigurations()
    {
      $this->h2("Showing the configuration for the OSF-Installer Tool");

      $this->h3("OSF Installer configuration");
      $this->span("version = \"{$this->installer_version}\"\n", 'info');
      $this->span("osf-configured = \"" . ($this->installer_osf_configured ? 'true' : 'false') . "\"", 'info');
      $this->span("osf-drupal-configured = \"" . ($this->installer_osf_drupal_configured ? 'true' : 'false') . "\"", 'info');
      $this->span("upgrade-distro = \"" . ($this->upgrade_distro ? 'true' : 'false') . "\"", 'info');
      $this->span("auto-deploy = \"" . ($this->auto_deploy ? 'true' : 'false') . "\"", 'info');

      $this->h3("OSF Common configuration");
      $this->span("application-id = \"{$this->application_id}\"", 'info');
      $this->span("api-key = \"{$this->api_key}\"", 'info');
      $this->span("data-folder = \"{$this->data_folder}\"", 'info');
      $this->span("logging-folder = \"{$this->logging_folder}\"", 'info');

      $this->h3("OSF Web Services configuration");
      $this->span("osf-web-services-version = \"{$this->osf_web_services_version}\"", 'info');
      $this->span("osf-web-services-folder = \"{$this->osf_web_services_folder}\"", 'info');
      $this->span("osf-web-services-domain = \"{$this->osf_web_services_domain}\"", 'info');

      $this->h3("OSF WS-PHP-API configuration");
      $this->span("osf-ws-php-api-version = \"{$this->osf_ws_php_api_version}\"", 'info');
      $this->span("osf-ws-php-api-folder = \"{$this->osf_ws_php_api_folder}\"", 'info');

      $this->h3("OSF Tests Suites configuration");
      $this->span("osf-tests-suites-version = \"{$this->osf_tests_suites_version}\"", 'info');
      $this->span("osf-tests-suites-folder = \"{$this->osf_tests_suites_folder}\"", 'info');

      $this->h3("OSF Data Validator Tool configuration");
      $this->span("data-validator-tool-version = \"{$this->data_validator_tool_version}\"", 'info');
      $this->span("data-validator-tool-folder = \"{$this->data_validator_tool_folder}\"", 'info');

      $this->h3("OSF Permissions Management Tool configuration");
      $this->span("permissions-management-tool-version = \"{$this->permissions_management_tool_version}\"", 'info');
      $this->span("permissions-management-tool-folder = \"{$this->permissions_management_tool_folder}\"", 'info');

      $this->h3("OSF Datasets Management Tool configuration");
      $this->span("datasets-management-tool-version = \"{$this->datasets_management_tool_version}\"", 'info');
      $this->span("datasets-management-tool-folder = \"{$this->datasets_management_tool_folder}\"", 'info');

      $this->h3("OSF Ontologies Management Tool configuration");
      $this->span("ontologies-management-tool-version = \"{$this->ontologies_management_tool_version}\"", 'info');
      $this->span("ontologies-management-tool-folder = \"{$this->ontologies_management_tool_folder}\"", 'info');

      $this->h3("Drupal framework configuration");
      $this->span("drupal-version = \"{$this->drupal_version}\"", 'info');
      $this->span("drupal-folder = \"{$this->drupal_folder}\"", 'info');
      $this->span("drupal-domain = \"{$this->drupal_domain}\"", 'info');
      $this->span("drupal-admin-username = \"{$this->drupal_admin_username}\"", 'info');
      $this->span("drupal-admin-password = \"{$this->drupal_admin_password}\"", 'info');

      $this->h3("SQL dependency configuration");
      $this->span("sql-server = \"{$this->sql_server}\"", 'info');
      $this->span("sql-host = \"{$this->sql_host}\"", 'info');
      $this->span("sql-port = \"{$this->sql_port}\"", 'info');
      $this->span("sql-root-username = \"{$this->sql_root_username}\"", 'info');
      $this->span("sql-root-password = \"{$this->sql_root_password}\"", 'info');
      $this->span("sql-app-username = \"{$this->sql_app_username}\"", 'info');
      $this->span("sql-app-password = \"{$this->sql_app_password}\"", 'info');
      $this->span("sql-app-database = \"{$this->sql_app_database}\"", 'info');
      $this->span("sql-app-engine = \"{$this->sql_app_engine}\"", 'info');
      $this->span("sql-app-collation = \"{$this->sql_app_collation}\"", 'info');

      $this->h3("SPARQL dependency configuration");
      $this->span("sparql-server = \"{$this->sparql_server}\"", 'info');
      $this->span("sparql-channel = \"{$this->sparql_channel}\"", 'info');
      $this->span("sparql-dsn = \"{$this->sparql_dsn}\"", 'info');
      $this->span("sparql-host = \"{$this->sparql_host}\"", 'info');
      $this->span("sparql-port = \"{$this->sparql_port}\"", 'info');
      $this->span("sparql-url = \"{$this->sparql_url}\"", 'info');
      $this->span("sparql-graph-url = \"{$this->sparql_graph_url}\"", 'info');
      $this->span("sparql-username = \"{$this->sparql_username}\"", 'info');
      $this->span("sparql-password = \"{$this->sparql_password}\"", 'info');

      $this->h3("Keycache dependency configuration");
      $this->span("keycache-enabled = \"{$this->keycache_enabled}\"", 'info');
      $this->span("keycache-server = \"{$this->keycache_server}\"", 'info');
      $this->span("keycache-host = \"{$this->keycache_host}\"", 'info');
      $this->span("keycache-port = \"{$this->keycache_port}\"", 'info');
      $this->span("keycache-ui-password = \"{$this->keycache_ui_password}\"", 'info');

      $this->h3("Solr dependency configuration");
      $this->span("solr-host = \"{$this->solr_host}\"", 'info');
      $this->span("solr-port = \"{$this->solr_port}\"", 'info');
      $this->span("solr-core = \"{$this->solr_core}\"", 'info');

      $this->h3("OWL dependency configuration");
      $this->span("owl-host = \"{$this->owl_host}\"", 'info');
      $this->span("owl-port = \"{$this->owl_port}\"", 'info');

      $this->h3("Scones dependency configuration");
      $this->span("scones-host = \"{$this->scones_host}\"", 'info');
      $this->span("scones-port = \"{$this->scones_port}\"", 'info');
      $this->span("scones-url = \"{$this->scones_url}\"", 'info');
    }
  }
