<?php

  abstract class OSFInstaller
  {
    /* Parsed intaller.ini configuration file */
    protected $config;
    
    /* Full path of the logfile */
    protected $log_file = '';
    
    /* Specify if if we output everything we got from the commands */
    protected $verbose = FALSE;
    
    // Configufation options
    
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
    public function installStructWSFPHPAPI()
    {                                                            
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
      $this->exec('mkdir /tmp/structwsfphpapi');

      $this->cecho("Downloading the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsfphpapi https://github.com/structureddynamics/structWSF-PHP-API/archive/master.zip');

      $this->cecho("Installing the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('unzip -o /tmp/structwsfphpapi/master.zip -d /tmp/structwsfphpapi/');      
      
      if(!is_dir($this->structwsf_folder.'/'))
      {
        $this->exec('mkdir '.$this->structwsf_folder.'/');      
      }
      
      $this->exec('cp -af /tmp/structwsfphpapi/structWSF-PHP-API-master/StructuredDynamics '.$this->structwsf_folder.'/');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsfphpapi/');
    }

    /**
    * Upgrade a structWSF-PHP-API installation
    */
    public function upgradeStructWSFPHPAPI()
    {
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
      $this->exec('mkdir /tmp/structwsfphpapi');

      $this->cecho("Downloading the latest code of the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsfphpapi https://github.com/structureddynamics/structWSF-PHP-API/archive/master.zip');

      $this->cecho("Upgrading the structWSF-PHP-API...\n", 'WHITE');
      $this->exec('unzip /tmp/structwsfphpapi/master.zip -d /tmp/structwsfphpapi/ -o');      
      
      if(!is_dir($this->structwsf_folder.'/'))
      {
        $this->exec('mkdir '.$this->structwsf_folder.'/');      
      }
      
      $this->exec('cp -af /tmp/structwsfphpapi/structWSF-PHP-API-master/StructuredDynamics '.$this->structwsf_folder.'/');

      $this->cecho("Cleaning upgrade folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsfphpapi/');   
    }
    
    /**
    * Install the Datasets Management Tool
    */
    public function installDatasetsManagementTool()
    {
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
      $this->exec('mkdir /tmp/dmt');

      $this->cecho("Downloading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dmt https://github.com/structureddynamics/structWSF-Datasets-Management-Tool/archive/master.zip');

      $this->cecho("Installing the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/master.zip -d /tmp/dmt/');      
      
      $this->exec('mkdir '.$this->datasets_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/dmt/structWSF-Datasets-Management-Tool-master/* '.$this->datasets_management_tool_folder.'/');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }
    
    /**
    * Upgrade a Datasets Management Tool installation
    */
    public function upgradeDatasetsManagementTool()
    {
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
      $this->exec('mkdir /tmp/dmt');

      $this->cecho("Downloading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dmt https://github.com/structureddynamics/structWSF-Datasets-Management-Tool/archive/master.zip');

      $this->cecho("Upgrading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/master.zip -d /tmp/dmt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the sync.ini file
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-master/data/');
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-master/missing/');
      $this->exec('rm -rf /tmp/dmt/structWSF-Datasets-Management-Tool-master/datasetIndexes/');
      $this->exec('rm -f /tmp/dmt/structWSF-Datasets-Management-Tool-master/sync.ini');      
      
      $this->exec("cp -af /tmp/dmt/structWSF-Datasets-Management-Tool-master/* ".$this->datasets_management_tool_folder."/");

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }    
    
    /**
    * Install structWSF
    */
    public function installStructWSF()
    {
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
      
      $currentWorkingDirectory = getcwd();
      
      $ns = '/StructuredDynamics/structwsf/ws';

      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir /tmp/structwsf-install');

      $this->cecho("Downloading structWSF...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/structwsf-install https://github.com/structureddynamics/structWSF-Open-Semantic-Framework/archive/master.zip');

      $this->cecho("Installing structWSF...\n", 'WHITE');
      $this->exec('unzip -o /tmp/structwsf-install/master.zip -d /tmp/structwsf-install/');      
      
      $this->exec('mkdir '.$this->structwsf_folder.'/');      
      
      $this->exec('cp -af /tmp/structwsf-install/structWSF-Open-Semantic-Framework-master/* '.$this->structwsf_folder.'/');

      $this->cecho("Configuring structWSF...\n", 'WHITE');
      
      //$this->cecho("Fixing the index.php file to refer to the proper SID folder...\n", 'WHITE');

      //$this->exec('sed -i \'s>$sidDirectory = "";>$sidDirectory = "/structwsf/tmp/";>\' "'.$this->structwsf_folder.'/index.php"');

      $this->cecho("Configure Apache2 for structWSF...\n", 'WHITE');
      
      $this->exec('cp resources/structwsf/structwsf /etc/apache2/sites-available/');

      $this->exec('sudo ln -s /etc/apache2/sites-available/structwsf /etc/apache2/sites-enabled/structwsf');
      
      // Fix the structWSF path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/structwsf>'.$this->structwsf_folder.$ns.'>" "/etc/apache2/sites-available/structwsf"');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Configure the WebService.php file...\n", 'WHITE');

      $this->exec('sed -i \'s>public static $data_ini = "/usr/share/structwsf/StructuredDynamics/structwsf/ws/";>public static $data_ini = "'.$this->structwsf_folder.$ns.'/";>\' "'.$this->structwsf_folder.$ns.'/framework/WebService.php"');
      $this->exec('sed -i \'s>public static $network_ini = "/usr/share/structwsf/StructuredDynamics/structwsf/ws/";>public static $network_ini = "'.$this->structwsf_folder.$ns.'/";>\' "'.$this->structwsf_folder.$ns.'/framework/WebService.php"');

      $return = $this->getInput("What is the domain name where the structWSF instance will be accessible (default: ".$this->structwsf_domain.")");

      $this->cecho("Configure the data.ini configuration file...\n", 'WHITE');
      
      if($return != '')
      {
        $this->structwsf_domain = $return;
      }     

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
      $this->exec('sed -i "s>wsf_graph = \"http://localhost/wsf/\">wsf_graph = \"http://'.$this->structwsf_domain.'/wsf/\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix dtd_base
      $this->exec('sudo sed -i "s>dtd_base = \"http://localhost/ws/dtd/\">dtd_base = \"http://'.$this->structwsf_domain.'/ws/dtd/\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix ontologies_files_folder
      $this->exec('sudo sed -i "s>ontologies_files_folder = \"/data/ontologies/files/\">ontologies_files_folder = \""'.$this->data_folder.'"/ontologies/files/\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix ontological_structure_folder
      $this->exec('sudo sed -i "s>ontological_structure_folder = \"/data/ontologies/structure/\">ontological_structure_folder = \"'.$this->data_folder.'/ontologies/structure/\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix password
      $this->exec('sudo sed -i "s>password = \"dba\">password = \"'.$dbaPassword.'\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix host
      $this->exec('sudo sed -i "s>host = \"localhost\">host = \"'.$this->structwsf_domain.'\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix fields_index_folder
      $this->exec('sudo sed -i "s>fields_index_folder = \"/tmp/\">fields_index_folder = \"'.$this->data_folder.'/structwsf/tmp/\">" "'.$this->structwsf_folder.$ns.'/data.ini"');

      // fix wsf_base_url
      $this->exec('sudo sed -i "s>wsf_base_url = \"http://localhost\">wsf_base_url = \"http://'.$this->structwsf_domain.'\">" "'.$this->structwsf_folder.$ns.'/network.ini"');

      // fix wsf_base_path
      $this->exec('sudo sed -i "s>wsf_base_path = \"/usr/share/structwsf/\">wsf_base_path = \"'.$this->structwsf_folder.$ns.'/\">" "'.$this->structwsf_folder.$ns.'/network.ini"');

      $this->exec('sudo sed -i "s>enable_lrl = \"FALSE\">enable_lrl = \"TRUE\">" "'.$this->structwsf_folder.$ns.'/data.ini"');


      
      if(!$this->isYes($this->getInput("Do you want to enable logging in structWSF? (yes/no) (default: yes)")))
      {
        $this->exec('sudo sed -i "s>log_enable = \"true\">log_enable = \"false\">" "'.$this->structwsf_folder.$ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Create web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_create = \"false\">track_create = \"true\">" "'.$this->structwsf_folder.$ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Update web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_update = \"false\">track_update = \"true\">" "'.$this->structwsf_folder.$ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to enable changes tracking for the CRUD: Delete web service endpoint? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>track_delete = \"false\">track_delete = \"true\">" "'.$this->structwsf_folder.$ns.'/network.ini"');
      }
      
      if($this->isYes($this->getInput("Do you want to geo-enable structWSF? (yes/no) (default: no)")))
      {
        $this->exec('sudo sed -i "s>geoenabled = \"false\">geoenabled = \"true\">" "'.$this->structwsf_folder.$ns.'/network.ini"');
      }
      
      $this->cecho("Install the Solr schema for structWSF...\n", 'WHITE');
      
      if(!file_exists('/usr/share/solr/structwsf/solr/conf/schema.xml'))
      {
        $this->cecho("Solr is not yet installed. Install Solr using this --install-solr option and then properly configure its schema by hand.\n", 'WHITE');
      }
      else
      {
        $this->exec('cp -f '.$this->structwsf_folder.$ns.'/framework/solr_schema_v1_3_1.xml /usr/share/solr/structwsf/solr/conf/schema.xml');
        
        $this->cecho("Restarting Solr...\n", 'WHITE');
        $this->exec('/etc/init.d/solr stop');
        $this->exec('/etc/init.d/solr start');
      }
      
      $this->cecho("Installing ARC2...\n", 'WHITE');
      
      $this->chdir($this->structwsf_folder.$ns.'/framework/arc2/');
      
      $this->exec('wget -q https://github.com/semsol/arc2/archive/v2.1.1.zip');
      
      $this->exec('unzip v2.1.1.zip');
      
      $this->chdir($this->structwsf_folder.$ns.'/framework/arc2/arc2-2.1.1/');
      
      $this->exec('mv * ../');
      
      $this->chdir($this->structwsf_folder.$ns.'/framework/arc2/');
      
      $this->exec('rm -rf arc2-2.1.1');
      
      $this->exec('rm v*.zip*');
      
      $this->chdir($currentWorkingDirectory);
      
      
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
      
      $this->chdir($currentWorkingDirectory);
      
      $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/commit.php"');
      
      $return = shell_exec('php resources/virtuoso/commit.php');
      
      if($return == 'errors')
      {
        $this->cecho("Couldn't commit triples to the Virtuoso triples store...\n", 'YELLOW');
      }
      
      $this->cecho("Rename the wsf_indexer.php script with a random name for security purposes...\n", 'WHITE');
      
      $shadow = md5(microtime());
      
      $this->chdir($this->structwsf_folder.$ns.'/auth/');
      
      rename('wsf_indexer.php', 'wsf_indexer_'.$shadow.'.php');

      $this->cecho("Create Data & Ontologies folders...\n", 'WHITE');
      
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/files/"');
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/structure/"');

      $this->cecho("Download the core OSF ontologies files...\n", 'WHITE');

      $this->chdir($this->data_folder.'/ontologies/files');
            
      $this->exec('wget -q https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/aggr/aggr.owl');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/iron/iron.owl');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/owl/owl.rdf');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdf.xml');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdfs.xml');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/sco/sco.owl');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wgs84/wgs84.owl');
      $this->exec('wget -q sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wsf/wsf.owl');

      $this->cecho("Load ontologies...\n", 'WHITE');
      
      $this->chdir($this->ontologies_management_tool_folder);
      
      $this->exec('php sync.php --generate-structures="'.$this->data_folder.'/ontologies/structure/" --structwsf="http://'.$this->structwsf_domain.'/ws/"');

      $this->cecho("Create underlying ontological structures...\n", 'WHITE');
      
      $this->exec('php sync.php --load-all --load-list="'.rtrim($currentWorkingDirectory, '/').'/resources/structwsf/ontologies.lst" --structwsf="http://'.$this->structwsf_domain.'/ws/"');

      $this->installStructWSFTestsSuites();

      $this->chdir($currentWorkingDirectory);

      
      $this->cecho("Set files owner permissions...\n", 'WHITE');
      
      $this->exec('chown -R www-data:www-data '.$this->structwsf_folder.$ns.'/');
      $this->exec('chmod -R 755 '.$this->structwsf_folder.$ns.'/');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/structwsf-install/');  
      
      $this->runStructWSFTestsSuites($this->structwsf_folder);
    }    

    /**
    * Install the structWSF PHPUNIT Tests Suites
    */
    public function installStructWSFTestsSuites()
    {
      $currentWorkingDirectory = getcwd();
      
      $this->cecho("Installing PHPUNIT\n", 'WHITE');
      
      $this->exec('apt-get install -y php-pear');
      
      $this->exec('pear channel-discover pear.phpunit.de');
      
      $this->exec('pear channel-discover pear.symfony-project.com');
      
      $this->exec('pear channel-discover pear.phpunit.de');
      
      $this->exec('pear upgrade-all');
      
      $this->exec('pear install --force --alldeps phpunit/PHPUnit');
      
      $this->cecho("Install tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/master.zip');
      
      $this->exec('unzip master.zip');      
      
      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/structWSF-Tests-Suites-master/StructuredDynamics/structwsf/tests/');
      
      $this->exec('mv * ../../../../');
      
      $this->exec('rm *.zip');
            
      $this->exec('rm -rf structWSF-Tests-Suites-master');
      
      $this->cecho("Configure the tests suites...\n", 'WHITE');

      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $return = $this->getInput("What is the domain name where the structWSF instance is accessible (default: ".$this->structwsf_domain.")");

      if($return != '')
      {
        $this->structwsf_domain = $return;        
      }
      
      $return = $this->getInput("What is the structwsf installation folder (default: ".$this->structwsf_folder.")");

      if($return != '')
      {
        $this->structwsf_folder = $return;        
      }
      
      $this->exec('sed -i "s>REPLACEME>'.$this->structwsf_folder.'/StructuredDynamics/structwsf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \"'.$this->structwsf_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->structwsf_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->structwsf_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($currentWorkingDirectory);
    }
    
    /**
    * Upgrade the structWSF PHPUNIT Tests Suites
    */
    public function upgradeStructWSFTestsSuites()
    {
      $currentWorkingDirectory = getcwd();
            
      $this->cecho("Upgrading tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/structwsftestssuites-install/');
      
      $this->chdir('/tmp/structwsftestssuites-install/');
      
      $this->exec('wget -q https://github.com/structureddynamics/structWSF-Tests-Suites/archive/master.zip');
      
      $this->chdir('/tmp/structwsftestssuites-install/structWSF-Tests-Suites-master/StructuredDynamics/structwsf/');

      $this->exec('rm -rf '.$this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $this->exec('cp -f tests '.$this->structwsf_folder.'/StructuredDynamics/structwsf/');
                  
      $this->cecho("Configure the tests suites...\n", 'WHITE');

      $this->chdir($this->structwsf_folder.'/StructuredDynamics/structwsf/tests/');
      
      $return = $this->getInput("What is the domain name where the structWSF instance is accessible (default: ".$this->structwsf_domain.")");

      if($return != '')
      {
        $this->structwsf_domain = $return;        
      }
      
      $return = $this->getInput("What is the structwsf installation folder (default: ".$this->structwsf_folder.")");

      if($return != '')
      {
        $this->structwsf_folder = $return;        
      }
      
      $this->exec('sed -i "s>REPLACEME>'.$this->structwsf_folder.'/StructuredDynamics/structwsf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \"'.$this->structwsf_folder.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->structwsf_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->structwsf_domain.'/wsf/ws/\";>" Config.php');      
      
      $this->chdir($currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/structwsftestssuites-install/');
    }    

    /**
    * Install the Ontologies Management Tool
    */
    public function installOntologiesManagementTool()
    {
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
      $this->exec('mkdir /tmp/omt');

      $this->cecho("Downloading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/omt https://github.com/structureddynamics/structWSF-Ontologies-Management-Tool/archive/master.zip');

      $this->cecho("Installing the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/master.zip -d /tmp/omt/');      
      
      $this->exec('mkdir '.$this->ontologies_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/omt/structWSF-Ontologies-Management-Tool-master/* '.$this->ontologies_management_tool_folder.'/');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/omt/');      
    }
    
    /**
    * Update an Ontologies Management Tool installation
    */
    public function upgradeOntologiesManagementTool()
    {
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
      $this->exec('mkdir /tmp/omt');

      $this->cecho("Downloading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/omt https://github.com/structureddynamics/structWSF-Ontologies-Management-Tool/archive/master.zip');

      $this->cecho("Upgrading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/master.zip -d /tmp/omt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the sync.ini file
      $this->exec('rm -rf /tmp/omt/structWSF-Ontologies-Management-Tool-master/sync.ini');
      
      $this->exec("cp -af /tmp/omt/structWSF-Ontologies-Management-Tool-master/* ".$this->ontologies_management_tool_folder."/");

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/omt/');      
    }
    
    public function runStructWSFTestsSuites($installationFolder = '')
    {
      if($installationFolder == '')
      {
        $return = $this->getInput("What is the structwsf installation folder (default: ".$this->structwsf_folder.")");

        if($return != '')
        {
          $installationFolder = $return;        
        }
        else
        {
          $installationFolder = $this->structwsf_folder;
        }
      }
      
      $currentWorkingDirectory = getcwd();
      
      $this->chdir($installationFolder.'/StructuredDynamics/structwsf/tests/');
      
      passthru('phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml');
      
      $this->chdir($currentWorkingDirectory);      
    }
    
    /**
    * Execute a shell command. The command is also logged into the logging file.
    *     
    * @param mixed $command the shell command to execute
    * 
    * @return Returns the exit code of the executed shell command
    */
    public function exec($command)
    {
      $output = array();
      $this->log(array($command), TRUE);      
      exec($command, $output, $return);
      $this->log($output);      
      
      return($return);
    }
    
    
    /**
    * Change the current folder of the script. The command is also logged into the logging file.
    *     
    * @param mixed $dir folder path where to go

    */
    public function chdir($dir)
    {
      $this->log(array('cd '.$dir), TRUE);      
      chdir($dir);
    }    
    
    /**
    * Colorize an output to the shell terminal.
    * 
    * @param mixed $text Text to echo into the terminal screen
    * @param mixed $color Color to use
    * @param mixed $return specify if we want to return the colorized text to the script instead of the terminal
    */
    public function cecho($text, $color = "NORMAL", $return = FALSE)
    {
      // Log the text
      file_put_contents($this->log_file, $text, FILE_APPEND);
      
      $_colors = array(
        'LIGHT_RED'    => "[1;31m",
        'LIGHT_GREEN'  => "[1;32m",
        'YELLOW'       => "[1;33m",
        'LIGHT_BLUE'   => "[1;34m",
        'MAGENTA'      => "[1;35m",
        'LIGHT_CYAN'   => "[1;36m",
        'WHITE'        => "[1;37m",
        'NORMAL'       => "[0m",
        'BLACK'        => "[0;30m",
        'RED'          => "[0;31m",
        'GREEN'        => "[0;32m",
        'BROWN'        => "[0;33m",
        'BLUE'         => "[0;34m",
        'CYAN'         => "[0;36m",
        'BOLD'         => "[1m",
        'UNDERSCORE'   => "[4m",
        'REVERSE'      => "[7m",
      );    
      
      $out = $_colors["$color"];
      
      if($out == "")
      { 
        $out = "[0m"; 
      }
      
      if($return)
      {
        return(chr(27)."$out$text".chr(27)."[0m");
      }
      else
      {
        echo chr(27)."$out$text".chr(27).chr(27)."[0m";
      }
    }
    
    /**
    * Log information into the logging file
    * 
    * @param mixed $lines An array of lines to log into the logging file
    * @param mixed $forceSilence Specify if we want to overwrite the verbosity of 
    *                            the script and make sure that log() stay silent.
    */
    public function log($lines, $forceSilence=FALSE)
    {
      foreach($lines as $line)
      {
        file_put_contents($this->log_file, $line."\n", FILE_APPEND);

        if($this->verbose && !$forceSilence)
        {
          $this->cecho($line."\n", 'BLUE');
        }
      }
    }  
    
    /**
    * Enable the verbosity of the class. Everything get outputed to the shell terminal
    */
    public function verbose()
    {
      $this->verbose = TRUE;      
    }  
    
    /**
    * Disable the verbosity of the class. No command output will be displayed to the terminal.
    */
    public function silent()
    {
      $this->verbose = FALSE;
    }

    /**
    * Prompt the user with a question, wait for input, and return that input from the user.
    *     
    * @param mixed $msg Message to display to the user before waiting for an answer.
    * 
    * @return Returns the answer of the user.
    */
    public function getInput($msg)
    {
      fwrite(STDOUT, $this->cecho("$msg: ", 'MAGENTA', TRUE));
      $varin = trim(fgets(STDIN));
      
      $this->log(array("[USER-INPUT]: ".$varin."\n"), TRUE);
      
      return $varin;
    }       
    
    /**
    * Check if the answer of an input is equivalent to "yes". The strings that are equivalent to "yes" are:    
    * "1", "true", "on", "y" and "yes". Returns FALSE otherwise. 
    * 
    * @param mixed $input Input to test
    * 
    * @param Returns TRUE if the input is equivalent to "yes", FALSE otherwise
    */
    public function isYes($input) 
    {
      $input = strtolower($input);
      
      $answer = filter_var($input, FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
      
      if($input === NULL)
      {
        return(FALSE);
      }  
      
      if($input == 'y')      
      {
        return(TRUE);
      }
      
      return($answer);
    }
  }
?>
