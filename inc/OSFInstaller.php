<?php

  include_once('OSFConfigurator.php');

  abstract class OSFInstaller extends OSFConfigurator
  {
    function __construct($configFile)
    {
      parent::__construct($configFile);
    }
    
    /**
    * Tries to install PHP5 using the packages available for the linux distribution
    */
    abstract public function installPhp5();
    
    /**
    * Install PHP5 with the modifications required by OSF, from source code.
    * 
    * Use this only if the packaged version of PHP5 is not working for you.
    */
    abstract public function installPhp5FromSource();
    
    /**
    * Install Virtuoso as required by OSF
    */
    abstract public function installVirtuoso();
    
    /**
    * Install Solr as required by OSF
    */
    abstract public function installSolr();

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
    * Install the entire OSF stack. Running this command will install the full stack on the server
    * according to the settings specified in the installer.ini file.
    */
    public function installOSF()
    {
      $this->cecho("You are about to install the Open Semantic Framework.\n", 'WHITE');
      $this->cecho("This installation process will install all the softwares that are part of the OSF stack. It will take 10 minutes of your time, but the process will go on for a few hours because all pieces of software that get compiled.\n\n", 'WHITE');
      $this->cecho("The log of this installation is available here: ".$this->log_file."\n", 'WHITE');
      $this->cecho("\n\nCopyright 2008-13. Structured Dynamics LLC. All rights reserved.\n\n", 'WHITE');
      
      $this->cecho("\n\n");
      $this->cecho("---------------------------------\n", 'WHITE');
      $this->cecho(" General Settings Initialization \n", 'WHITE'); 
      $this->cecho("---------------------------------\n", 'WHITE'); 
      $this->cecho("\n\n");

      $this->cecho("\n\n");
      $this->cecho("------------------------\n", 'WHITE');
      $this->cecho(" Installing prerequires \n", 'WHITE');
      $this->cecho("------------------------\n", 'WHITE');
      $this->cecho("\n\n");

      $yes = $this->isYes($this->getInput("We recommand you to upgrade all softwares of the server. Would you like to do this right now? (yes/no)"));             
      
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

      $this->installMySQL();    

      $this->installPhp5();
     
      $this->installApache2();  

      $this->installPhpMyAdmin();
      
      $this->installVirtuoso();
      
      $this->installSolr();

      $this->installStructWSFPHPAPI();
      $this->installDatasetsManagementTool();
      $this->installOntologiesManagementTool();

      $this->installStructWSF();      
    }
    
    
    /**
    * Install the structWSF-PHP-API library
    * 
    */
    public function installStructWSFPHPAPI($version = '')
    {                                                  
      if($version == '')
      {
        $version = $this->structwsf_php_api_version;
      }
                    
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("------------------------------\n", 'WHITE');
      $this->cecho(" Installing structWSF-PHP-API \n", 'WHITE');
      $this->cecho("------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      if(is_dir($this->structwsf_folder.'/StructuredDynamics/structwsf/php/'))                
      {
        $this->cecho("The structWSF-PHP-API is already installed. Consider upgrading it with the option: --upgrade-structwsf-php-api\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/structwsfphpapi');

      $this->cecho("Downloading the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsfphpapi https://github.com/structureddynamics/structWSF-PHP-API/archive/'.$version.'.zip');

      $this->cecho("Installing the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('unzip -o /tmp/structwsfphpapi/'.$version.'.zip -d /tmp/structwsfphpapi/');      
      
      if(!is_dir($this->structwsf_folder.'/'))
      {
        $this->exec('mkdir -p '.$this->structwsf_folder.'/');      
      }
      
      $this->exec('cp -af /tmp/structwsfphpapi/structWSF-PHP-API-'.$version.'/StructuredDynamics '.$this->structwsf_folder.'/');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsfphpapi/');
    }

    /**
    * Upgrade a structWSF-PHP-API installation
    */
    public function upgradeStructWSFPHPAPI($version = '')
    {
      if($version == '')
      {
        $version = $this->structwsf_php_api_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho(" Upgrading structWSF-PHP-API \n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE'); 
      
      $backupFolder = '/tmp/structwsfphpapi-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('mv '.$this->structwsf_folder.'/StructuredDynamics/structwsf/php/ '.$backupFolder);
      
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/structwsfphpapi');

      $this->cecho("Downloading the latest code of the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsfphpapi https://github.com/structureddynamics/structWSF-PHP-API/archive/'.$version.'.zip');

      $this->cecho("Upgrading the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('unzip -o /tmp/structwsfphpapi/'.$version.'.zip -d /tmp/structwsfphpapi/');      
      
      if(!is_dir($this->structwsf_folder.'/'))
      {
        $this->exec('mkdir -p '.$this->structwsf_folder.'/');      
      }
      
      $this->exec('cp -af /tmp/structwsfphpapi/structWSF-PHP-API-'.$version.'/StructuredDynamics '.$this->structwsf_folder.'/');

      $this->cecho("Cleaning upgrade folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsfphpapi/');   
    }
    
    /**
    * Install the Datasets Management Tool
    */
    public function installDatasetsManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->datasets_management_tool_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("------------------------------------\n", 'WHITE');
      $this->cecho(" Installing Datasets Management Tool \n", 'WHITE');
      $this->cecho("------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      if(is_dir($this->datasets_management_tool_folder.'/'))                
      {
        $this->cecho("The Datasets Management Tool is already installed. Consider upgrading it with the option: --upgrade-datasets-management-tool\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dmt');

      $this->cecho("Downloading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dmt https://github.com/structureddynamics/structWSF-Datasets-Management-Tool/archive/'.$version.'.zip');

      $this->cecho("Installing the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/'.$version.'.zip -d /tmp/dmt/');      
      
      $this->exec('mkdir -p '.$this->datasets_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/dmt/structWSF-Datasets-Management-Tool-'.$version.'/* '.$this->datasets_management_tool_folder.'/');

      $this->exec('chmod 755 '.$this->datasets_management_tool_folder.'/dmt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$this->datasets_management_tool_folder.'/dmt dmt');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }
    
    /**
    * Install the Datasets Management Tool
    */
    public function installDataValidatorTool($version = '')
    {
      if($version == '')
      {
        $version = $this->data_validator_tool_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("--------------------------------\n", 'WHITE');
      $this->cecho(" Installing Data Validator Tool \n", 'WHITE');
      $this->cecho("--------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      $dataValidatorFolder = $this->structwsf_folder.'/StructuredDynamics/structwsf/validator/';
      
      if(is_dir($dataValidatorFolder))                
      {
        $this->cecho("The Data Validator Tool is already installed. Consider upgrading it with the option: --upgrade-data-validator-tool\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dvt');

      $this->cecho("Downloading the Data Validator Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dvt https://github.com/structureddynamics/structWSF-Data-Validator-Tool/archive/'.$version.'.zip');

      $this->cecho("Installing the Data Validator Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dvt/'.$version.'.zip -d /tmp/dvt/');      
      
      $this->exec('mkdir -p '.$dataValidatorFolder);      
      
      $this->exec('cp -af /tmp/dvt/structWSF-Data-Validator-Tool-'.$version.'/StructuredDynamics/structwsf/validator/* '.$dataValidatorFolder);

      $this->exec('chmod 755 '.$dataValidatorFolder.'dvt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$dataValidatorFolder.'dvt dvt');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dvt/');      
    }    
    
    /**
    * Upgrade a Datasets Management Tool installation
    */
    public function upgradeDatasetsManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->datasets_management_tool_version;
      }      
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("----------------------------------------\n", 'WHITE');
      $this->cecho(" Upgrading the Datasets Management Tool \n", 'WHITE');
      $this->cecho("----------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $backupFolder = '/tmp/dmt-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$this->datasets_management_tool_folder.'/ '.$backupFolder);
                                              
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dmt');

      $this->cecho("Downloading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dmt https://github.com/structureddynamics/structWSF-Datasets-Management-Tool/archive/'.$version.'.zip');

      $this->cecho("Upgrading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/'.$version.'.zip -d /tmp/dmt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the sync.ini file
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-'.$version.'/data/');
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-'.$version.'/missing/');
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-'.$version.'/datasetIndexes/');
      $this->exec('rm -f /tmp/dmt/structWSF-Datasets-Management-Tool-'.$version.'/sync.ini');      
      
      $this->exec("cp -af /tmp/dmt/structWSF-Datasets-Management-Tool-".$version."/* ".$this->datasets_management_tool_folder."/");

      // Make "dmt" executable
      $this->exec('chmod 755 '.$this->datasets_management_tool_folder.'/dmt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }    
    
    /**
    * Upgrade a Data Validator Tool installation
    */
    public function upgradeDataValidatorTool($version = '')
    {
      if($version == '')
      {
        $version = $this->data_validator_tool_version;
      }      
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------------------\n", 'WHITE');
      $this->cecho(" Upgrading the Data Validator Tool \n", 'WHITE');
      $this->cecho("-----------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $dataValidatorFolder = $this->structwsf_folder.'/StructuredDynamics/structwsf/validator/';
      
      $backupFolder = '/tmp/dvt-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$dataValidatorFolder.' '.$backupFolder);
                                              
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dvt');

      $this->cecho("Downloading the Data Validator Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dvt https://github.com/structureddynamics/structWSF-Data-Validator-Tool/archive/'.$version.'.zip');

      $this->cecho("Upgrading the Data Validator Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dvt/'.$version.'.zip -d /tmp/dvt/');      
      
      $this->exec('cp -af /tmp/dvt/structWSF-Data-Validator-Tool-'.$version.'/StructuredDynamics/structwsf/validator/* '.$dataValidatorFolder);

      // Make "dvt" executable
      $this->exec('chmod 755 '.$dataValidatorFolder.'dvt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dvt/');      
    }     
    
    /**
    * Install structWSF
    */
    public function installStructWSF($version='')
    {
      if($version == '')
      {
        $version = $this->structwsf_version;
      }
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("----------------------\n", 'WHITE');
      $this->cecho(" Installing structWSF \n", 'WHITE');
      $this->cecho("----------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
  
      if(is_dir($this->structwsf_folder.'/StructuredDynamics/structwsf/ws/'))                
      {
        $this->cecho("The structWSF is already installed. Consider upgrading it with the option: --upgrade-structwsf\n", 'YELLOW');
        
        return;
      } 
      
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/structwsf-install');

      $this->cecho("Downloading structWSF...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsf-install https://github.com/structureddynamics/structWSF-Open-Semantic-Framework/archive/'.$version.'.zip');

      $this->cecho("Installing structWSF...\n", 'WHITE');
      $this->exec('unzip -o /tmp/structwsf-install/'.$version.'.zip -d /tmp/structwsf-install/');      
      
      $this->exec('mkdir -p '.$this->structwsf_folder.'/');      
      
      $this->exec('cp -af /tmp/structwsf-install/structWSF-Open-Semantic-Framework-'.$version.'/* '.$this->structwsf_folder.'/');

      $this->cecho("Configuring structWSF...\n", 'WHITE');
      
      //$this->cecho("Fixing the index.php file to refer to the proper SID folder...\n", 'WHITE');

      //$this->exec('sed -i \'s>$sidDirectory = "";>$sidDirectory = "/structwsf/tmp/";>\' "'.$this->structwsf_folder.'/index.php"');

      $this->cecho("Configure Apache2 for structWSF...\n", 'WHITE');
      
      $this->exec('cp resources/structwsf/structwsf /etc/apache2/sites-available/');

      $this->exec('sudo ln -s /etc/apache2/sites-available/structwsf /etc/apache2/sites-enabled/structwsf');
      
      // Fix the structWSF path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/structwsf>'.$this->structwsf_folder.$this->structwsf_ns.'>" "/etc/apache2/sites-available/structwsf"');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Configure the WebService.php file...\n", 'WHITE');

      $this->exec('sed -i \'s>public static $data_ini = "/usr/share/structwsf/StructuredDynamics/structwsf/ws/";>public static $data_ini = "'.$this->structwsf_folder.$this->structwsf_ns.'/";>\' "'.$this->structwsf_folder.$this->structwsf_ns.'/framework/WebService.php"');
      $this->exec('sed -i \'s>public static $network_ini = "/usr/share/structwsf/StructuredDynamics/structwsf/ws/";>public static $network_ini = "'.$this->structwsf_folder.$this->structwsf_ns.'/";>\' "'.$this->structwsf_folder.$this->structwsf_ns.'/framework/WebService.php"');

      $this->cecho("Configure the data.ini configuration file...\n", 'WHITE');

      $dbaPassword = 'dba';     
      
      $return = $this->getInput("What is the password of the DBA user in Virtuoso (default: dba)");

      if($return != '')
      {
        $dbaPassword = $return;
      }     

      $this->cecho("Make sure structWSF is aware of itself by changing the hosts file...\n", 'WHITE');
      
      if(stripos(file_get_contents('/etc/hosts'), 'OSF-Installer') == FALSE)
      {
        file_put_contents('/etc/hosts', "\n\n# Added by the OSF-Installer to make structWSF aware of itself\n127.0.0.1 ".$this->structwsf_domain, FILE_APPEND);
      } 
      
      // fix wsf_graph
      $this->exec('sed -i "s>wsf_graph = \"http://localhost/wsf/\">wsf_graph = \"http://'.$this->structwsf_domain.'/wsf/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix dtd_base
      $this->exec('sudo sed -i "s>dtd_base = \"http://localhost/ws/dtd/\">dtd_base = \"http://'.$this->structwsf_domain.'/ws/dtd/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix ontologies_files_folder
      $this->exec('sudo sed -i "s>ontologies_files_folder = \"/data/ontologies/files/\">ontologies_files_folder = \""'.$this->data_folder.'"/ontologies/files/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix ontological_structure_folder
      $this->exec('sudo sed -i "s>ontological_structure_folder = \"/data/ontologies/structure/\">ontological_structure_folder = \"'.$this->data_folder.'/ontologies/structure/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix password
      $this->exec('sudo sed -i "s>password = \"dba\">password = \"'.$dbaPassword.'\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix host
      $this->exec('sudo sed -i "s>host = \"localhost\">host = \"'.$this->structwsf_domain.'\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix fields_index_folder
      $this->exec('sudo sed -i "s>fields_index_folder = \"/tmp/\">fields_index_folder = \"'.$this->data_folder.'/structwsf/tmp/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');

      // fix wsf_base_url
      $this->exec('sudo sed -i "s>wsf_base_url = \"http://localhost\">wsf_base_url = \"http://'.$this->structwsf_domain.'\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');

      // fix wsf_base_path
      $this->exec('sudo sed -i "s>wsf_base_path = \"/usr/share/structwsf/\">wsf_base_path = \"'.$this->structwsf_folder.$this->structwsf_ns.'/\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');

      $this->exec('sudo sed -i "s>enable_lrl = \"FALSE\">enable_lrl = \"TRUE\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/data.ini"');


      
      if(!$this->isYes($this->getInput("Do you want to enable logging in structWSF? (yes/no) (default: yes)")))
      {
        $this->exec('sudo sed -i "s>log_enable = \"true\">log_enable = \"false\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Create web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_create = \"false\">track_create = \"true\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Update web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_update = \"false\">track_update = \"true\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Delete web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_delete = \"false\">track_delete = \"true\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to geo-enable structWSF? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>geoenabled = \"false\">geoenabled = \"true\">" "'.$this->structwsf_folder.$this->structwsf_ns.'/network.ini"');
      }
      
      $this->cecho("Install the Solr schema for structWSF...\n", 'WHITE');
      
      if(!file_exists('/usr/share/solr/structwsf/solr/conf/schema.xml'))
      {
        $this->cecho("Solr is not yet installed. Install Solr using this --install-solr option and then properly configure its schema by hand.\n", 'WHITE');
      }
      else
      {
        $this->exec('cp -f '.$this->structwsf_folder.$this->structwsf_ns.'/framework/solr_schema_v1_3_1.xml /usr/share/solr/structwsf/solr/conf/schema.xml');
        
        $this->cecho("Restarting Solr...\n", 'WHITE');
        $this->exec('/etc/init.d/solr stop');
        $this->exec('/etc/init.d/solr start');
      }
      
      $this->cecho("Installing ARC2...\n", 'WHITE');
      
      $this->chdir($this->structwsf_folder.$this->structwsf_ns.'/framework/arc2/');
      
      $this->exec('wget -q https://github.com/semsol/arc2/archive/v2.1.1.zip');
      
      $this->exec('unzip v2.1.1.zip');
      
      $this->chdir($this->structwsf_folder.$this->structwsf_ns.'/framework/arc2/arc2-2.1.1/');
      
      $this->exec('mv * ../');
      
      $this->chdir($this->structwsf_folder.$this->structwsf_ns.'/framework/arc2/');
      
      $this->exec('rm -rf arc2-2.1.1');
      
      $this->exec('rm v*.zip*');
      
      $this->chdir($this->currentWorkingDirectory);
      
      
      $this->cecho("Installing OWLAPI requirements...", 'WHITE');
      
      $this->exec('apt-get -y install tomcat6');
      
      $this->exec('/etc/init.d/tomcat6 stop');
      
      $this->cecho("Downloading OWLAPI...\n", 'WHITE');
      
      $this->chdir('/var/lib/tomcat6/webapps/');
      
      $this->exec('wget -q http://techwiki.openstructs.org/files/OWLAPI.war');
      
      $this->cecho("Starting Tomcat6 to install the OWLAPI war installation file...\n", 'WHITE');
      
      $this->exec('/etc/init.d/tomcat6 start');
      
      // wait 20 secs to make sure Tomcat6 had the time to install the OWLAPI webapp
      sleep(20);
      
      $this->cecho("Configuring PHP for the OWLAPI...\n", 'WHITE');
      
      $this->exec('sed -i "s/allow_url_include = Off/allow_url_include = On/" /etc/php5/apache2/php.ini'); 
      $this->exec('sed -i "s/allow_url_include = Off/allow_url_include = On/" /etc/php5/cli/php.ini'); 

      $this->exec(' sed -i "s/allow_call_time_pass_reference = Off/allow_call_time_pass_reference = On/" /etc/php5/apache2/php.ini');
      $this->exec(' sed -i "s/allow_call_time_pass_reference = Off/allow_call_time_pass_reference = On/" /etc/php5/cli/php.ini');

      $this->cecho("Restart Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');

      $this->cecho("Create the WSF Network...\n", 'WHITE');
      
      $this->cecho("Reset WSF...\n", 'WHITE');
      
      $this->exec('curl -s "http://'.$this->structwsf_domain.'/ws/auth/wsf_indexer.php?action=reset"');
      
      $this->cecho("Create WSF...\n", 'WHITE');
      
      $this->exec('curl -s "http://'.$this->structwsf_domain.'/ws/auth/wsf_indexer.php?action=create_wsf&server_address=http://'.$this->structwsf_domain.'"');
      

      $domainIP = shell_exec('(ping -c 1 '.$this->structwsf_domain.' | grep -E -o "[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}" | head -1)');
      
      $this->cecho("Create user full access for (server's own external IP): ".$domainIP." ...\n", 'WHITE');
      
      $this->exec('curl -s "http://'.$this->structwsf_domain.'/ws/auth/wsf_indexer.php?action=create_user_full_access&user_address='.$domainIP.'&server_address=http://'.$this->structwsf_domain.'"');
      
      $this->cecho("Create user full access for: 127.0.0.1 ...\n", 'WHITE');
      
      $this->exec('curl -s "http://'.$this->structwsf_domain.'/ws/auth/wsf_indexer.php?action=create_user_full_access&user_address=127.0.0.1&server_address=http://'.$this->structwsf_domain.'"');
      
      $this->cecho("Create world readable dataset read...\n", 'WHITE');
      
      $this->exec('curl -s "http://'.$this->structwsf_domain.'/ws/auth/wsf_indexer.php?action=create_world_readable_dataset_read&server_address=http://'.$this->structwsf_domain.'"');
      
      $this->cecho("Commit transactions to Virtuoso...\n", 'WHITE');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/commit.php"');
      
      $return = shell_exec('php resources/virtuoso/commit.php');
      
      if($return == 'errors')
      {
        $this->cecho("Couldn't commit triples to the Virtuoso triples store...\n", 'YELLOW');
      }
      
      $this->cecho("Rename the wsf_indexer.php script with a random name for security purposes...\n", 'WHITE');
      
      $shadow = md5(microtime());
      
      $this->chdir($this->structwsf_folder.$this->structwsf_ns.'/auth/');
      
      rename('wsf_indexer.php', 'wsf_indexer_'.$shadow.'.php');

      $this->cecho("Create Data & Ontologies folders...\n", 'WHITE');
      
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/files/"');
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/structure/"');

      $this->cecho("Download the core OSF ontologies files...\n", 'WHITE');

      $this->chdir($this->data_folder.'/ontologies/files');
            
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/aggr/aggr.owl');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/iron/iron.owl');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/owl/owl.rdf');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdf.xml');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdfs.xml');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/sco/sco.owl');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wgs84/wgs84.owl');
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wsf/wsf.owl');

      $this->cecho("Load ontologies...\n", 'WHITE');
      
      $this->chdir($this->ontologies_management_tool_folder);
      
      $this->exec('omt --load-all --load-list="'.rtrim($this->currentWorkingDirectory, '/').'/resources/structwsf/ontologies.lst" --structwsf="http://'.$this->structwsf_domain.'/ws/"');

      $this->cecho("Create underlying ontological structures...\n", 'WHITE');
      
      $this->exec('omt --generate-structures="'.$this->data_folder.'/ontologies/structure/" --structwsf="http://'.$this->structwsf_domain.'/ws/"');

      $this->installStructWSFTestsSuites();

      $this->chdir($this->currentWorkingDirectory);

      
      $this->cecho("Set files owner permissions...\n", 'WHITE');
      
      $this->exec('chown -R www-data:www-data '.$this->structwsf_folder.$this->structwsf_ns.'/');
      $this->exec('chmod -R 755 '.$this->structwsf_folder.$this->structwsf_ns.'/');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsf-install/');  
      
      $this->runStructWSFTestsSuites($this->structwsf_folder);
    }    

    /**
    * Install the structWSF PHPUNIT Tests Suites
    */
    public function installStructWSFTestsSuites($version = '')
    {
      if($version == '')
      {
        $version = $this->structwsf_tests_suites_version;
      }
            
      $this->cecho("Installing PHPUNIT\n", 'WHITE');

      $this->chdir('/tmp');
      
      $this->exec('wget http://pear.php.net/go-pear.phar');
      
      passthru('php go-pear.phar');
      
      $this->exec('pear channel-discover pear.phpunit.de', 'warning');
      
      $this->exec('pear channel-discover pear.symfony-project.com', 'warning');
      
      $this->exec('pear upgrade-all', 'warning');

      $this->exec('pear config-set auto_discover 1');
      
      $this->exec('pear install pear.phpunit.de/PHPUnit');
      
      
      $this->cecho("PHPUnit Installed!\n", 'WHITE');      
      
      $this->cecho("Install tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/'.$version.'.zip');
      
      $this->exec('unzip '.$version.'.zip');      
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/structWSF-Tests-Suites-'.$version.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('mv * ../../../../');

      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('rm *.zip');
            
      $this->exec('rm -rf structWSF-Tests-Suites-'.$version.'');
      
      $this->cecho("Configure the tests suites...\n", 'WHITE');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->structwsf_folder.'/StructuredDynamics/structwsf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \"'.$this->structwsf_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->structwsf_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->structwsf_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
    }    
    

    /**
    * Update the structWSF PHPUNIT Tests Suites
    */
    public function updateStructWSFTestsSuites($version = '')
    {
      if($version == '')
      {
        $version = $this->structwsf_tests_suites_version;
      }
      
      $this->cecho("Updating tests suites...\n", 'WHITE');
      
      $this->exec('rm -rf '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('mkdir -p '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/'.$version.'.zip');
      
      $this->exec('unzip '.$version.'.zip');      
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/structWSF-Tests-Suites-'.$version.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('mv * ../../../../');

      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('rm *.zip');
            
      $this->exec('rm -rf structWSF-Tests-Suites-'.$version.'');
      
      $this->cecho("Configure the tests suites...\n", 'WHITE');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->structwsf_folder.'/StructuredDynamics/structwsf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \"'.$this->structwsf_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->structwsf_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->structwsf_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
    }    
    
    /**
    * Install the Ontologies Management Tool
    */
    public function installOntologiesManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->ontologies_management_tool_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("---------------------------------------\n", 'WHITE');
      $this->cecho(" Installing Ontologies Management Tool \n", 'WHITE');
      $this->cecho("---------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      if(is_dir($this->ontologies_management_tool_folder.'/'))                
      {
        $this->cecho("The Ontologies Management Tool is already installed. Consider upgrading it with the option: --upgrade-ontologies-management-tool\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/omt');

      $this->cecho("Downloading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/omt https://github.com/structureddynamics/structWSF-Ontologies-Management-Tool/archive/'.$version.'.zip');

      $this->cecho("Installing the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/'.$version.'.zip -d /tmp/omt/');      
      
      $this->exec('mkdir -p '.$this->ontologies_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/omt/structWSF-Ontologies-Management-Tool-'.$version.'/* '.$this->ontologies_management_tool_folder.'/');

      $this->exec('chmod 755 '.$this->ontologies_management_tool_folder.'/omt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$this->ontologies_management_tool_folder.'/omt omt');
      
      $this->chdir($this->currentWorkingDirectory);
            
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/omt/');      
    }   
    
    /**
    * Update an Ontologies Management Tool installation
    */
    public function upgradeOntologiesManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->ontologies_management_tool_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("------------------------------------------\n", 'WHITE');
      $this->cecho(" Upgrading the Ontologies Management Tool \n", 'WHITE');
      $this->cecho("------------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $backupFolder = '/tmp/omt-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$this->ontologies_management_tool_folder.'/ '.$backupFolder);
                                              
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/omt');

      $this->cecho("Downloading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/omt https://github.com/structureddynamics/structWSF-Ontologies-Management-Tool/archive/'.$version.'.zip');

      $this->cecho("Upgrading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/'.$version.'.zip -d /tmp/omt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the sync.ini file
      $this->exec('rm -rf /tmp/omt/structWSF-Ontologies-Management-Tool-'.$version.'/sync.ini');
      
      $this->exec("cp -af /tmp/omt/structWSF-Ontologies-Management-Tool-".$version."/* ".$this->ontologies_management_tool_folder."/");

      // Make "omt" executable
      $this->exec('chmod 755 '.$this->ontologies_management_tool_folder.'/omt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/omt/');      
    }
  }
?>
