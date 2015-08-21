<?php

  use \StructuredDynamics\osf\php\api\framework\ServerIDQuery;

  include_once('OSFConfigurator.php');

  abstract class OSFInstaller extends OSFConfigurator
  {
    protected $dbaPassword = '';
    
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
    abstract public function installMySQL();
    
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
      $this->cecho("You are about to install the Open Semantic Framework.\n", 'WHITE');
      $this->cecho("This installation process installs all the software components that are part of the OSF stack. It will take 10 minutes of your time, but the process will go on for a few hours because of the many pieces of software that get compiled.\n\n", 'WHITE');
      $this->cecho("The log of this installation is available here: ".$this->log_file."\n", 'WHITE');
      $this->cecho("\n\nCopyright 2008-15. Structured Dynamics LLC. All rights reserved.\n\n", 'WHITE');
      
      $this->cecho("\n\n");
      $this->cecho("---------------------------------\n", 'WHITE');
      $this->cecho(" General Settings Initialization \n", 'WHITE'); 
      $this->cecho("---------------------------------\n", 'WHITE'); 
      $this->cecho("\n\n");

      $this->cecho("\n\n");
      $this->cecho("------------------------\n", 'WHITE');
      $this->cecho(" Installing prerequisites \n", 'WHITE');
      $this->cecho("------------------------\n", 'WHITE');
      $this->cecho("\n\n");

      $yes = $this->isYes($this->getInput("We recommend that you upgrade all software on the server. Would you like to do this right now? (yes/no)"));             
      
      if($yes)
      {
        $this->cecho("Updating the package registry...\n", 'WHITE');
        $this->exec('apt-get -y update');
        
        $this->cecho("Upgrading the server...\n", 'WHITE');
        $this->exec('apt-get -y upgrade');        
      }
      
      $this->cecho("Installing required general packages...\n", 'WHITE');
      $this->exec('apt-get -y install curl gcc libssl-dev openssl gawk vim default-jdk ftp-upload');        

      // Dependency chain:
      // PHP5 depends on MySQL
      // Virtuoso depends on PHP5

      $this->installPhp5();
      $this->installApache2();
      $this->installVirtuoso();
      $this->installSolr();
      $this->installMemcached();

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

      $this->cecho("Now that the OSF instance is installed, you can install OSF for Drupal on the same server using this command:\n\n", 'CYAN');
      $this->cecho("    ./osf-installer --install-osf-drupal\n\n", 'CYAN');
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
      $this->exec('phpunit --configuration phpunit.xml --filter \'StructuredDynamics\\osf\\tests\\ws\\crud\\read\\CrudReadTest::testLanguageEnglishSpecified\'');

      $this->cecho("Restarting Virtuoso...\n", 'WHITE');
      $this->exec('/etc/init.d/virtuoso stop');
      sleep(20);
      $this->exec('/etc/init.d/virtuoso start');

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
      $this->chown("{$installPath}/", "www-data");
      $this->chgrp("{$installPath}/", "www-data");
      $this->chmod("{$installPath}/", "755");
      $this->mkdir("{$this->data_folder}/osf-web-services/tmp/");
      $this->mkdir("{$this->data_folder}/osf-web-services/configs/");
      $this->chown("{$this->data_folder}/osf-web-services/", "www-data");
      $this->chgrp("{$this->data_folder}/osf-web-services/", "www-data");
      $this->chmod("{$this->data_folder}/osf-web-services/", "500");
      $this->chmod("{$this->data_folder}/osf-web-services/tmp/", "700");

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
      $configPath = "{$this->data_folder}/osf-web-services/configs";

      // Configure
      $this->span("Configuring...", 'info');
      $this->cp("{$installPath}/keys.ini", "{$configPath}/keys.ini");
      $this->cp("{$installPath}/osf.ini", "{$configPath}/osf.ini");
      // OSF Web Service scripts
      //$this->sed("\$sidDirectory = \".*\";", "\$sidDirectory = \"/osf-web-services/tmp/\";", "{$installPath}/index.php");
      $this->sed("public static \$osf_ini = \".*\";", "public static \$osf_ini = \"{$configPath}/\";", "{$installPath}/framework/WebService.php");
      $this->sed("public static \$keys_ini = \".*\";", "public static \$keys_ini = \"{$configPath}/\";", "{$installPath}/framework/WebService.php");
      // OSF Web Service credentials
      $this->append("\n{$this->application_id} = \"{$this->api_key}\"", "{$configPath}/keys.ini");
      // OSF Web Service paths
      $this->SetIni("network", "wsf_base_url", "\"http://{$this->osf_web_services_domain}\"", "{$configPath}/osf.ini");
      $this->SetIni("network", "wsf_base_path", "\"http://{$this->osf_web_services_domain}/wsf/\"", "{$configPath}/osf.ini");
      // OSF Tools paths
      $this->SetIni("datasets", "wsf_graph", "\"{$installPath}/\"", "{$configPath}/osf.ini");
      $this->SetIni("datasets", "dtd_base", "\"http://{$this->osf_web_services_domain}/ws/dtd/\"", "{$configPath}/osf.ini");
      $this->SetIni("ontologies", "ontologies_files_folder", "\"{$this->data_folder}/ontologies/files/\"", "{$configPath}/osf.ini");
      $this->SetIni("ontologies", "ontological_structure_folder", "\"{$this->data_folder}/ontologies/structure/\"", "{$configPath}/osf.ini");
      // SPARQL dependency
      $this->SetIni("triplestore", "channel", "\"{$this->sparql_channel}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "dsn", "\"{$this->sparql_dsn}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "host", "\"{$this->sparql_host}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "port", "\"{$this->sparql_port}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "sparql", "\"{$this->sparql_url}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "sparql-graph", "\"{$this->sparql_graph_url}\"", "{$configPath}/osf.ini");
      if ($this->sparql_channel == 'http') {
        $this->SetIni("triplestore", "sparql-insert", "\"insert\"", "{$configPath}/osf.ini");
      }
      $this->SetIni("triplestore", "username", "\"{$this->sparql_username}\"", "{$configPath}/osf.ini");
      $this->SetIni("triplestore", "password", "\"{$this->sparql_password}\"", "{$configPath}/osf.ini");
      // Keycache dependency
      $this->SetIni("memcached", "memcached_enabled", "\"{$this->keycache_enabled}\"", "{$configPath}/osf.ini");
      $this->SetIni("memcached", "memcached_host", "\"{$this->keycache_host}\"", "{$configPath}/osf.ini");
      $this->SetIni("memcached", "memcached_port", "\"{$this->keycache_port}\"", "{$configPath}/osf.ini");
      // Solr dependency
      $this->SetIni("solr", "solr_host", "\"{$this->solr_host}\"", "{$configPath}/osf.ini");
      $this->SetIni("solr", "solr_port", "\"{$this->solr_port}\"", "{$configPath}/osf.ini");
      $this->SetIni("solr", "solr_core", "\"{$this->solr_core}\"", "{$configPath}/osf.ini");
      $this->SetIni("solr", "fields_index_folder", "\"{$this->data_folder}/osf-web-services/tmp/\"", "{$configPath}/osf.ini");
      // OWL dependency
      $this->SetIni("scones", "endpoint", "\"http://{$this->scones_host}:{$this->scones_port}/{$this->scones_url}/\"", "{$configPath}/osf.ini");
      // Other
      $this->SetIni("geo", "geoenabled", "\"true\"", "{$configPath}/osf.ini");
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
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("REPLACEME", "{$this->osf_web_services_folder}/StructuredDynamics/osf", "{$installPath}/phpunit.xml");
      $this->sed("\$this-\>osfInstanceFolder = \".*\";", "\$this-\>osfInstanceFolder = \"{$this->osf_web_services_folder}/{$this->osf_web_services_ns}/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>endpointUrl = \".*/ws/\";", "\$this-\>endpointUrl = \"http://{$this->osf_web_services_domain}/ws/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>endpointUri = \".*/wsf/ws/\";", "\$this-\>endpointUri = \"http://{$this->osf_web_services_domain}/wsf/ws/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>userID = '.*/wsf/users/tests-suites';", "\$this-\>userID = 'http://{$this->osf_web_services_domain}/wsf/users/tests-suites';", "{$installPath}/Config.php");
      $this->sed("\$this-\>adminGroup = '.*/wsf/groups/administrators';", "\$this-\>adminGroup = 'http://{$this->osf_web_services_domain}/wsf/groups/administrators';", "{$installPath}/Config.php");
      $this->sed("\$this-\>testGroup = \".*/wsf/groups/unittests\";", "\$this-\>testGroup = \"http://{$this->osf_web_services_domain}/wsf/groups/unittests\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>testUser = \".*/wsf/users/unittests\";", "\$this-\>testUser = \"http://{$this->osf_web_services_domain}/wsf/users/unittests\";", "{$installPath}/Config.php");
      // Web Service credentials
      $this->sed("\$this-\>applicationID = '.*';", "\$this-\>applicationID = '{$this->application_id}';", "{$installPath}/Config.php");
      $this->sed("\$this-\>apiKey = '.*';", "\$this-\>apiKey = '{$this->api_key}';", "{$installPath}/Config.php");
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
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-data-validator-tool", 'warn');
            return;
          }
          $this->install_OSF_DataValidatorTool($pkgVersion);
          $this->config_OSF_DataValidatorTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-data-validator-tool", 'warn');
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
            $this->span("The package is not installed. Consider installing it with the option: --install-data-validator-tool", 'warn');
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
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("folder = \".*\"", "folder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/dvt.ini");
      $this->sed("network = \".*\"", "network = \"http://{$this->osf_web_services_domain}/ws/\"", "{$installPath}/dvt.ini");
      // Web Service credentials
      $this->sed("application-id = \".*\"", "application-id = \"{$this->application_id}\"", "{$installPath}/dvt.ini");
      $this->sed("api-key = \".*\"", "api-key = \"{$this->api_key}\"", "{$installPath}/dvt.ini");
      $this->sed("user = \".*\"", "user = \"http://{$this->osf_web_services_domain}/wsf/users/admin\"", "{$installPath}/dvt.ini");
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
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-permissions-management-tool", 'warn');
            return;
          }
          $this->install_OSF_PermissionsManagementTool($pkgVersion);
          $this->config_OSF_PermissionsManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-permissions-management-tool", 'warn');
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
            $this->span("The package is not installed. Consider installing it with the option: --install-permissions-management-tool", 'warn');
            return;
          }
          $this->config_OSF_PermissionsManagementTool($pkgVersion);
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
      $installPath = "{$this->permissions_management_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/pmt.ini");
      $this->sed("osfWebServicesEndpointsUrl = \".*\"", "osfWebServicesEndpointsUrl = \"http://{$this->osf_web_services_domain}/ws/\"", "{$installPath}/pmt.ini");
      // Web Service credentials
      $this->sed("application-id = \".*\"", "application-id = \"{$this->application_id}\"", "{$installPath}/pmt.ini");
      $this->sed("api-key = \".*\"", "api-key = \"{$this->api_key}\"", "{$installPath}/pmt.ini");
      $this->sed("user = \".*\"", "user = \"http://{$this->osf_web_services_domain}/wsf/users/admin\"", "{$installPath}/pmt.ini");
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
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-datasets-management-tool", 'warn');
            return;
          }
          $this->install_OSF_DatasetsManagementTool($pkgVersion);
          $this->config_OSF_DatasetsManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-datasets-management-tool", 'warn');
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
            $this->span("The package is not installed. Consider installing it with the option: --install-datasets-management-tool", 'warn');
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
      $installPath = "{$this->datasets_management_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/dmt.ini");
      // Datasets paths
      $this->sed("indexesFolder = \".*\"", "indexesFolder = \"{$installPath}/datasetIndexes/\"", "{$installPath}/dmt.ini");
      $this->sed("ontologiesStructureFiles = \".*\"", "ontologiesStructureFiles = \"{$this->data_folder}/ontologies/structure/\"", "{$installPath}/dmt.ini");
      $this->sed("missingVocabulary = \".*\"", "missingVocabulary = \"{$installPath}/missing/\"", "{$installPath}/dmt.ini");
      // Web Service credentials
      $this->sed("application-id = \".*\"", "application-id = \"{$this->application_id}\"", "{$installPath}/dmt.ini");
      $this->sed("api-key = \".*\"", "api-key = \"{$this->api_key}\"", "{$installPath}/dmt.ini");
      $this->sed("user = \".*\"", "user = \"http://{$this->osf_web_services_domain}/wsf/users/admin\"", "{$installPath}/dmt.ini");
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
            $this->span("The package is already installed. Consider upgrading it with the option: --upgrade-ontologies-management-tool", 'warn');
            return;
          }
          $this->install_OSF_OntologiesManagementTool($pkgVersion);
          $this->config_OSF_OntologiesManagementTool();
          break;
        case 'upgrade':
          $this->h2("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-ontologies-management-tool", 'warn');
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
            $this->span("The package is not installed. Consider installing it with the option: --install-ontologies-management-tool", 'warn');
            return;
          }
          $this->config_OSF_OntologiesManagementTool($pkgVersion);
          break;
        case 'load':
          $this->h2("Loading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->span("The package is not installed. Consider installing it with the option: --install-ontologies-management-tool", 'warn');
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
      $this->mkdir("{$this->data_folder}/ontologies/files/");
      $this->mkdir("{$this->data_folder}/ontologies/structure/");

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
      $installPath = "{$this->ontologies_management_tool_folder}";

      // Configure
      $this->span("Configuring...", 'info');
      // Web Service paths
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/omt.ini");
      // Web Service credentials
      $this->sed("application-id = \".*\"", "application-id = \"{$this->application_id}\"", "{$installPath}/omt.ini");
      $this->sed("api-key = \".*\"", "api-key = \"{$this->api_key}\"", "{$installPath}/omt.ini");
      $this->sed("user = \".*\"", "user = \"http://{$this->osf_web_services_domain}/wsf/users/admin\"", "{$installPath}/omt.ini");
      $this->sed("group = \".*\"", "group = \"http://{$this->osf_web_services_domain}/wsf/groups/administrators\"", "{$installPath}/omt.ini");
    }

    /**
     * Load OSF Ontologies Management Tool
     */
    private function load_OSF_OntologiesManagementTool()
    {
      // Get package info
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
      $this->cp("{$tmpPath}/.", "{$this->data_folder}/ontologies/files/", TRUE);
      $this->cp("{$cwdPath}/resources/osf-web-services/classHierarchySerialized.srz", "{$this->data_folder}/ontologies/structure/classHierarchySerialized.srz");
      $this->cp("{$cwdPath}/resources/osf-web-services/propertyHierarchySerialized.srz", "{$this->data_folder}/ontologies/structure/propertyHierarchySerialized.srz");
      $this->sed("file://localhost/data", "file://localhost/{$this->data_folder}/", "{$cwdPath}/resources/osf-web-services/ontologies.lst");
      $this->span("Loading the core OSF ontologies...", 'info');
      $this->exec("omt --load-advanced-index=\"true\" --load-all --load-list=\"{$cwdPath}/resources/osf-web-services/ontologies.lst\" --osf-web-services=\"http://{$this->osf_web_services_domain}/ws/\"");
      $this->span("Creating underlying ontological structures...", 'info');
      $this->exec("omt --generate-structures=\"{$this->data_folder}/ontologies/structure/\" --osf-web-services=\"http://{$this->osf_web_services_domain}/ws/\"");

      // Cleanup
      $this->span("Cleaning...", 'info');
      $this->rm("{$tmpPath}/", TRUE);  
    }

    protected function commit($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' "EXEC=exec(\'checkpoint\')"', $output, $return);
      
      $this->log($output);      
      
      if($return > 0)
      {
        return(FALSE);
      }
      
      return(TRUE);
    }
    
    protected function change_password($password)
    {
      exec('/usr/bin/isql-v 1111 dba dba "EXEC=user_change_password(\'dav\', \'dav\', \''.$password.'\')"', $output, $return);
      
      $this->log($output);      
      
      if($return > 0)
      {
        return(FALSE);
      }

      exec('/usr/bin/isql-v 1111 dba dba "EXEC=user_change_password(\'dba\', \'dba\', \''.$password.'\')"', $output, $return);

      if($return > 0)
      {
        return(FALSE);
      }
      
      return(TRUE);      
    }
    
    protected function init_osf($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' /tmp/init_osf.sql', $output, $return);
      
      $this->log($output);      

      unlink('/tmp/init_osf.sql');
      
      if($return > 0)
      {
        return(FALSE);
      }
      
      return(TRUE);
    }
    
    protected function update_sparql_roles($password)
    {
      exec('/usr/bin/isql-v 1111 dba '.$password.' "EXEC=user_grant_role(\'SPARQL\', \'SPARQL_UPDATE\', 0)"', $output, $return);
      
      $this->log($output);      

      if($return > 0)
      {
        return(FALSE);
      }
      
      return(TRUE);
    }    
  }
?>
