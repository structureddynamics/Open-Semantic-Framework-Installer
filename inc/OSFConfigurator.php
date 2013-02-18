<?php

  include_once('inc/CommandlineTool.php');

  class OSFConfigurator extends CommandlineTool
  {
    /* Parsed intaller.ini configuration file */
    protected $config;
    
    // Configufation options

    /* Determine if the installer is configured */
    public $installer_configured = FALSE;
    
    /* version of virtuoso to install */
    protected $virtuoso_version = "6.1.6";
    
    /* Version of drupal to install */
    protected $drupal_version = "7.19";

    /* Version of structWSF to install */
    protected $structwsf_version = "dev";

    /* Version of structWSF-PHP-API to install */
    protected $structwsf_php_api_version = "dev";

    /* Version of structWSF Tests Suites to install */
    protected $structwsf_tests_suites_version = "dev";

    /* Version of conStruct to install */
    protected $construct_version = "7.x-1.0";

    /* Folder where the data is managed */
    protected $data_folder = "/data";

    /* Folder where to install structWSF */
    protected $structwsf_folder = "/usr/share/structwsf";
    
    /* Namespace extension of the structwsf folder. This is where the code resides */
    protected $structwsf_ns = "/StructuredDynamics/structwsf/ws";
    
    /* Folder where to install the Datasets Management Tool */
    protected $datasets_management_tool_folder = "/usr/share/datasets-management-tool";

    /* Folder where to install the Ontologies Management Tool */
    protected $ontologies_management_tool_folder = "/usr/share/ontologies-management-tool";

    /* Version of the Datasets Management Tool to install */
    protected $datasets_management_tool_version = "dev";

    /* Version of the Ontologies Management Tool to install */
    protected $ontologies_management_tool_version = "dev";

    /* Folder where to put the logging files */
    protected $logging_folder = "/tmp";
    
    /* Domain name where to access the structWSF instance */
    protected $structwsf_domain = "localhost";    
    
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
        if(isset($this->config['installer']['configured']))
        {
          if(strtolower($this->config['installer']['configured']) === 'false')
          {
            $this->installer_configured = FALSE;
          }
          else
          {
            $this->installer_configured = TRUE;
          }
        }
        
        if(isset($this->config['data']['virtuoso-version']))
        {
          $this->virtuoso_version = $this->config['data']['virtuoso-version'];
        }
        
        if(isset($this->config['construct']['drupal-version']))
        {
          $this->drupal_version = $this->config['construct']['drupal-version'];
        }
        
        if(isset($this->config['structwsf']['structwsf-version']))
        {
          if(strtolower($this->config['structwsf']['structwsf-version']) == 'dev')
          {
            $this->structwsf_version = 'master';
          }
          else
          {
            $this->structwsf_version = $this->config['structwsf']['structwsf-version'];
          }
        }        
        
        if(isset($this->config['structwsf']['structwsf-php-api-version']))
        {
          if(strtolower($this->config['structwsf']['structwsf-php-api-version']) == 'dev')
          {
            $this->structwsf_php_api_version = 'master';
          }
          else
          {
            $this->structwsf_php_api_version = $this->config['structwsf']['structwsf-php-api-version'];
          }
        }        

        if(isset($this->config['structwsf']['structwsf-tests-suites-version']))
        {
          if(strtolower($this->config['structwsf']['structwsf-tests-suites-version']) == 'dev')
          {
            $this->structwsf_tests_suites_version = 'master';
          }
          else
          {
            $this->structwsf_tests_suites_version = $this->config['structwsf']['structwsf-tests-suites-version'];
          }
        }        
        
        if(isset($this->config['construct']['construct-version']))
        {
          $this->construct_version = $this->config['construct']['construct-version'];
        }
        
        if(isset($this->config['structwsf']['structwsf-domain']))
        {
          $this->strucwsf_domain = $this->config['structwsf']['structwsf-domain'];
        }
        
        if(isset($this->config['data']['data-folder']))
        {
          $this->data_folder = rtrim($this->config['data']['data-folder'], '/');
        }
        
        if(isset($this->config['structwsf']['strucwsf-folder']))
        {
          $this->structwsf_folder = rtrim($this->config['structwsf']['strucwsf-folder'], '/');
        }
        
        if(isset($this->config['tools']['datasets-management-tool-folder']))
        {
          $this->datasets_management_tool_folder = rtrim($this->config['tools']['datasets-management-tool-folder'], '/');
        }
        
        if(isset($this->config['tools']['ontologies-management-tool-folder']))
        {
          $this->ontologies_management_tool_folder = rtrim($this->config['tools']['ontologies-management-tool-folder'], '/');
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
    * Ask a series of questions to the user to configure the installer software.
    */
    public function configureInstaller()
    {
      $this->cecho("Configure the OSF-Installer Tool\n\n", 'WHITE');
      $this->cecho("Note: if you want to use the default value, you simply have to press Enter on your keyboard.\n\n", 'WHITE');

      $this->cecho("\n\nstructWSF related configuration settings:\n", 'CYAN');
      
      $return = $this->getInput("What is the structWSF version you want to install or upgrade? (default: ".($this->structwsf_version == 'master' ? 'dev' : $this->structwsf_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
        
        $this->structwsf_version = $return;
      }          
      
      $return = $this->getInput("What is the structWSF-PHP-API version you want to install or upgrade? (default: ".($this->structwsf_php_api_version == 'master' ? 'dev' : $this->structwsf_php_api_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
                
        $this->structwsf_php_api_version = $return;
      }          
      
      $return = $this->getInput("What is the structWSF Tests Suites version you want to install or upgrade? (default: ".($this->structwsf_tests_suites_version == 'master' ? 'dev' : $this->structwsf_tests_suites_version).")");
      
      if($return != '')
      {
        if($return == 'dev')
        {
          $return = 'master';
        }
        
        $this->structwsf_tests_suites_version = $return;
      }          
      
      $return = $this->getInput("Where do you what to install structWSF, or where is structWSF installed? (default: ".$this->structwsf_folder.")");
      
      if($return != '')
      {
        $this->structwsf_folder = $return;
      }       
      
      $return = $this->getInput("What is the domain name where the structWSF instance will be accessible (default: ".$this->structwsf_domain.")");
      
      if($return != '')
      {
        $this->structwsf_domain = $return;
      }    
      
      $this->cecho("\n\nconStruct related configuration settings:\n", 'CYAN');
            
      $return = $this->getInput("What is the Drupal version you want to install or upgrade? (default: ".$this->drupal_version.")");
      
      if($return != '')
      {
        $this->drupal_version = $return;
      }          
      
      $return = $this->getInput("What is the conStruct version you want to install or upgrade? (default: ".$this->construct_version.")");
      
      if($return != '')
      {
        $this->construct_version = $return;
      }          
      
      $this->cecho("\n\nOther tools related configuration settings:\n", 'CYAN');

      $return = $this->getInput("What is the Datasets Management Tool version you want to install or upgrade? (default: ".($this->datasets_management_tool_version == 'master' ? 'dev' : $this->datasets_management_tool_ver).")");
      
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
      
      $this->installer_configured = TRUE;   
      
      $this->saveConfigurations(); 
    }
    
    private function saveConfigurations()
    {
      $ini = "[installer]
configured = \"".($this->installer_configured ? 'true' : 'false')."\"

[structwsf]
structwsf-version = \"".$this->structwsf_version."\"
strucwsf-folder = \"".$this->structwsf_folder."\"
structwsf-domain = \"".$this->structwsf_domain."\"
structwsf-php-api-version = \"".$this->structwsf_version."\"
structwsf-tests-suites-version = \"".$this->structwsf_version."\"

[construct]
drupal-version = \"".$this->drupal_version."\"
construct-version = \"".$this->construct_version."\"

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
      $this->cecho("\n\nstructWSF related configuration settings:\n", 'CYAN');

      $this->cecho("structwsf-version: ".($this->structwsf_version == 'master' ? 'dev' : $this->structwsf_version)."\n", 'WHITE');
      $this->cecho("structwsf-folder: ".$this->structwsf_folder."\n", 'WHITE');
      $this->cecho("structwsf-domain: ".$this->structwsf_domain."\n", 'WHITE');
      $this->cecho("structwsf-php-api-version: ".($this->structwsf_php_api_version == 'master' ? 'dev' : $this->structwsf_php_api_version)."\n", 'WHITE');
      $this->cecho("structwsf-tests-suites-version: ".($this->structwsf_tests_suites_version == 'master' ? 'dev' : $this->structwsf_tests_suites_version)."\n", 'WHITE');
      
      $this->cecho("\n\nconStruct related configuration settings:\n", 'CYAN');
            
      $this->cecho("drupal-version: ".$this->drupal_version."\n", 'WHITE');
      $this->cecho("construct-version: ".$this->construct_version."\n", 'WHITE');
      
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
    * Upgrade the structWSF PHPUNIT Tests Suites
    */
    public function upgradeStructWSFTestsSuites()
    {
      $this->cecho("Upgrading tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/structwsftestssuites-upgrade/');
      
      $this->chdir('/tmp/structwsftestssuites-upgrade/');
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/'.$this->structwsf_tests_suites_version.'.zip');
      
      $this->exec('unzip '.$this->structwsf_tests_suites_version.'.zip');
      
      $this->chdir('/tmp/structwsftestssuites-upgrade/structWSF-Tests-Suites-'.$this->structwsf_tests_suites_version.'/StructuredDynamics/structwsf/');

      $this->exec('rm -rf '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('cp -af tests '.$this->structwsf_folder.'/StructuredDynamics/structwsf/');
                  
      $this->cecho("Configure the tests suites...\n", 'WHITE');

      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->structwsf_folder.'/StructuredDynamics/structwsf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \"'.$this->structwsf_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->structwsf_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->structwsf_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/structwsftestssuites-install/');
    }         
    
    public function runStructWSFTestsSuites($installationFolder = '')
    {
      if($installationFolder == '')
      {
        $installationFolder = $this->structwsf_folder;
      }
      
      $this->chdir($installationFolder.'/StructuredDynamics/structwsf/tests/');
      
      passthru('phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml');
      
      $this->chdir($this->currentWorkingDirectory);      
    }    
  }
?>
