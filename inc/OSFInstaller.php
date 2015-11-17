<?php

  use \StructuredDynamics\osf\php\api\framework\ServerIDQuery;

  include_once('OSFConfigurator.php');

  abstract class OSFInstaller extends OSFConfigurator
  {

    function __construct($configFile)
    {
      parent::__construct($configFile);
    }

    /**
     * Tries to install PHP5 using the packages available for the Linux distribution
     */
    abstract public function installPhp5();

    /**
     * Install Virtuoso as required by OSF
     */
    abstract public function installVirtuoso();

    /**
     * Install Solr as required by OSF
     */
    abstract public function installSolr();

    /**
     * Install Memcached as required by OSF
     */
    abstract public function installMemcached();

    /**
     * Install Apache2 as required by OSF
     */
    abstract public function installApache2();

    /**
     * Install MySQL as required by OSF
     */
    abstract public function installSQL();

    /**
     * Install MySQL as required by OSF
     */
    abstract public function installPhpMyAdmin();

    /**
     * Install the entire OSF stack. Running this command installs the full stack on the server
     * according to the settings specified in the installer.ini file.
     */
    public function install_OSF()
    {
      $this->span("You are about to install the Open Semantic Framework.");
      $this->span("This installation process installs all the software components that are part of the OSF stack. It will take 10 minutes of your time, but the process will go on for a few hours because of the many pieces of software that get compiled.\n");
      $this->span("The log of this installation is available here: ".$this->log_file);
      $this->span("\n\nCopyright 2008-15. Structured Dynamics LLC. All rights reserved.\n\n");

      $this->h1("General Settings Initialization");
      $this->prepareDistro();

      // Dependency chain:
      // PHP stack for OSF and OSF-Drupal
      $this->installSQL('client');
      $this->installPHP();
      $this->installApache();
      // Java stack for Solr, Owl and Scones
      $this->installJava();
      $this->installTomcat();
      // Backends
      $this->installSQL('server');
      $this->installVirtuoso();
      $this->installSolr();
      $this->installMemcached();
      // Tools
      $this->installPhpMyAdmin();

      // Generate some OSF API key is none has been defined by the user
      if(empty($this->api_key) || $this->api_key == "some-key") 
      {
        $this->span("Generating a OSF API Key...", 'info');
        $this->api_key = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));
        $this->span("The generated API Key is {$this->api_key}", 'notice');
      }

      // OSF Tools, Components and Web Service
      $this->switch_OSF_PermissionsManagementTool('install');
      $this->switch_OSF_DatasetsManagementTool('install');
      $this->switch_OSF_OntologiesManagementTool('install');
      $this->switch_OSF_DataValidatorTool('install');
      $this->switch_OSF_TestsSuites('install');
      $this->switch_OSF_WSPHPAPI('install');
      $this->switch_OSF_WebServices('install');

      $this->install_OSF_vhost();
      $this->setupSolr();
      $this->installARC2();
      $this->installOWLAPI();
      $this->setupVirtuoso();
      $this->load_OSF_OntologiesManagementTool();

      $this->exec('/etc/init.d/apache2 restart');

      $this->installPHPUnit();
      $this->runOSFTestsSuites($this->osf_web_services_folder);

      $this->span("Now that the OSF instance is installed, you can install OSF for Drupal on the same server using this command:\n\n", 'notice');
      $this->span("    ./osf-installer -d --install-osf-drupal\n\n", 'notice');
    }

    /**
     * Install Drupal and the OSF Drupal modules
     */
    abstract public function install_OSF_Drupal();

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
     * Switch for OSF Web Services
     */
    public function switch_OSF_WebServices($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Web Services";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->osf_web_services_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_web_services_ns}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-web-services", 'warn');
            return;
          }
          $this->install_OSF_WebServices($pkgVersion);
          $this->config_OSF_WebServices();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-web-services", 'warn');
            return;
          }
          $this->upgrade_OSF_WebServices($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_WebServices($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-web-services", 'warn');
            return;
          }
          $this->config_OSF_WebServices($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Web Services
     */
    private function install_OSF_WebServices($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}";
      $dataPath = "{$this->data_folder}";
      $tmpPath = "/tmp/osf/web-services";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Web-Services/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      
      $this->cp("{$tmpPath}/OSF-Web-Services-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chown("{$installPath}/", "www-data", TRUE);
      $this->chgrp("{$installPath}/", "www-data", TRUE);
      $this->chmod("{$installPath}/", "755", TRUE);
      
      $this->mkdir("{$dataPath}/osf-web-services/tmp/");          
      $this->mkdir("{$dataPath}/osf-web-services/configs/");
      $this->chown("{$dataPath}/osf-web-services/", "www-data", TRUE);
      $this->chgrp("{$dataPath}/osf-web-services/", "www-data", TRUE);
      $this->chmod("{$dataPath}/osf-web-services/", "500", TRUE);
      $this->chmod("{$dataPath}/osf-web-services/tmp/", "700", TRUE);

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF Web Services
     */
    private function upgrade_OSF_WebServices($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_web_services_ns}";
      $bckPath = "/tmp/osf/web-services-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_WebServices($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/osf.ini", "{$installPath}/");
      $this->mv("{$bckPath}/keys.ini", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Web Services
     */
    private function uninstall_OSF_WebServices()
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_web_services_ns}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
    }

    /**
     * Configure OSF Web Services
     */
    private function config_OSF_WebServices()
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_web_services_ns}";
      $dataPath = "{$this->data_folder}/osf-web-services/configs";

      // Configure
      $this->span("Configuring...", 'info');
      $this->cp("{$installPath}/keys.ini", "{$dataPath}/keys.ini");
      $this->cp("{$installPath}/osf.ini", "{$dataPath}/osf.ini");
      $this->rm("{$installPath}/keys.ini");
      $this->rm("{$installPath}/osf.ini");
      // OSF Web Service scripts
      $this->sed("public static \$osf_ini = \".*\";", "public static \$osf_ini = \"{$dataPath}/\";",
        "{$installPath}/framework/WebService.php");
      $this->sed("public static \$keys_ini = \".*\";", "public static \$keys_ini = \"{$dataPath}/\";",
        "{$installPath}/framework/WebService.php");
      // OSF Web Service credentials
      if(stripos(file_get_contents("{$dataPath}/keys.ini"), "{$this->application_id} = ") === FALSE)
      {
        $this->append("\n{$this->application_id} = \"{$this->api_key}\"",
         "{$dataPath}/keys.ini");
      }
      else
      {
        $this->sed("{$this->application_id} = .*", "", "{$dataPath}/keys.ini");
        $this->append("{$this->application_id} = \"{$this->api_key}\"",
         "{$dataPath}/keys.ini");
      }
      // OSF Web Service paths
      $this->setIni("network", "wsf_base_url", "\"http://{$this->osf_web_services_domain}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("network", "wsf_base_path", "\"{$this->osf_web_services_folder}/{$this->osf_web_services_ns}/\"",
        "{$dataPath}/osf.ini");
      // OSF Tools paths
      $this->setIni("datasets", "wsf_graph", "\"http://{$this->osf_web_services_domain}/wsf/\"",
        "{$dataPath}/osf.ini");
      $this->setIni("datasets", "dtd_base", "\"http://{$this->osf_web_services_domain}/ws/dtd/\"",
        "{$dataPath}/osf.ini");
      $this->setIni("ontologies", "ontologies_files_folder", "\"{$this->data_folder}/ontologies/files/\"",
        "{$dataPath}/osf.ini");
      $this->setIni("ontologies", "ontological_structure_folder", "\"{$this->data_folder}/ontologies/structure/\"",
        "{$dataPath}/osf.ini");
      // SPARQL dependency
      $this->setIni("triplestore", "channel", "\"{$this->sparql_channel}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "dsn", "\"{$this->sparql_dsn}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "host", "\"{$this->sparql_host}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "port", "\"{$this->sparql_port}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "sparql", "\"{$this->sparql_url}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "sparql-graph", "\"{$this->sparql_graph_url}\"",
        "{$dataPath}/osf.ini");
      if ($this->sparql_channel == 'http') {
        $this->setIni("triplestore", "sparql-insert", "\"insert\"",
          "{$dataPath}/osf.ini");
      }
      $this->setIni("triplestore", "username", "\"{$this->sparql_username}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("triplestore", "password", "\"{$this->sparql_password}\"",
        "{$dataPath}/osf.ini");
      // Keycache dependency
      $this->setIni("memcached", "memcached_enabled", "\"{$this->keycache_enabled}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("memcached", "memcached_host", "\"{$this->keycache_host}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("memcached", "memcached_port", "\"{$this->keycache_port}\"",
        "{$dataPath}/osf.ini");
      // Solr dependency
      $this->setIni("solr", "solr_host", "\"{$this->solr_host}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("solr", "solr_port", "\"{$this->solr_port}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("solr", "solr_core", "\"{$this->solr_core}\"",
        "{$dataPath}/osf.ini");
      $this->setIni("solr", "fields_index_folder", "\"{$this->data_folder}/osf-web-services/tmp/\"",
        "{$dataPath}/osf.ini");
      // OWL dependency
      $this->setIni("scones", "endpoint", "\"http://{$this->scones_host}:{$this->scones_port}/{$this->scones_url}/\"",
        "{$dataPath}/osf.ini");
      // Other
      $this->setIni("geo", "geoenabled", "\"true\"",
        "{$dataPath}/osf.ini");
    }

    /**
     * Switch for OSF WS-PHP-API
     */
    public function switch_OSF_WSPHPAPI($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF WS-PHP-API Library";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->osf_ws_php_api_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_ws_php_api_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/php/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-ws-php-api", 'warn');
            return;
          }
          $this->install_OSF_WSPHPAPI($pkgVersion);
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/php/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-ws-php-api", 'warn');
            return;
          }
          $this->upgrade_OSF_WSPHPAPI($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/php/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_WSPHPAPI($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF WS-PHP-API
     */
    private function install_OSF_WSPHPAPI($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_ws_php_api_folder}";
      $tmpPath = "/tmp/osf/ws-php-api";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Web-Services-PHP-API/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Web-Services-PHP-API-{$pkgVersion}/StructuredDynamics/osf/.", "{$installPath}/", TRUE);

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF WS-PHP-API
     */
    private function upgrade_OSF_WSPHPAPI($pkgVersion = '')
    {
      // Install
      $this->install_OSF_WSPHPAPI($pkgVersion);
    }

    /**
     * Uninstall OSF WS-PHP-API
     */
    private function uninstall_OSF_WSPHPAPI()
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_ws_php_api_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/php/", TRUE);
    }

    /**
     * Switch for OSF Tests suites
     */
    public function switch_OSF_TestsSuites($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Tests suites";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->osf_tests_suites_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-tests-suites", 'warn');
            return;
          }
          $this->install_OSF_TestsSuites($pkgVersion);
          $this->config_OSF_TestsSuites();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-tests-suites", 'warn');
            return;
          }
          $this->upgrade_OSF_TestsSuites($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_TestsSuites($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-tests-suites", 'warn');
            return;
          }
          $this->config_OSF_TestsSuites($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Tests suites
     */
    private function install_OSF_TestsSuites($pkgVersion = '')
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
     * Upgrade OSF Tests suites
     */
    private function upgrade_OSF_TestsSuites($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";
      $bckPath = "/tmp/osf/tests-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_TestsSuites($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/phpunit.xml", "{$installPath}/");
      $this->mv("{$bckPath}/Config.php", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Tests suites
     */
    private function uninstall_OSF_TestsSuites()
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
    }

    /**
     * Configure OSF Tests suites
     */
    private function config_OSF_TestsSuites()
    {
      // Get package info
      $configPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("REPLACEME", "{$this->osf_web_services_folder}/StructuredDynamics/osf",
        "{$configPath}/phpunit.xml");
      $this->sed("\$this-\>osfInstanceFolder = \".*\";", "\$this-\>osfInstanceFolder = \"{$this->osf_web_services_folder}/{$this->osf_web_services_ns}/\";",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>endpointUrl = \".*/ws/\";", "\$this-\>endpointUrl = \"http://{$this->osf_web_services_domain}/ws/\";",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>endpointUri = \".*/wsf/ws/\";", "\$this-\>endpointUri = \"http://{$this->osf_web_services_domain}/wsf/ws/\";",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>userID = '.*/wsf/users/tests-suites';", "\$this-\>userID = 'http://{$this->osf_web_services_domain}/wsf/users/tests-suites';",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>adminGroup = '.*/wsf/groups/administrators';", "\$this-\>adminGroup = 'http://{$this->osf_web_services_domain}/wsf/groups/administrators';",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>testGroup = \".*/wsf/groups/unittests\";", "\$this-\>testGroup = \"http://{$this->osf_web_services_domain}/wsf/groups/unittests\";",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>testUser = \".*/wsf/users/unittests\";", "\$this-\>testUser = \"http://{$this->osf_web_services_domain}/wsf/users/unittests\";",
        "{$configPath}/Config.php");
      // Web Service credentials
      $this->sed("\$this-\>applicationID = '.*';", "\$this-\>applicationID = '{$this->application_id}';",
        "{$configPath}/Config.php");
      $this->sed("\$this-\>apiKey = '.*';", "\$this-\>apiKey = '{$this->api_key}';",
        "{$configPath}/Config.php");
    }

    /**
     * Switch for OSF Data Validator Tool
     */
    public function switch_OSF_DataValidatorTool($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Data Validator Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->data_validator_tool_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-data-validator-tool", 'warn');
            return;
          }
          $this->install_OSF_DataValidatorTool($pkgVersion);
          $this->config_OSF_DataValidatorTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-data-validator-tool", 'warn');
            return;
          }
          $this->upgrade_OSF_DataValidatorTool($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_DataValidatorTool($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-data-validator-tool", 'warn');
            return;
          }
          $this->config_OSF_DataValidatorTool($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Data Validator Tool
     */
    private function install_OSF_DataValidatorTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";
      $tmpPath = "/tmp/osf/dvt";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Data-Validator-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Data-Validator-Tool-{$pkgVersion}/StructuredDynamics/osf/validator/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/dvt", 755);
      $this->ln("{$installPath}/dvt", "/usr/bin/dvt");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF Data Validator Tool
     */
    private function upgrade_OSF_DataValidatorTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";
      $bckPath = "/tmp/osf/dvt-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_DataValidatorTool($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/dvt.ini", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Data Validator Tool
     */
    private function uninstall_OSF_DataValidatorTool()
    {
      // Get package info
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
      $this->rm("/usr/bin/dvt");
    }

    /**
     * Configure OSF Data Validator Tool
     */
    private function config_OSF_DataValidatorTool()
    {
      // Get package info
      $configPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // OSF Web Service paths
      $this->setIni("OSF-WS-PHP-API", "folder", "\"{$this->osf_web_services_folder}\"",
        "{$configPath}/dvt.ini");
      $this->setIni("osf", "network", "\"http://{$this->osf_web_services_domain}/ws/\"",
        "{$configPath}/dvt.ini");
      // OSF Web Service credentials
      $this->setIni("credentials", "application-id", "\"{$this->application_id}\"",
        "{$configPath}/dvt.ini");
      $this->setIni("credentials", "api-key", "\"{$this->api_key}\"",
        "{$configPath}/dvt.ini");
      $this->setIni("credentials", "user", "\"http://{$this->osf_web_services_domain}/wsf/users/admin\"",
        "{$configPath}/dvt.ini");
    }

    /**
     * Switch for OSF Permissions Management Tool
     */
    public function switch_OSF_PermissionsManagementTool($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Permissions Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->permissions_management_tool_version;
          break;
      }
      $installPath = "{$this->permissions_management_tool_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-permissions-management-tool", 'warn');
            return;
          }
          $this->install_OSF_PermissionsManagementTool($pkgVersion);
          $this->config_OSF_PermissionsManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-permissions-management-tool", 'warn');
            return;
          }
          $this->upgrade_OSF_PermissionsManagementTool($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_PermissionsManagementTool($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-permissions-management-tool", 'warn');
            return;
          }
          $this->config_OSF_PermissionsManagementTool($pkgVersion);
          break;
        case 'load':
          $this->h2("Loading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-permissions-management-tool", 'warn');
            return;
          }
          $this->load_OSF_PermissionsManagementTool($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Permissions Management Tool
     */
    private function install_OSF_PermissionsManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->permissions_management_tool_folder}";
      $tmpPath = "/tmp/osf/pmt";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Permissions-Management-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Permissions-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/pmt", 755);
      $this->ln("{$installPath}/pmt", "/usr/bin/pmt");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF Permissions Management Tool
     */
    private function upgrade_OSF_PermissionsManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->permissions_management_tool_folder}";
      $bckPath = "/tmp/osf/pmt-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_PermissionsManagementTool($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/pmt.ini", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Permissions Management Tool
     */
    private function uninstall_OSF_PermissionsManagementTool()
    {
      // Get package info
      $installPath = "{$this->permissions_management_tool_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
      $this->rm("/usr/bin/pmt");
    }

    /**
     * Configure OSF Permissions Management Tool
     */
    private function config_OSF_PermissionsManagementTool()
    {
      // Get package info
      $configPath = "{$this->permissions_management_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // OSF Web Service paths
      $this->setIni("config", "osfWebServicesFolder", "\"{$this->osf_web_services_folder}\"",
        "{$configPath}/pmt.ini");
      $this->setIni("config", "osfWebServicesEndpointsUrl", "\"http://{$this->osf_web_services_domain}/ws/\"",
        "{$configPath}/pmt.ini");
      // OSF Web Service credentials
      $this->setIni("credentials", "application-id", "\"{$this->application_id}\"",
        "{$configPath}/pmt.ini");
      $this->setIni("credentials", "api-key", "\"{$this->api_key}\"",
        "{$configPath}/pmt.ini");
      $this->setIni("credentials", "user", "\"http://{$this->osf_web_services_domain}/wsf/users/admin\"",
        "{$configPath}/pmt.ini");
    }

    /**
     * Load OSF Permissions Management Tool
     */
    private function load_OSF_PermissionsManagementTool()
    {
      // Get package info
      $dataPath = "{$this->data_folder}";
      $cwdPath = rtrim($this->currentWorkingDirectory, '/');

      // Install
      // OSF PMT administrative groups
      $this->span("Creating the Drupal administrators group...", 'info');
      $this->exec("pmt --create-group=\"http://{$this->drupal_domain}/role/3/administrator\" --app-id=\"{$this->application_id}\"");
      // OSF PMT administrative users
      $this->span("Creating the Drupal administrator user...", 'info');
      $this->exec("pmt --register-user=\"http://{$this->drupal_domain}/user/1\" --register-user-group=\"http://{$this->drupal_domain}/role/3/administrator\"");
      // OSF PMT permissions for core datasets
      $this->span("Creating the permissions for the core datasets...", 'info');
      $this->exec("pmt --create-access --access-dataset=\"http://{$this->osf_web_services_domain}/wsf/\" --access-group=\"http://{$this->drupal_domain}/role/3/administrator\" --access-perm-create=\"true\" --access-perm-read=\"true\" --access-perm-update=\"true\" --access-perm-delete=\"true\" --access-all-ws");
      $this->exec("pmt --create-access --access-dataset=\"http://{$this->osf_web_services_domain}/wsf/datasets/\" --access-group=\"http://{$this->drupal_domain}/role/3/administrator\" --access-perm-create=\"true\" --access-perm-read=\"true\" --access-perm-update=\"true\" --access-perm-delete=\"true\" --access-all-ws");
      $this->exec("pmt --create-access --access-dataset=\"http://{$this->osf_web_services_domain}/wsf/ontologies/\" --access-group=\"http://{$this->drupal_domain}/role/3/administrator\" --access-perm-create=\"true\" --access-perm-read=\"true\" --access-perm-update=\"true\" --access-perm-delete=\"true\" --access-all-ws");
      // OSF PMT permissions for loaded ontologies
      $this->cp("{$cwdPath}/resources/osf-web-services/ontologies.lst", "{$dataPath}/ontologies/");
      $this->sed("file://localhost/data", "file://localhost/".trim($dataPath, '/')."/",
        "{$dataPath}/ontologies//ontologies.lst", "g");
      $loadedOntologies = explode(' ', file_get_contents("{$dataPath}/ontologies/ontologies.lst"));
      foreach($loadedOntologies as $loadedOntology) {
        $this->exec("pmt --create-access --access-dataset=\"{$loadedOntology}\" --access-group=\"http://{$this->drupal_domain}/role/3/administrator\" --access-perm-create=\"true\" --access-perm-read=\"true\" --access-perm-update=\"true\" --access-perm-delete=\"true\" --access-all-ws");
      }
    }

    /**
     * Switch for Datasets Management Tool
     */
    public function switch_OSF_DatasetsManagementTool($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Datasets Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->datasets_management_tool_version;
          break;
      }
      $installPath = "{$this->datasets_management_tool_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-datasets-management-tool", 'warn');
            return;
          }
          $this->install_OSF_DatasetsManagementTool($pkgVersion);
          $this->config_OSF_DatasetsManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-datasets-management-tool", 'warn');
            return;
          }
          $this->upgrade_OSF_DatasetsManagementTool($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_DatasetsManagementTool($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-datasets-management-tool", 'warn');
            return;
          }
          $this->config_OSF_DatasetsManagementTool($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Datasets Management Tool
     */
    private function install_OSF_DatasetsManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->datasets_management_tool_folder}";
      $dataPath = "{$this->data_folder}";
      $tmpPath = "/tmp/osf/dmt";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Datasets-Management-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Datasets-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/dmt", 755);
      $this->ln("{$installPath}/dmt", "/usr/bin/dmt");
      $this->mkdir("{$dataPath}/datasets/data/");
      $this->mkdir("{$dataPath}/datasets/datasetIndexes/");
      $this->mkdir("{$dataPath}/datasets/missing/");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF Datasets Management Tool
     */
    private function upgrade_OSF_DatasetsManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->datasets_management_tool_folder}";
      $bckPath = "/tmp/osf/dmt-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_DatasetsManagementTool($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/dmt.ini", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Datasets Management Tool
     */
    private function uninstall_OSF_DatasetsManagementTool()
    {
      // Get package info
      $installPath = "{$this->datasets_management_tool_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
      $this->rm("/usr/bin/dmt");
    }

    /**
     * Configure OSF Datasets Management Tool
     */
    private function config_OSF_DatasetsManagementTool()
    {
      // Get package info
      $configPath = "{$this->datasets_management_tool_folder}";
      $dataPath = "{$this->data_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // OSF Web Service paths
      $this->setIni("config", "osfWebServicesFolder", "\"{$this->osf_web_services_folder}\"",
        "{$configPath}/dmt.ini");
      $this->setIni("config", "indexesFolder", "\"{$dataPath}/datasets/datasetIndexes/\"",
        "{$configPath}/dmt.ini");
      $this->setIni("config", "ontologiesStructureFiles", "\"{$dataPath}/ontologies/structure/\"",
        "{$configPath}/dmt.ini");
      $this->setIni("config", "missingVocabulary", "\"{$dataPath}/datasets/missing/\"",
        "{$configPath}/dmt.ini");
      // OSF Web Service credentials
      $this->setIni("credentials", "application-id", "\"{$this->application_id}\"",
        "{$configPath}/dmt.ini");
      $this->setIni("credentials", "api-key", "\"{$this->api_key}\"",
        "{$configPath}/dmt.ini");
      $this->setIni("credentials", "user", "\"http://{$this->osf_web_services_domain}/wsf/users/admin\"",
        "{$configPath}/dmt.ini");
    }

    /**
     * Switch for OSF Ontologies Management Tool
     */
    public function switch_OSF_OntologiesManagementTool($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "OSF Ontologies Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->ontologies_management_tool_version;
          break;
      }
      $installPath = "{$this->ontologies_management_tool_folder}";

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h2("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-osf-ontologies-management-tool", 'warn');
            return;
          }
          $this->install_OSF_OntologiesManagementTool($pkgVersion);
          $this->config_OSF_OntologiesManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-ontologies-management-tool", 'warn');
            return;
          }
          $this->upgrade_OSF_OntologiesManagementTool($pkgVersion);
          break;
        case 'uninstall':
          $this->h2("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Nothing to do.", 'warn');
            return;
          }
          $this->uninstall_OSF_OntologiesManagementTool($pkgVersion);
          break;
        case 'configure':
          $this->h2("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-ontologies-management-tool", 'warn');
            return;
          }
          $this->config_OSF_OntologiesManagementTool($pkgVersion);
          break;
        case 'load':
          $this->h2("Loading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-osf-ontologies-management-tool", 'warn');
            return;
          }
          $this->load_OSF_OntologiesManagementTool($pkgVersion);
          break;
        default:
          $this->h2("{$pkgName} {$pkgVersion}");
          $this->span("Wrong operation. Nothing to do.", 'warn');
          return;
          break;
      }
    }

    /**
     * Install OSF Ontologies Management Tool
     */
    private function install_OSF_OntologiesManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->ontologies_management_tool_folder}";
      $dataPath = "{$this->data_folder}";
      $tmpPath = "/tmp/osf/omt";

      // Download
      $this->span("Downloading...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Ontologies-Management-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->span("Installing...", 'info');
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Ontologies-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/omt", 755);
      $this->ln("{$installPath}/omt", "/usr/bin/omt");
      $this->mkdir("{$dataPath}/ontologies/files/");
      $this->mkdir("{$dataPath}/ontologies/structure/");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
     * Upgrade OSF Ontologies Management Tool
     */
    private function upgrade_OSF_OntologiesManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = "{$this->ontologies_management_tool_folder}";
      $bckPath = "/tmp/osf/omt-" . date('Y-m-d_H-i-s');

      // Backup
      $this->span("Making backup...", 'info');
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->install_OSF_OntologiesManagementTool($pkgVersion);

      // Restore
      $this->span("Restoring backup...", 'info');
      $this->mv("{$bckPath}/omt.ini", "{$installPath}/");

      // Cleanup
      $this->span("Cleaning backup...", 'info');
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
     * Uninstall OSF Ontologies Management Tool
     */
    private function uninstall_OSF_OntologiesManagementTool()
    {
      // Get package info
      $installPath = "{$this->ontologies_management_tool_folder}";

      // Uninstall
      $this->span("Uninstalling...", 'info');
      $this->rm("{$installPath}/", TRUE);
      $this->rm("/usr/bin/omt");
    }

    /**
     * Configure OSF Ontologies Management Tool
     */
    private function config_OSF_OntologiesManagementTool()
    {
      // Get package info
      $configPath = "{$this->ontologies_management_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // OSF Web Service paths
      $this->setIni("config", "osfWebServicesFolder", "\"{$this->osf_web_services_folder}\"",
        "{$configPath}/omt.ini");
      // OSF Web Service credentials
      $this->setIni("credentials", "application-id", "\"{$this->application_id}\"",
        "{$configPath}/omt.ini");
      $this->setIni("credentials", "api-key", "\"{$this->api_key}\"",
        "{$configPath}/omt.ini");
      $this->setIni("credentials", "user", "\"http://{$this->osf_web_services_domain}/wsf/users/admin\"",
        "{$configPath}/omt.ini");
      $this->setIni("credentials", "group", "\"http://{$this->osf_web_services_domain}/wsf/groups/administrators\"",
        "{$configPath}/omt.ini");
    }

    /**
     * Load OSF Ontologies Management Tool
     */
    private function load_OSF_OntologiesManagementTool()
    {
      // Get package info
      $dataPath = "{$this->data_folder}";
      $cwdPath = rtrim($this->currentWorkingDirectory, '/');
      $tmpPath = "/tmp/osf/omt";

      // Download
      $this->span("Downloading the core OSF ontologies...", 'info');
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/aggr/aggr.owl", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/iron/iron.owl", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/owl/owl.rdf", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdf.xml", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdfs.xml", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/sco/sco.owl", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wgs84/wgs84.owl", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wsf/wsf.owl", "{$tmpPath}/");
      $this->wget("https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/drupal/drupal.owl", "{$tmpPath}/");

      // Install
      $this->span("Installing the core OSF ontologies...", 'info');
      $this->cp("{$tmpPath}/.", "{$dataPath}/ontologies/files/", TRUE);
      $this->cp("{$cwdPath}/resources/osf-web-services/classHierarchySerialized.srz", "{$dataPath}/ontologies/structure/");
      $this->cp("{$cwdPath}/resources/osf-web-services/propertyHierarchySerialized.srz", "{$dataPath}/ontologies/structure/");
      $this->cp("{$cwdPath}/resources/osf-web-services/ontologies.lst", "{$dataPath}/ontologies/");
      $this->sed("file://localhost/data", "file://localhost/".trim($dataPath, '/')."/",
        "{$dataPath}/ontologies//ontologies.lst", "g");
      $this->span("Loading the core OSF ontologies...", 'info');
      $this->exec("omt --load-advanced-index=\"true\" --load-all --load-list=\"{$dataPath}/ontologies/ontologies.lst\" --osf-web-services=\"http://{$this->osf_web_services_domain}/ws/\"");
      $this->span("Creating underlying ontological structures...", 'info');
      $this->exec("omt --generate-structures=\"{$dataPath}/ontologies/structure/\" --osf-web-services=\"http://{$this->osf_web_services_domain}/ws/\"");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);
    }

    protected function commit($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' "EXEC=exec(\'checkpoint\')"', $output, $return);

      $this->log($output);

      if($return > 0) {
        return(FALSE);
      }

      return(TRUE);
    }

    protected function change_password($password)
    {
      exec('/usr/bin/isql-v 1111 dba dba "EXEC=user_change_password(\'dav\', \'dav\', \''.$password.'\')"', $output, $return);

      $this->log($output);

      if($return > 0) {
        return(FALSE);
      }

      exec('/usr/bin/isql-v 1111 dba dba "EXEC=user_change_password(\'dba\', \'dba\', \''.$password.'\')"', $output, $return);

      if($return > 0) {
        return(FALSE);
      }

      return(TRUE);  
    }

    protected function init_osf($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' /tmp/init_osf.sql', $output, $return);

      $this->log($output);

      unlink('/tmp/init_osf.sql');

      if($return > 0) {
        return(FALSE);
      }

      return(TRUE);
    }

    protected function update_sparql_roles($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' "EXEC=user_grant_role(\'SPARQL\', \'SPARQL_UPDATE\', 0)"', $output, $return);

      $this->log($output);

      if($return > 0) {
        return(FALSE);
      }

      return(TRUE);
    }
  }

