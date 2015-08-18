<?php

  include_once('inc/CommandlineTool.php');

  class OSFConfigurator extends CommandlineTool
  {
    /* Parsed intaller.ini configuration file */
    protected $config;

    /* OSF Installation status */
    public $installer_osf_configured = FALSE;

    /* OSF Drupal Installation status */
    public $installer_osf_drupal_configured = FALSE;

    /* OSF Common */
    protected $application_id = 'administer';
    protected $api_key = 'some-key';
    protected $data_folder = "/data";
    protected $logging_folder = "/tmp";

    /* Namespace extension of the OSF Web Services folder. This is where the code resides */
    protected $osf_web_services_ns = "/StructuredDynamics/osf/ws";

    /* OSF Web Services */
    protected $osf_web_services_version = "3.3.0";
    protected $osf_web_services_folder = "/usr/share/osf";
    protected $osf_web_services_domain = "localhost";

    /* OSF WS-PHP-API */
    protected $osf_ws_php_api_version = "3.1.2";
    protected $osf_ws_php_api_folder = "StructuredDynamics/osf";

    /* OSF Tests Suites */
    protected $osf_tests_suites_version = "3.3.0";
    protected $osf_tests_suites_folder = "StructuredDynamics/osf/tests";

    /* OSF Data Validator Tool */
    protected $data_validator_tool_version = "3.1.0";
    protected $data_validator_tool_folder = "StructuredDynamics/osf/validator";

    /* OSF Permissions Management Tool */
    protected $permissions_management_tool_version = "3.1.0";
    protected $permissions_management_tool_folder = "/usr/share/permissions-management-tool";

    /* OSF Datasets Management Tool */
    protected $datasets_management_tool_version = "3.3.0";
    protected $datasets_management_tool_folder = "/usr/share/datasets-management-tool";

    /* OSF Ontologies Management Tool */
    protected $ontologies_management_tool_version = "3.1.0";
    protected $ontologies_management_tool_folder = "/usr/share/ontologies-management-tool";

    /* Drupal framework */
    protected $drupal_version = "7.23";
    protected $drupal_folder = "/usr/share/drupal";
    protected $drupal_domain = "localhost";

    /**
     *  Construct class by loading configuration
     */
    function __construct($configFile)
    {
      parent::__construct();

      // Load the installer configuration file
      $this->config = parse_ini_file($configFile, TRUE);

      if (!$this->config) {
        $this->cecho("An error occured when we tried to parse the {$configFile} file. Make sure it is parseable and try again\n", 'RED');
        exit(1);
      }

      /**
       *  OSF Installation status
       */
      if (isset($this->config['installer']['osfConfigured'])) {
        if (!empty($this->config['installer']['osfConfigured'])) {
          if (strtolower($this->config['installer']['osfConfigured']) === 'false') {
            $this->installer_osf_configured = FALSE;
          } else {
            $this->installer_osf_configured = TRUE;
          }
        }
      }

      /**
       *  OSF Drupal Installation status
       */
      if (isset($this->config['installer']['osfDrupalConfigured'])) {
        if (!empty($this->config['installer']['osfDrupalConfigured'])) {
          if (strtolower($this->config['installer']['osfDrupalConfigured']) === 'false') {
            $this->installer_osf_drupal_configured = FALSE;
          } else {
            $this->installer_osf_drupal_configured = TRUE;
          }
        }
      }

      /**
       *  OSF Common
       */
      if (isset($this->config['osf']['application-id'])) {
        if (!empty($this->config['osf']['application-id'])) {
          $this->application_id = $this->config['osf']['application-id'];
        }
      }
      if (isset($this->config['osf']['api-key'])) {
        if (!empty($this->config['osf']['api-key'])) {
          $this->api_key = $this->config['osf']['api-key'];
        }
      }
      if (isset($this->config['osf']['data-folder'])) {
        if (!empty($this->config['osf']['data-folder'])) {
          $this->data_folder = rtrim($this->config['osf']['data-folder'], '/');
        }
      }
      if (isset($this->config['osf']['logging-folder'])) {
        if (!empty($this->config['osf']['logging-folder'])) {
          $this->logging_folder = rtrim($this->config['osf']['logging-folder'], '/');
        }
      }

      /**
       *  OSF Web Services
       */
      if (isset($this->config['osf-web-services']['osf-web-services-version'])) {
        if (!empty($this->config['osf-web-services']['osf-web-services-version'])) {
          if (strtolower($this->config['osf-web-services']['osf-web-services-version']) == 'dev') {
            $this->osf_web_services_version = 'master';
          } else {
            $this->osf_web_services_version = $this->config['osf-web-services']['osf-web-services-version'];
          }
        }
      }
      if (isset($this->config['osf-web-services']['osf-web-services-folder'])) {
        if (!empty($this->config['osf-web-services']['osf-web-services-folder'])) {
          $this->osf_web_services_folder = rtrim($this->config['osf-web-services']['osf-web-services-folder'], '/');
        }
      }
      if (isset($this->config['osf-web-services']['osf-web-services-domain'])) {
        if (!empty($this->config['osf-web-services']['osf-web-services-domain'])) {
          $this->osf_web_services_domain = $this->config['osf-web-services']['osf-web-services-domain'];
        }
      }

      /**
       *  OSF WS-PHP-API
       */
      if (isset($this->config['osf-components']['osf-ws-php-api-version'])) {
        if (!empty($this->config['osf-components']['osf-ws-php-api-version'])) {
          if (strtolower($this->config['osf-components']['osf-ws-php-api-version']) == 'dev') {
            $this->osf_ws_php_api_version = 'master';
          } else {
            $this->osf_ws_php_api_version = $this->config['osf-components']['osf-ws-php-api-version'];
          }
        }
      }
      if (isset($this->config['osf-components']['osf-ws-php-api-folder'])) {
        if (!empty($this->config['osf-components']['osf-ws-php-api-folder'])) {
          $this->osf_ws_php_api_folder = rtrim($this->config['osf-components']['osf-ws-php-api-folder'], '/');
        }
      }

      /**
       *  OSF Tests Suites
       */
      if (isset($this->config['osf-components']['osf-tests-suites-version'])) {
        if (!empty($this->config['osf-components']['osf-tests-suites-version'])) {
          if (strtolower($this->config['osf-components']['osf-tests-suites-version']) == 'dev') {
            $this->osf_tests_suites_version = 'master';
          } else {
            $this->osf_tests_suites_version = $this->config['osf-components']['osf-tests-suites-version'];
          }
        }
      }
      if (isset($this->config['osf-components']['osf-tests-suites-folder'])) {
        if (!empty($this->config['osf-components']['osf-tests-suites-folder'])) {
          $this->osf_tests_suites_folder = rtrim($this->config['osf-components']['osf-tests-suites-folder'], '/');
        }
      }

      /**
       *  OSF Data Validator Tool
       */
      if (isset($this->config['osf-components']['data-validator-tool-version'])) {
        if (!empty($this->config['osf-components']['data-validator-tool-version'])) {
          if (strtolower($this->config['osf-components']['data-validator-tool-version']) == 'dev') {
            $this->data_validator_tool_version = 'master';
          } else {
            $this->data_validator_tool_version = $this->config['osf-components']['data-validator-tool-version'];
          }
        }
      }
      if (isset($this->config['osf-components']['data-validator-tool-folder'])) {
        if (!empty($this->config['osf-components']['data-validator-tool-folder'])) {
          $this->data_validator_tool_folder = rtrim($this->config['osf-components']['data-validator-tool-folder'], '/');
        }
      }

      /**
       *  OSF Permissions Management Tool
       */
      if (isset($this->config['osf-tools']['permissions-management-tool-version'])) {
        if (!empty($this->config['osf-tools']['permissions-management-tool-version'])) {
          if (strtolower($this->config['osf-tools']['permissions-management-tool-version']) == 'dev') {
            $this->permissions_management_tool_version = 'master';
          } else {
            $this->permissions_management_tool_version = $this->config['osf-tools']['permissions-management-tool-version'];
          }
        }
      }
      if (isset($this->config['osf-tools']['permissions-management-tool-folder'])) {
        if (!empty($this->config['osf-tools']['permissions-management-tool-folder'])) {
          $this->permissions_management_tool_folder = rtrim($this->config['osf-tools']['permissions-management-tool-folder'], '/');
        }
      }

      /**
       *  OSF Datasets Management Tool
       */
      if (isset($this->config['osf-tools']['datasets-management-tool-version'])) {
        if (!empty($this->config['osf-tools']['datasets-management-tool-version'])) {
          if(strtolower($this->config['osf-tools']['datasets-management-tool-version']) == 'dev') {
            $this->datasets_management_tool_version = 'master';
          } else {
            $this->datasets_management_tool_version = $this->config['osf-tools']['datasets-management-tool-version'];
          }
        }
      }
      if (isset($this->config['osf-tools']['datasets-management-tool-folder'])) {
        if (!empty($this->config['osf-tools']['datasets-management-tool-folder'])) {
          $this->datasets_management_tool_folder = rtrim($this->config['osf-tools']['datasets-management-tool-folder'], '/');
        }
      }

      /**
       *  OSF Ontologies Management Tool
       */
      if (isset($this->config['osf-tools']['ontologies-management-tool-version'])) {
        if (!empty($this->config['osf-tools']['ontologies-management-tool-version'])) {
          if (strtolower($this->config['osf-tools']['ontologies-management-tool-version']) == 'dev') {
            $this->ontologies_management_tool_version = 'master';
          } else {
            $this->ontologies_management_tool_version = $this->config['osf-tools']['ontologies-management-tool-version'];
          }
        }
      }
      if (isset($this->config['osf-tools']['ontologies-management-tool-folder'])) {
        if (!empty($this->config['osf-tools']['ontologies-management-tool-folder'])) {
          $this->ontologies_management_tool_folder = rtrim($this->config['osf-tools']['ontologies-management-tool-folder'], '/');
        }
      }

      /**
       *  Drupal framework
       */
      if (isset($this->config['osf-drupal']['drupal-version'])) {
        if (!empty($this->config['osf-drupal']['drupal-version'])) {
          $this->drupal_version = $this->config['osf-drupal']['drupal-version'];
        }
      }
      if (isset($this->config['osf-drupal']['drupal-folder'])) {
        if (!empty($this->config['osf-drupal']['drupal-folder'])) {
          $this->drupal_folder = rtrim($this->config['osf-drupal']['drupal-folder'], '/');
        }
      }
      if (isset($this->config['osf-drupal']['drupal-domain'])) {
        if (!empty($this->config['osf-drupal']['drupal-domain'])) {
          $this->drupal_domain = $this->config['osf-drupal']['drupal-domain'];
        }
      }

      // Dump to log
      $this->log_file = $this->logging_folder . '/osf-install-' . date('Y-m-d_H:i:s') . '.log';
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
       *  OSF Common
       */
      $this->h3("OSF Common configuration");
      $input = $this->getInput("Input a Application ID: (default: {$this->application_id})");
      if (!empty($input)) {
        $this->application_id = $input;
      }
      $input = $this->getInput("Input a API Key: (default: {$this->api_key})");
      if (!empty($input)) {
        $this->api_key = $input;
      }
      do {
        $input = $this->getInput("Input a data path: (default: {$this->data_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->data_folder = rtrim($input, '/');
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a logging path: (default: {$this->logging_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->logging_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->osf_web_services_version == 'master' ? 'dev' : $this->osf_web_services_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->osf_web_services_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->osf_web_services_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->osf_web_services_folder = rtrim($input, '/');
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a domain: (default: {$this->osf_web_services_domain})");
        if (!empty($input)) {
          if ($this->isDomain($input) == TRUE) {
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
        $input = $this->getInput("Input a version: (default: " . ($this->osf_ws_php_api_version == 'master' ? 'dev' : $this->osf_ws_php_api_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->osf_ws_php_api_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->osf_ws_php_api_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/'), FALSE) == TRUE) {
            $this->osf_ws_php_api_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->osf_tests_suites_version == 'master' ? 'dev' : $this->osf_tests_suites_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->osf_tests_suites_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->osf_tests_suites_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/'), FALSE) == TRUE) {
            $this->osf_tests_suites_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->data_validator_tool_version == 'master' ? 'dev' : $this->data_validator_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->data_validator_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->data_validator_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/'), FALSE) == TRUE) {
            $this->data_validator_tool_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->permissions_management_tool_version == 'master' ? 'dev' : $this->permissions_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->permissions_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->permissions_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->permissions_management_tool_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->datasets_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->datasets_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->datasets_management_tool_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->ontologies_management_tool_version == 'master' ? 'dev' : $this->ontologies_management_tool_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->ontologies_management_tool_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->ontologies_management_tool_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->ontologies_management_tool_folder = rtrim($input, '/');
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
        $input = $this->getInput("Input a version: (default: " . ($this->drupal_version == 'master' ? 'dev' : $this->drupal_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->drupal_version = $input;
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a path: (default: {$this->drupal_folder})");
        if (!empty($input)) {
          if ($this->isPath(rtrim($input, '/')) == TRUE) {
            $this->drupal_folder = rtrim($input, '/');
            break;
          }
        } else {
          break;
        }
      } while (1);
      do {
        $input = $this->getInput("Input a domain: (default: {$this->drupal_domain})");
        if (!empty($input)) {
          if ($this->isDomain($input) == TRUE) {
            $this->drupal_domain = $input;
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
      $ini .= "osfConfigured = \"" . ($this->installer_osf_configured ? 'true' : 'false') . "\"\n";
      $ini .= "osfDrupalConfigured = \"" . ($this->installer_osf_drupal_configured ? 'true' : 'false') . "\"\n";
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
      $this->span("osfConfigured = \"" . ($this->installer_osf_configured ? 'true' : 'false') . "\"", 'info');
      $this->span("osfDrupalConfigured = \"" . ($this->installer_osf_drupal_configured ? 'true' : 'false') . "\"", 'info');

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
    }

    /**
    * Upgrade the OSF PHPUNIT Tests Suites
    */
    public function upgradeOSFTestsSuites($version)
    {
      $this->cecho("Upgrading tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/osftestssuites-upgrade/');
      
      $this->chdir('/tmp/osftestssuites-upgrade/');
      
      $this->wget('https://github.com/structureddynamics/OSF-Tests-Suites/archive/'.$version.'.zip');
      
      $this->exec('unzip '.$version.'.zip');
      
      $this->chdir('/tmp/osftestssuites-upgrade/OSF-Tests-Suites-'.$version.'/StructuredDynamics/osf/');

      // Extract existing settings
      $configFile = file_get_contents($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/Config.php');

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
      
      $this->exec('rm -rf '.$this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('cp -af tests '.$this->osf_web_services_folder.'/StructuredDynamics/osf/');
                  
      $this->cecho("Configure the tests suites...\n", 'WHITE');

      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->osf_web_services_folder.'/StructuredDynamics/osf>" phpunit.xml');

      // Apply existing settings to new Config.php file
      $this->exec('sudo sed -i "s>$this-\>osfInstanceFolder = \".*\";>$this-\>osfInstanceFolder = \"'.$osfInstanceFolderExtracted.'\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \".*\";>$this-\>endpointUrl = \"'.$endpointUrlExtracted.'\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \".*\";>$this-\>endpointUri = \"'.$endpointUriExtracted.'\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>userID = \'.*\';>$this-\>userID = \''.$userIDExtracted.'\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>adminGroup = \'.*\';>$this-\>adminGroup = \''.$adminGroupExtracted.'\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>testGroup = \".*\";>$this-\>testGroup = \"'.$testGroupExtracted.'\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>testUser = \".*\";>$this-\>testUser = \"'.$testUserExtracted.'\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>applicationID = \'.*\';>$this-\>applicationID = \''.$applicationIDExtracted.'\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>apiKey = \'.*\';>$this-\>apiKey = \''.$apiKeyExtracted.'\';>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/osftestssuites-upgrade/');
    }         
    
    public function runOSFTestsSuites($installationFolder = '')
    {
      if($installationFolder == '')
      {
        $installationFolder = $this->osf_web_services_folder;
      }
      
      $this->chdir($installationFolder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('phpunit --configuration phpunit.xml --filter \'StructuredDynamics\\osf\\tests\\ws\\crud\\read\\CrudReadTest::testLanguageEnglishSpecified\'');
      
      $this->cecho("Restarting Virtuoso...\n", 'WHITE');
      $this->exec('/etc/init.d/virtuoso stop');
      
      sleep(20);
      
      $this->exec('/etc/init.d/virtuoso start');             
      
      passthru('phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml');
      
      $this->chdir($this->currentWorkingDirectory);      
    }    
  }
?>
