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
    protected $structwsf_version = "2.0";

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

    /* Folder where to put the logging files */
    protected $logging_folder = "/tmp";
    
    /* Domain name where to access the structWSF instance */
    protected $structwsf_domain = "localhost";    
    
    function __construct($configFile)
    {
      // Load the installer configuration file
      $this->config = parse_ini_file($configFile, TRUE); 
      
      if(!$this->config)
      {
        $this->cecho('An error occured when we tried to parse the '.$config.' file. Make sure it is parseable and try again.'."\n", 'RED');  
        die;
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
        
        if(isset($this->config['data']['virtuoso-folder']))
        {
          $this->virtuoso_version = $this->config['data']['virtuoso-folder'];
        }
        
        if(isset($this->config['construct']['drupal-version']))
        {
          $this->drupal_version = $this->config['construct']['drupal-version'];
        }
        
        if(isset($this->config['structwsf']['structwsf-version']))
        {
          $this->structwsf_version = $this->config['structwsf']['structwsf-version'];
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
      
      $return = $this->getInput("What is the structWSF version you want to install or upgrade? (default: ".$this->structwsf_version.")");
      
      if($return != '')
      {
        $this->structwsf_version = $return;
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
            
      $return = $this->getInput("Where do you what to install the Datasets Management Tool, or where is Datasets Management Tool installed? (default: ".$this->datasets_management_tool_folder.")");
      
      if($return != '')
      {
        $this->datasets_management_tool_folder = $return;
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

[construct]
drupal-version = \"".$this->drupal_version."\"
construct-version = \"".$this->construct_version."\"

[tools]
dataset-management-tool-folder = \"".$this->datasets_management_tool_folder."\"
ontologies-management-tool-folder = \"".$this->ontologies_management_tool_folder."\"

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

      $this->cecho("structwsf-version: ".$this->structwsf_version."\n", 'WHITE');
      $this->cecho("structwsf-folder: ".$this->structwsf_folder."\n", 'WHITE');
      $this->cecho("structwsf-domain: ".$this->structwsf_domain."\n", 'WHITE');
      
      $this->cecho("\n\nconStruct related configuration settings:\n", 'CYAN');
            
      $this->cecho("drupal-version: ".$this->drupal_version."\n", 'WHITE');
      $this->cecho("construct-version: ".$this->construct_version."\n", 'WHITE');
      
      $this->cecho("\n\nOther tools related configuration settings:\n", 'CYAN');

      $this->cecho("datasets-management-tool-folder: ".$this->datasets_management_tool_folder."\n", 'WHITE');
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
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/master.zip');
      
      $this->exec('unzip master.zip');
      
      $this->chdir('/tmp/structwsftestssuites-upgrade/structWSF-Tests-Suites-master/StructuredDynamics/structwsf/');

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
