<?php

  include_once('inc/CommandlineTool.php');

  class OSFConfigurator extends CommandlineTool
  {
    /* Parsed intaller.ini configuration file */
    protected $config;

    /* OSF Installation status */
    public $installer_osf_configured = FALSE;

    /* Drupal Installation status */
    public $installer_osf_drupal_configured = FALSE;

    /* OSF Application ID and Key */
    protected $application_id = 'administer';
    protected $api_key = 'some-key';

    /* OSF Storage paths */
    protected $data_folder = "/data";
    protected $logging_folder = "/tmp";

    /* Namespace extension of the OSF Web Services folder. This is where the code resides */
    protected $osf_web_services_ns = "/StructuredDynamics/osf/ws";

    /* OSF WS */
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

    /* Drupal OSF module */
    protected $osf_drupal_version = "7.x-2.x";

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
        if (strtolower($this->config['installer']['osfConfigured']) === 'false') {
          $this->installer_osf_configured = FALSE;
        } else {
          $this->installer_osf_configured = TRUE;
        }
      }

      /**
       *  Drupal Installation status
       */
      if (isset($this->config['installer']['osfDrupalConfigured'])) {
        if (strtolower($this->config['installer']['osfDrupalConfigured']) === 'false') {
          $this->installer_osf_drupal_configured = FALSE;
        } else {
          $this->installer_osf_drupal_configured = TRUE;
        }
      }

      /**
       *  OSF Application ID and Key
       */
      if (isset($this->config['osf-web-services']['application-id'])) {
        $this->application_id = $this->config['osf-web-services']['application-id'];
      }
      if (isset($this->config['osf-web-services']['api-key'])) {
        $this->api_key = $this->config['osf-web-services']['api-key'];
      }

      /**
       *  OSF Storage paths
       */
      if (isset($this->config['data']['data-folder'])) {
        $this->data_folder = rtrim($this->config['data']['data-folder'], '/');
      }
      if (isset($this->config['logging']['logging-folder'])) {
        $this->logging_folder = rtrim($this->config['logging']['logging-folder'], '/');
      }

      /**
       *  OSF OSF WS
       */
      if (isset($this->config['osf-web-services']['osf-web-services-version'])) {
        if (strtolower($this->config['osf-web-services']['osf-web-services-version']) == 'dev') {
          $this->osf_web_services_version = 'master';
        } else {
          $this->osf_web_services_version = $this->config['osf-web-services']['osf-web-services-version'];
        }
      }
      if (isset($this->config['osf-web-services']['osf-web-services-folder'])) {
        $this->osf_web_services_folder = rtrim($this->config['osf-web-services']['osf-web-services-folder'], '/');
      }
      if (isset($this->config['osf-web-services']['osf-web-services-domain'])) {
        $this->osf_web_services_domain = $this->config['osf-web-services']['osf-web-services-domain'];
      }

      /**
       *  OSF WS-PHP-API
       */
      if (isset($this->config['osf-web-services']['osf-ws-php-api-version'])) {
        if (strtolower($this->config['osf-web-services']['osf-ws-php-api-version']) == 'dev') {
          $this->osf_ws_php_api_version = 'master';
        } else {
          $this->osf_ws_php_api_version = $this->config['osf-web-services']['osf-ws-php-api-version'];
        }
      }
      if (isset($this->config['osf-web-services']['osf-ws-php-api-folder'])) {
        $this->osf_ws_php_api_folder = rtrim($this->config['osf-web-services']['osf-ws-php-api-folder'], '/');
      }

      /**
       *  OSF Tests Suites
       */
      if (isset($this->config['osf-web-services']['osf-tests-suites-version'])) {
        if (strtolower($this->config['osf-web-services']['osf-tests-suites-version']) == 'dev') {
          $this->osf_tests_suites_version = 'master';
        } else {
          $this->osf_tests_suites_version = $this->config['osf-web-services']['osf-tests-suites-version'];
        }
      }
      if (isset($this->config['osf-web-services']['osf-tests-suites-folder'])) {
        $this->osf_tests_suites_folder = rtrim($this->config['osf-web-services']['osf-tests-suites-folder'], '/');
      }

      /**
       *  OSF Data Validator Tool
       */
      if (isset($this->config['tools']['data-validator-tool-version'])) {
        if (strtolower($this->config['tools']['data-validator-tool-version']) == 'dev') {
          $this->data_validator_tool_version = 'master';
        } else {
          $this->data_validator_tool_version = $this->config['tools']['data-validator-tool-version'];
        }
      }
      if (isset($this->config['tools']['data-validator-tool-folder'])) {
        $this->data_validator_tool_folder = rtrim($this->config['tools']['data-validator-tool-folder'], '/');
      }

      /**
       *  OSF Permissions Management Tool
       */
      if (isset($this->config['tools']['permissions-management-tool-version'])) {
        if (strtolower($this->config['tools']['permissions-management-tool-version']) == 'dev') {
          $this->permissions_management_tool_version = 'master';
        } else {
          $this->permissions_management_tool_version = $this->config['tools']['permissions-management-tool-version'];
        }
      }
      if (isset($this->config['tools']['permissions-management-tool-folder'])) {
        $this->permissions_management_tool_folder = rtrim($this->config['tools']['permissions-management-tool-folder'], '/');
      }

      /**
       *  OSF Datasets Management Tool
       */
      if (isset($this->config['tools']['datasets-management-tool-version'])) {
        if(strtolower($this->config['tools']['datasets-management-tool-version']) == 'dev') {
          $this->datasets_management_tool_version = 'master';
        } else {
          $this->datasets_management_tool_version = $this->config['tools']['datasets-management-tool-version'];
        }
      }
      if (isset($this->config['tools']['datasets-management-tool-folder'])) {
        $this->datasets_management_tool_folder = rtrim($this->config['tools']['datasets-management-tool-folder'], '/');
      }

      /**
       *  OSF Ontologies Management Tool
       */
      if (isset($this->config['tools']['ontologies-management-tool-version'])) {
        if (strtolower($this->config['tools']['ontologies-management-tool-version']) == 'dev') {
          $this->ontologies_management_tool_version = 'master';
        } else {
          $this->ontologies_management_tool_version = $this->config['tools']['ontologies-management-tool-version'];
        }
      }
      if (isset($this->config['tools']['ontologies-management-tool-folder'])) {
        $this->ontologies_management_tool_folder = rtrim($this->config['tools']['ontologies-management-tool-folder'], '/');
      }

      /**
       *  Drupal framework
       */
      if (isset($this->config['osf-drupal']['drupal-version'])) {
        $this->drupal_version = $this->config['osf-drupal']['drupal-version'];
      }
      if (isset($this->config['osf-drupal ']['drupal-folder'])) {
        $this->drupal_folder = rtrim($this->config['osf-drupal']['drupal-folder'], '/');
      }
      if (isset($this->config['osf-drupal ']['drupal-domain'])) {
        $this->drupal_domain = $this->config['osf-drupal']['drupal-domain'];
      }

      /**
       *  Drupal OSF module
       */
      if (isset($this->config['osf-drupal']['osf-drupal-version'])) {
        $this->osf_drupal_version = $this->config['osf-drupal']['osf-drupal-version'];
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
      $this->header("Configure the OSF-Installer Tool", 'info');
      $this->cecho("Note: if you want to use the default value, you simply have to press Enter on your keyboard.\n\n", 'WHITE');

      $this->cecho("\n\nOSF Web Services related configuration settings:\n", 'CYAN');

      /**
       *  OSF OSF WS
       */
      do {
        $input = $this->getInput("What is the OSF Web Services version you want to install? (default: " . ($this->osf_web_services_version == 'master' ? 'dev' : $this->osf_web_services_version) . ", valid: dev or <version>)");
        if (!empty($input)) {
          if ($this->isVersion($input) == TRUE || $input == 'dev') {
            $this->osf_web_services_version = $input;
            break;
          }
        }
      } while (0);
      do {
        $input = $this->getInput("Where do you want to install the OSF Web Services? (default: {$this->osf_web_services_folder})");
        if (!empty($input)) {
          if ($this->isPath($input) == TRUE) {
            $this->osf_web_services_folder = $input;
            break;
          }
        }
      } while (0);
         
      

      
      $return = $this->getInput("What is the OSF-WS-PHP-API version you want to install or upgrade? (default: ".($this->osf_ws_php_api_version == 'master' ? 'dev' : $this->osf_ws_php_api_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
                
        $this->osf_ws_php_api_version = $return;
      }          
      
      $return = $this->getInput("What is the OSF Tests Suites version you want to install or upgrade? (default: ".($this->osf_tests_suites_version == 'master' ? 'dev' : $this->osf_tests_suites_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
        
        $this->osf_tests_suites_version = $return;
      }          
      

      
      $return = $this->getInput("What is the domain name where the OSF Web Services instance will be accessible (default: ".$this->osf_web_services_domain.")");
      
      if($return != '')
      {
        $this->osf_web_services_domain = $return;
      }    
      
      $this->cecho("\n\nOther tools related configuration settings:\n", 'CYAN');

      $return = $this->getInput("What is the Permissions Management Tool version you want to install or upgrade? (default: ".($this->permissions_management_tool_version == 'master' ? 'dev' : $this->permissions_management_tool_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
                
        $this->permissions_management_tool_version = $return;
      }       
            
      $return = $this->getInput("Where do you what to install the Permissions Management Tool, or where is Permissions Management Tool installed? (default: ".$this->permissions_management_tool_folder.")");
      
      if($return != '')
      {
        $this->permissions_management_tool_folder = $return;
      }       
      
      
      $return = $this->getInput("What is the Datasets Management Tool version you want to install or upgrade? (default: ".($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
                
        $this->datasets_management_tool_version = $return;
      }       
            
      $return = $this->getInput("Where do you what to install the Datasets Management Tool, or where is Datasets Management Tool installed? (default: ".$this->datasets_management_tool_folder.")");
      
      if($return != '')
      {
        $this->datasets_management_tool_folder = $return;
      }       

      $return = $this->getInput("What is the Ontologies Management Tool version you want to install or upgrade? (default: ".($this->ontologies_management_tool_version == 'master' ? 'dev' : $this->ontologies_management_tool_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
                
        $this->ontologies_management_tool_version = $return;
      }       
            
      $return = $this->getInput("Where do you what to install the Ontologies Management Tool, or where is Ontologies Management Tool installed? (default: ".$this->ontologies_management_tool_folder.")");
      
      if($return != '')
      {
        $this->ontologies_management_tool_folder = $return;
      }       
      
      $this->cecho("\n\nData related configuration settings:\n", 'CYAN');

      $return = $this->getInput("Where is located the data folder? (default: ".$this->data_folder.")");
      
      if($return != '')
      {
        $this->data_folder = $return;
      }       

      $this->cecho("\n\nLogging related configuration settings:\n", 'CYAN');
      
      $return = $this->getInput("Where is located the folder where to save the log files? (default: ".$this->logging_folder.")");
      
      if($return != '')
      {
        $this->logging_folder = $return;
      }   
      
      $this->installer_osf_configured = TRUE;   
      
      $this->saveConfigurations(); 
    }
    
    /**
    * Ask a series of questions to the user to configure the installer software related to OSF Drupal.
    */
    public function configureInstallerOSFDrupal()
    {
      $this->cecho("Configure the OSF-Installer Tool\n\n", 'WHITE');
      $this->cecho("Note: if you want to use the default value, you simply have to press Enter on your keyboard.\n\n", 'WHITE');

      $this->cecho("\n\n Drupal/OSF Drupal related configuration settings:\n", 'CYAN');
      
      $return = $this->getInput("What is the Drupal version you want to install? (default: ".$this->drupal_version.")");
      
      if($return != '')
      {
        $this->drupal_version = $return;
      }          
      
      $return = $this->getInput("What is the OSF Drupal version you want to install? (default: ".$this->osf_drupal_version.")");
      
      if($return != '')
      {
        $this->osf_drupal_version = $return;
      }                 
      
      $this->installer_osf_drupal_configured = TRUE;   
      
      $this->saveConfigurations(); 
    }    
    
    private function saveConfigurations()
    {
      $ini = "[installer]
osfConfigured = \"".($this->installer_osf_configured ? 'true' : 'false')."\"
osfDrupalConfigured = \"".($this->installer_osf_drupal_configured ? 'true' : 'false')."\"

[osf-web-services]
osf-web-services-version = \"".$this->osf_web_services_version."\"
osf-web-services-folder = \"".$this->osf_web_services_folder."\"
osf-web-services-domain = \"".$this->osf_web_services_domain."\"
osf-ws-php-api-version = \"".$this->osf_ws_php_api_version."\"
osf-tests-suites-version = \"".$this->osf_tests_suites_version."\"

[osf-drupal]
drupal-version = \"".$this->drupal_version."\"
osf-drupal-version = \"".$this->osf_drupal_version."\"

[tools]
permissions-management-tool-folder = \"".$this->permissions_management_tool_folder."\"
permissions-management-tool-version = \"".$this->permissions_management_tool_version."\"
datasets-management-tool-folder = \"".$this->datasets_management_tool_folder."\"
datasets-management-tool-version = \"".$this->datasets_management_tool_version."\"
ontologies-management-tool-folder = \"".$this->ontologies_management_tool_folder."\"
ontologies-management-tool-version = \"".$this->ontologies_management_tool_version."\"

[data]
data-folder = \"".$this->data_folder."\"

[logging]
logging-folder = \"".$this->logging_folder."\"      
";
      file_put_contents('installer.ini', $ini);

    }
    
    /**
    * List current configuration settings
    */
    public function listConfigurations()
    {
      $this->cecho("\n\nOSF Web Services related configuration settings:\n", 'CYAN');

      $this->cecho("osf-web-services-version: ".($this->osf_web_services_version == 'master' ? 'dev' : $this->osf_web_services_version)."\n", 'WHITE');
      $this->cecho("osf-web-services-folder: ".$this->osf_web_services_folder."\n", 'WHITE');
      $this->cecho("osf-web-services-domain: ".$this->osf_web_services_domain."\n", 'WHITE');
      $this->cecho("osf-ws-php-api-version: ".($this->osf_ws_php_api_version == 'master' ? 'dev' : $this->osf_ws_php_api_version)."\n", 'WHITE');
      $this->cecho("osf-tests-suites-version: ".($this->osf_tests_suites_version == 'master' ? 'dev' : $this->osf_tests_suites_version)."\n", 'WHITE');
      
      $this->cecho("\n\nOSF Drupal related configuration settings:\n", 'CYAN');
            
      $this->cecho("drupal-version: ".$this->drupal_version."\n", 'WHITE');
      $this->cecho("osf-drupal-version: ".$this->osf_drupal_version."\n", 'WHITE');
      
      $this->cecho("\n\nOther tools related configuration settings:\n", 'CYAN');

      $this->cecho("permissions-management-tool-version: ".($this->permissions_management_tool_version == 'master' ? 'dev' : $this->permissions_management_tool_version)."\n", 'WHITE');
      $this->cecho("permissions-management-tool-folder: ".$this->permissions_management_tool_folder."\n", 'WHITE');
      $this->cecho("datasets-management-tool-version: ".($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_version)."\n", 'WHITE');
      $this->cecho("datasets-management-tool-folder: ".$this->datasets_management_tool_folder."\n", 'WHITE');
      $this->cecho("ontologies-management-tool-version: ".($this->ontologies_management_tool_version == 'master' ? 'dev' : $this->ontologies_management_tool_version)."\n", 'WHITE');
      $this->cecho("ontologies-management-tool-folder: ".$this->ontologies_management_tool_folder."\n", 'WHITE');
      
      $this->cecho("\n\nData related configuration settings:\n", 'CYAN');

      $this->cecho("data-folder: ".$this->data_folder."\n", 'WHITE');

      $this->cecho("\n\nLogging related configuration settings:\n", 'CYAN');

      $this->cecho("logging-folder: ".$this->logging_folder."\n\n", 'WHITE');
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
