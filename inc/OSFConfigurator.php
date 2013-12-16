<?php

  include_once('inc/CommandlineTool.php');

  class OSFConfigurator extends CommandlineTool
  {
    /* Parsed intaller.ini configuration file */
    protected $config;
    
    // Configufation options

    /* Determine if the installer is configured */
    public $installer_osf_configured = FALSE;
    public $installer_osf_drupal_configured = FALSE;
    
    /* version of virtuoso to install */
    protected $virtuoso_version = "6.1.6";
    
    /* Version of drupal to install */
    protected $drupal_version = "7.23";

    /* Version of OSF Web Services to install */
    protected $osf_web_services_version = "dev";

    /* Version of OSF-WS-PHP-API to install */
    protected $osf_ws_php_api_version = "dev";

    /* Version of OSF Tests Suites to install */
    protected $osf_tests_suites_version = "dev";

    /* Version of OSF Drupal to install */
    protected $osf_drupal_version = "7.x-2.x";

    /* Folder where the data is managed */
    protected $data_folder = "/data";

    /* Folder where to install OSF Web Services */
    protected $osf_web_services_folder = "/usr/share/osf";

    /* Folder where to install Drupal/OSF Drupal */
    protected $drupal_folder = "/usr/share/drupal";
    
    /* Namespace extension of the OSF Web Services folder. This is where the code resides */
    protected $osf_web_services_ns = "/StructuredDynamics/osf/ws";
    
    /* Folder where to install the Datasets Management Tool */
    protected $datasets_management_tool_folder = "/usr/share/osf-datasets-management-tool";

    /* Folder where to install the Permissions Management Tool */
    protected $permissions_management_tool_folder = "/usr/share/osf-permissions-management-tool";

    /* Folder where to install the Ontologies Management Tool */
    protected $ontologies_management_tool_folder = "/usr/share/osf-ontologies-management-tool";

    /* Version of the Datasets Management Tool to install */
    protected $datasets_management_tool_version = "dev";

    /* Version of the Permissions Management Tool to install */
    protected $permissions_management_tool_version = "dev";

    /* Version of the Ontologies Management Tool to install */
    protected $ontologies_management_tool_version = "dev";

    /* Folder where to put the logging files */
    protected $logging_folder = "/tmp";
    
    /* Domain name where to access the OSF Web Services instance */
    protected $osf_web_services_domain = "localhost";    
    
    protected $application_id = 'administer';
    
    protected $api_key = 'some-key';
    
    function __construct($configFile)
    {
      parent::__construct();
      
      // Load the installer configuration file
      $this->config = parse_ini_file($configFile, TRUE); 
      
      if(!$this->config)
      {
        $this->cecho('An error occured when we tried to parse the '.$configFile.' file. Make sure it is parseable and try again.'."\n", 'RED');  
        exit(1);
      }      
      else
      {
        if(isset($this->config['installer']['osfConfigured']))
        {
          if(strtolower($this->config['installer']['osfConfigured']) === 'false')
          {
            $this->installer_osf_configured = FALSE;
          }
          else
          {
            $this->installer_osf_configured = TRUE;
          }
        }
        
        if(isset($this->config['installer']['osfDrupalConfigured']))
        {
          if(strtolower($this->config['installer']['osfDrupalConfigured']) === 'false')
          {
            $this->installer_osf_drupal_configured = FALSE;
          }
          else
          {
            $this->installer_osf_drupal_configured = TRUE;
          }
        }
        
        if(isset($this->config['data']['virtuoso-version']))
        {
          $this->virtuoso_version = $this->config['data']['virtuoso-version'];
        }
        
        if(isset($this->config['osf-drupal']['drupal-version']))
        {
          $this->drupal_version = $this->config['osf-drupal']['drupal-version'];
        }
        
        if(isset($this->config['osf-web-services']['osf-web-services-version']))
        {
          if(strtolower($this->config['osf-web-services']['osf-web-services-version']) == 'dev')
          {
            $this->osf_web_services_version = 'master';
          }
          else
          {
            $this->osf_web_services_version = $this->config['osf-web-services']['osf-web-services-version'];
          }
        }        
        
        if(isset($this->config['osf-web-services']['osf-ws-php-api-version']))
        {
          if(strtolower($this->config['osf-web-services']['osf-ws-php-api-version']) == 'dev')
          {
            $this->osf_ws_php_api_version = 'master';
          }
          else
          {
            $this->osf_ws_php_api_version = $this->config['osf-web-services']['osf-ws-php-api-version'];
          }
        }        

        if(isset($this->config['osf-web-services']['osf-tests-suites-version']))
        {
          if(strtolower($this->config['osf-web-services']['osf-tests-suites-version']) == 'dev')
          {
            $this->osf_tests_suites_version = 'master';
          }
          else
          {
            $this->osf_tests_suites_version = $this->config['osf-web-services']['osf-tests-suites-version'];
          }
        }        
        
        if(isset($this->config['osf-drupal']['osf-drupal-version']))
        {
          $this->osf_drupal_version = $this->config['osf-drupal']['osf-drupal-version'];
        }
        
        if(isset($this->config['osf-web-services']['osf-web-services-domain']))
        {
          $this->osf_web_services_domain = $this->config['osf-web-services']['osf-web-services-domain'];
        }
        
        if(isset($this->config['data']['data-folder']))
        {
          $this->data_folder = rtrim($this->config['data']['data-folder'], '/');
        }
        
        if(isset($this->config['osf-web-services']['osf-web-services-folder']))
        {
          $this->osf_web_services_folder = rtrim($this->config['osf-web-services']['osf-web-services-folder'], '/');
        }
        
        if(isset($this->config['osf-drupal ']['drupal-folder']))
        {
          $this->drupal_folder = rtrim($this->config['osf-drupal']['drupal-folder'], '/');
        }
        
        if(isset($this->config['tools']['datasets-management-tool-folder']))
        {
          $this->datasets_management_tool_folder = rtrim($this->config['tools']['datasets-management-tool-folder'], '/');
        }
        
        if(isset($this->config['tools']['permissions-management-tool-folder']))
        {
          $this->permissions_management_tool_folder = rtrim($this->config['tools']['permissions-management-tool-folder'], '/');
        }
        
        if(isset($this->config['tools']['ontologies-management-tool-folder']))
        {
          $this->ontologies_management_tool_folder = rtrim($this->config['tools']['ontologies-management-tool-folder'], '/');
        }        
        
        if(isset($this->config['tools']['permissions-management-tool-version']))
        {
          if(strtolower($this->config['tools']['permissions-management-tool-version']) == 'dev')
          {
            $this->permissions_management_tool_version = 'master';
          }
          else
          {
            $this->permissions_management_tool_version = $this->config['tools']['permissions-management-tool-version'];
          }
        }         
        
        if(isset($this->config['tools']['ontologies-management-tool-version']))
        {
          if(strtolower($this->config['tools']['ontologies-management-tool-version']) == 'dev')
          {
            $this->ontologies_management_tool_version = 'master';
          }
          else
          {
            $this->ontologies_management_tool_version = $this->config['tools']['ontologies-management-tool-version'];
          }
        }        
        
        if(isset($this->config['tools']['datasets-management-tool-version']))
        {
          if(strtolower($this->config['tools']['datasets-management-tool-version']) == 'dev')
          {
            $this->datasets_management_tool_version = 'master';
          }
          else
          {
            $this->datasets_management_tool_version = $this->config['tools']['datasets-management-tool-version'];
          }
        }          
                                              
        if(isset($this->config['logging']['logging-folder']))
        {
          $this->logging_folder = rtrim($this->config['logging']['logging-folder'], '/');
        }        
        
        $this->log_file = $this->logging_folder.'/osf-install-'.date('Y-m-d_H:i:s').'.log';        
      }
    }
    
    /**
    * Ask a series of questions to the user to configure the installer software related to OSF Web Services.
    */
    public function configureInstallerOSF()
    {
      $this->cecho("Configure the OSF-Installer Tool\n\n", 'WHITE');
      $this->cecho("Note: if you want to use the default value, you simply have to press Enter on your keyboard.\n\n", 'WHITE');

      $this->cecho("\n\nOSF Web Services related configuration settings:\n", 'CYAN');
      
      $return = $this->getInput("What is the OSF Web Services version you want to install or upgrade? (default: ".($this->osf_web_services_version == 'master' ? 'dev' : $this->osf_web_services_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
        
        $this->osf_web_services_version = $return;
      }          
      
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
      
      $return = $this->getInput("Where do you what to install the OSF Web Services, or where are the OSF Web Services installed? (default: ".$this->osf_web_services_folder.")");
      
      if($return != '')
      {
        $this->osf_web_services_folder = $return;
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
      
      $return = $this->getInput("What is the Virtuoso version you want to install? (default: ".$this->virtuoso_version.")");
      
      if($return != '')
      {
        $this->virtuoso_version = $return;
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
osf-ws-php-api-version = \"".$this->osf_web_services_version."\"
osf-tests-suites-version = \"".$this->osf_web_services_version."\"

[osf-drupal]
drupal-version = \"".$this->drupal_version."\"
osf-drupal-version = \"".$this->osf_drupal_version."\"

[tools]
datasets-management-tool-folder = \"".$this->datasets_management_tool_folder."\"
datasets-management-tool-version = \"".$this->datasets_management_tool_version."\"
ontologies-management-tool-folder = \"".$this->ontologies_management_tool_folder."\"
ontologies-management-tool-version = \"".$this->ontologies_management_tool_version."\"

[data]
virtuoso-version = \"".$this->virtuoso_version."\"
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

      $this->cecho("datasets-management-tool-version: ".($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_version)."\n", 'WHITE');
      $this->cecho("datasets-management-tool-folder: ".$this->datasets_management_tool_folder."\n", 'WHITE');
      $this->cecho("ontologies-management-tool-version: ".($this->ontologies_management_tool_version == 'master' ? 'dev' : $this->ontologies_management_tool_version)."\n", 'WHITE');
      $this->cecho("ontologies-management-tool-folder: ".$this->ontologies_management_tool_folder."\n", 'WHITE');
      
      $this->cecho("\n\nData related configuration settings:\n", 'CYAN');

      $this->cecho("data-folder: ".$this->data_folder."\n", 'WHITE');
      $this->cecho("virtuoso-version: ".$this->virtuoso_version."\n", 'WHITE');

      $this->cecho("\n\nLogging related configuration settings:\n", 'CYAN');

      $this->cecho("logging-folder: ".$this->logging_folder."\n\n", 'WHITE');
    }
    
    /**
    * Upgrade the OSF PHPUNIT Tests Suites
    */
    public function upgradeOSFTestsSuites()
    {
      $this->cecho("Upgrading tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/osftestssuites-upgrade/');
      
      $this->chdir('/tmp/osftestssuites-upgrade/');
      
      $this->wget('https://github.com/structureddynamics/OSF-Tests-Suites/archive/'.$this->osf_tests_suites_version.'.zip');
      
      $this->exec('unzip '.$this->osf_tests_suites_version.'.zip');
      
      $this->chdir('/tmp/osftestssuites-upgrade/OSF-Web-Services-Tests-Suites-'.$this->osf_tests_suites_version.'/StructuredDynamics/osf/');

      $this->exec('rm -rf '.$this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('cp -af tests '.$this->osf_web_services_folder.'/StructuredDynamics/osf/');
                  
      $this->cecho("Configure the tests suites...\n", 'WHITE');

      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->osf_web_services_folder.'/StructuredDynamics/osf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>osfInstanceFolder = \"/usr/share/osf/\";>$this-\>osfInstanceFolder = \"'.$this->osf_web_services_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->osf_web_services_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->osf_web_services_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/osftestssuites-install/');
    }         
    
    public function runOSFTestsSuites($installationFolder = '')
    {
      if($installationFolder == '')
      {
        $installationFolder = $this->osf_web_services_folder;
      }
      
      $this->chdir($installationFolder.'/StructuredDynamics/osf/tests/');
      
      passthru('phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml');
      
      $this->chdir($this->currentWorkingDirectory);      
    }    
  }
?>
