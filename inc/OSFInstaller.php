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
    public function installOSF()
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
      
      $this->installOSFWSPHPAPI();
      $this->installPermissionsManagementTool();
      $this->installDatasetsManagementTool();
      $this->switchOntologiesManagementTool('install');

      $this->installOSFWebServices();      
      
      $this->cecho("Now that the OSF instance is installed, you can install OSF for Drupal on the same server using this command:\n\n", 'CYAN');
      $this->cecho("    ./osf-installer --install-osf-drupal\n\n", 'CYAN');
    }
    
    /**
    * Install Drupal and the OSF Drupal modules
    */
    abstract public function installOSFDrupal();  

    /**
    * Install the OSF-WS-PHP-API library
    * 
    */
    public function installOSFWSPHPAPI($pkgVersion = '')
    {
      // Get name, version and paths
      $pkgName = "WS-PHP-API";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->osf_ws_php_api_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_ws_php_api_folder}";
      $tmpPath = "/tmp/osf/ws-php-api";

      $this->h1("Installing {$pkgName} {$pkgVersion}");
      // Check if is installed
      if (is_dir("{$installPath}/php/")) {
        $this->cecho("The {$pkgName} {$pkgVersion} is already installed. Consider upgrading it with the option: --upgrade-osf-ws-php-api\n", 'YELLOW');
        return;
      }

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Web-Services-PHP-API/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Web-Services-PHP-API-{$pkgVersion}/StructuredDynamics/osf/.", "{$installPath}/", TRUE);

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
    * Upgrade a OSF-WS-PHP-API installation
    */
    public function upgradeOSFPHPAPI($version = '')
    {
      if($version == '')
      {
        $version = $this->osf_ws_php_api_version;
      }
      elseif($version == 'dev')
      {
        $version = 'master';
      }      
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho(" Upgrading OSF-WS-PHP-API \n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE'); 
      
      $backupFolder = '/tmp/osfwsphpapi-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('mv '.$this->osf_web_services_folder.'/StructuredDynamics/osf/php/ '.$backupFolder);
      
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/osfwsphpapi');

      $this->cecho("Downloading the latest code of the OSF-WS-PHP-API...\n", 'WHITE');
      $this->wget('https://github.com/structureddynamics/OSF-Web-Services-PHP-API/archive/'.$version.'.zip', '/tmp/osfwsphpapi');
      
      $this->cecho("Upgrading the OSF-WS-PHP-API...\n", 'WHITE');
      $this->exec('unzip -o /tmp/osfwsphpapi/'.$version.'.zip -d /tmp/osfwsphpapi/');      
      
      if(!is_dir($this->osf_web_services_folder.'/'))
      {
        $this->exec('mkdir -p '.$this->osf_web_services_folder.'/');      
      }
      
      $this->exec('rm -f /tmp/osfwsphpapi/OSF-Web-Services-PHP-API-'.$version.'/StructuredDynamics/osf/framework/namespaces.csv');
      
      $this->exec('cp -af /tmp/osfwsphpapi/OSF-Web-Services-PHP-API-'.$version.'/StructuredDynamics '.$this->osf_web_services_folder.'/');

      $this->cecho("Cleaning upgrade folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/osfwsphpapi/');   
    }

    /**
    * Install the Datasets Management Tool
    */
    public function installDatasetsManagementTool($pkgVersion = '')
    {
      // Get name, version and paths
      $pkgName = "Datasets Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->datasets_management_tool_version;
          break;
      }
      $installPath = $this->datasets_management_tool_folder;
      $tmpPath = "/tmp/osf/dmt";

      $this->h1("Installing {$pkgName} {$pkgVersion}");
      // Check if is installed
      if (is_dir("{$installPath}/")) {
        $this->cecho("The {$pkgName} {$pkgVersion} is already installed. Consider upgrading it with the option: --upgrade-datasets-management-tool\n", 'YELLOW');
        return;
      }

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Datasets-Management-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Datasets-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/dmt", 755);
      $this->ln("{$installPath}/dmt", "/usr/bin/dmt");

      // Configure
      $this->h2("Configuring...");
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/dmt.ini");
      $this->sed("indexesFolder = \".*\"", "indexesFolder = \"{$installPath}/datasetIndexes/\"", "{$installPath}/dmt.ini");
      $this->sed("ontologiesStructureFiles = \".*\"", "ontologiesStructureFiles = \"{$this->data_folder}/ontologies/structure/\"", "{$installPath}/dmt.ini");
      $this->sed("missingVocabulary = \".*\"", "missingVocabulary = \"{$installPath}/missing/\"", "{$installPath}/dmt.ini");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
    * Install the Permissions Management Tool
    */
    public function installPermissionsManagementTool($pkgVersion = '')
    {
      // Get name, version and paths
      $pkgName = "Permissions Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->permissions_management_tool_version;
          break;
      }
      $installPath = $this->permissions_management_tool_folder;
      $tmpPath = "/tmp/osf/pmt";

      $this->h1("Installing {$pkgName} {$pkgVersion}");
      // Check if is installed
      if (is_dir("{$installPath}/")) {
        $this->cecho("The {$pkgName} {$pkgVersion} is already installed. Consider upgrading it with the option: --upgrade-permissions-management-tool\n", 'YELLOW');
        return;
      }

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Permissions-Management-Tool/archive/{$pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Permissions-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/pmt", 755);
      $this->ln("{$installPath}/pmt", "/usr/bin/pmt");

      // Configure
      $this->h2("Configuring...");
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/pmt.ini");
      $this->sed("osfWebServicesEndpointsUrl = \".*\"", "osfWebServicesEndpointsUrl = \"http://{$this->osf_web_services_domain}/ws/\"", "{$installPath}/pmt.ini");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
    * Install the Datasets Management Tool
    */
    public function installDataValidatorTool($pkgVersion = '')
    {
      // Get name, version and paths
      $pkgName = "Data Validator Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->data_validator_tool_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->data_validator_tool_folder}";
      $tmpPath = "/tmp/osf/dvt";

      $this->h1("Installing {$pkgName} {$pkgVersion}");
      // Check if is installed
      if (is_dir("{$installPath}/")) {
        $this->cecho("The {$pkgName} {$pkgVersion} is already installed. Consider upgrading it with the option: --upgrade-data-validator-tool\n", 'YELLOW');
        return;
      }

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Data-Validator-Tool/archive/{$pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Data-Validator-Tool-{$pkgVersion}/StructuredDynamics/osf/validator/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/dvt", 755);
      $this->ln("{$installPath}/dvt", "/usr/bin/dvt");

      // Configure
      $this->h2("Configuring...");
      $this->sed("folder = \".*\"", "folder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/dvt.ini");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
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
      elseif($version == 'dev')
      {
        $version = 'master';
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
      $this->wget('https://github.com/structureddynamics/OSF-Datasets-Management-Tool/archive/'.$version.'.zip', '/tmp/dmt');

      $this->cecho("Upgrading the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/'.$version.'.zip -d /tmp/dmt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the dmt.ini file
      $this->exec('rm -rf /tmp/dmt/OSF-Datasets-Management-Tool-'.$version.'/data/');
      $this->exec('rm -rf /tmp/dmt/OSF-Datasets-Management-Tool-'.$version.'/missing/');
      $this->exec('rm -rf /tmp/dmt/OSF-Datasets-Management-Tool-'.$version.'/datasetIndexes/');
      $this->exec('rm -f /tmp/dmt/OSF-Datasets-Management-Tool-'.$version.'/dmt.ini');      
      
      $this->exec("cp -af /tmp/dmt/OSF-Datasets-Management-Tool-".$version."/* ".$this->datasets_management_tool_folder."/");

      // Make "dmt" executable
      $this->exec('chmod 755 '.$this->datasets_management_tool_folder.'/dmt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }    
    
    /**
    * Upgrade a Permissions Management Tool installation
    */
    public function upgradePermissionsManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->permissions_management_tool_version;
      }  
      elseif($version == 'dev')
      {
        $version = 'master';
      }
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-------------------------------------------\n", 'WHITE');
      $this->cecho(" Upgrading the Permissions Management Tool \n", 'WHITE');
      $this->cecho("-------------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $backupFolder = '/tmp/pmt-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$this->permissions_management_tool_folder.'/ '.$backupFolder);
                                              
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/pmt');

      $this->cecho("Downloading the Permissions Management Tool...\n", 'WHITE');
      $this->wget('https://github.com/structureddynamics/OSF-Permissions-Management-Tool/archive/'.$version.'.zip', '/tmp/pmt');

      $this->cecho("Upgrading the Permissions Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/pmt/'.$version.'.zip -d /tmp/pmt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the pmt.ini file
      $this->exec('rm -f /tmp/pmt/OSF-Permissions-Management-Tool-'.$version.'/pmt.ini');      
      
      $this->exec("cp -af /tmp/pmt/OSF-Permissions-Management-Tool-".$version."/* ".$this->permissions_management_tool_folder."/");

      // Make "pmt" executable
      $this->exec('chmod 755 '.$this->permissions_management_tool_folder.'/pmt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/pmt/');      
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
      elseif($version == 'dev')
      {
        $version = 'master';
      }
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------------------\n", 'WHITE');
      $this->cecho(" Upgrading the Data Validator Tool \n", 'WHITE');
      $this->cecho("-----------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $dataValidatorFolder = $this->osf_web_services_folder.'/StructuredDynamics/osf/validator/';
      
      $backupFolder = '/tmp/dvt-'.date('Y-m-d_H-i-s');  
      
      $this->cecho("Moving old version into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$dataValidatorFolder.' '.$backupFolder);
                                              
      $this->cecho("Preparing upgrade...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dvt');

      $this->cecho("Downloading the Data Validator Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dvt https://github.com/structureddynamics/OSF-Data-Validator-Tool/archive/'.$version.'.zip');

      $this->cecho("Upgrading the Data Validator Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dvt/'.$version.'.zip -d /tmp/dvt/');      
      
      $this->exec('cp -af /tmp/dvt/OSF-Data-Validator-Tool-'.$version.'/StructuredDynamics/osf/validator/* '.$dataValidatorFolder);

      // Make "dvt" executable
      $this->exec('chmod 755 '.$dataValidatorFolder.'dvt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dvt/');      
    }     
    
    /**
    * Install OSF Web Services
    */
    public function installOSFWebServices($version='')
    {
      if($version == '')
      {
        $version = $this->osf_web_services_version;
      }
      elseif($version == 'dev')
      {
        $version = 'master';
      }
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("---------------------------------\n", 'WHITE');
      $this->cecho(" Installing the OSF Web Services \n", 'WHITE');
      $this->cecho("---------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
  
      if(is_dir($this->osf_web_services_folder.'/StructuredDynamics/osf/ws/'))                
      {
        $this->cecho("The OSF Web Services are already installed. Consider upgrading it with the option: --upgrade-osf-web-services\n", 'YELLOW');
        
        return;
      } 
      
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/osf-web-services-install');

      $this->cecho("Downloading the OSF Web Services...\n", 'WHITE');
      $this->wget('https://github.com/structureddynamics/OSF-Web-Services/archive/'.$version.'.zip', '/tmp/osf-web-services-install');

      $this->cecho("Installing the OSF Web Services...\n", 'WHITE');
      $this->exec('unzip -o /tmp/osf-web-services-install/'.$version.'.zip -d /tmp/osf-web-services-install/');      
      
      $this->exec('mkdir -p '.$this->osf_web_services_folder.'/');      
      
      $this->exec('cp -af /tmp/osf-web-services-install/OSF-Web-Services-'.$version.'/* '.$this->osf_web_services_folder.'/');

      $this->cecho("Configuring the OSF Web Services...\n", 'WHITE');
      
      //$this->cecho("Fixing the index.php file to refer to the proper SID folder...\n", 'WHITE');

      //$this->exec('sed -i \'s>$sidDirectory = "";>$sidDirectory = "/osf-web-services/tmp/";>\' "'.$this->osf_web_services_folder.'/index.php"');

      $this->cecho("Configure Apache2 for the OSF Web Services...\n", 'WHITE');
      
      $this->exec('cp resources/osf-web-services/osf-web-services /etc/apache2/sites-available/osf-web-services.conf');

      $this->exec('sudo ln -s /etc/apache2/sites-available/osf-web-services.conf /etc/apache2/sites-enabled/osf-web-services.conf');
      
      // Fix the OSF Web Services path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/osf>'.$this->osf_web_services_folder.$this->osf_web_services_ns.'>" "/etc/apache2/sites-available/osf-web-services.conf"');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Configure the osf.ini configuration file...\n", 'WHITE');

      $this->cecho("Make sure the OSF Web Services are aware of themselves by changing the hosts file...\n", 'WHITE');
      
      if(stripos(file_get_contents('/etc/hosts'), 'OSF-Installer') == FALSE)
      {
        file_put_contents('/etc/hosts', "\n\n# Added by the OSF-Installer to make the OSF Web Services are aware of themselves\n127.0.0.1 ".$this->osf_web_services_domain, FILE_APPEND);
      }       
      
      $channel = '';     
      
      while($channel != 'odbc' &&
            $channel != 'http')
      {
        $channel = $this->getInput("What SPARQL communication channel do you want to use: 'odbc' or 'http'");        
      }
      
      $this->exec('sed -i "s>channel = \"odbc\">channel = \"'.$channel.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"'); 
      
      if($channel == 'http')
      {
        $this->exec('sed -i "s>sparql-insert = \"virtuoso\">sparql-insert = \"insert\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"'); 
      }
            
      // fix wsf_graph
      $this->exec('sed -i "s>wsf_graph = \"http://localhost/wsf/\">wsf_graph = \"http://'.$this->osf_web_services_domain.'/wsf/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix dtd_base
      $this->exec('sudo sed -i "s>dtd_base = \"http://localhost/ws/dtd/\">dtd_base = \"http://'.$this->osf_web_services_domain.'/ws/dtd/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix ontologies_files_folder
      $this->exec('sudo sed -i "s>ontologies_files_folder = \"/data/ontologies/files/\">ontologies_files_folder = \""'.$this->data_folder.'"/ontologies/files/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix ontological_structure_folder
      $this->exec('sudo sed -i "s>ontological_structure_folder = \"/data/ontologies/structure/\">ontological_structure_folder = \"'.$this->data_folder.'/ontologies/structure/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix password
      $this->exec('sudo sed -i "s>password = \"dba\">password = \"'.$this->dbaPassword.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix host
      $this->exec('sudo sed -i "s>host = \"localhost\">host = \"'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');
      $this->exec('sudo sed -i "s>solr_host = \"localhost\">solr_host = \"'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix fields_index_folder
      $this->exec('sudo sed -i "s>fields_index_folder = \"/tmp/\">fields_index_folder = \"'.$this->data_folder.'/osf-web-services/tmp/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');
      
      // fix wsf_base_url
      $this->exec('sudo sed -i "s>wsf_base_url = \"http://localhost\">wsf_base_url = \"http://'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix wsf_base_path
      $this->exec('sudo sed -i "s>wsf_base_path = \"/usr/share/osf/StructuredDynamics/osf/ws/\">wsf_base_path = \"'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      $this->cecho("Create the OSF Web Services tmp folder...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$this->data_folder.'/osf-web-services/tmp/');
      $this->exec('mkdir -p '.$this->data_folder.'/osf-web-services/configs/');

      // Always geo-enable an instance
      $this->exec('sudo sed -i "s>geoenabled = \"false\">geoenabled = \"true\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      $appID = 'administer';
      
      $return = $this->getInput("What is the first Application ID of the OSF Web Services network you want to create? This key will be used by the PMT, DMT, OMT and OSF for Drupal tools. Only use alpha numeric characters *without* spaces (default: ".$appID.")");
      
      if($return != '')
      {
        $appID = $return;
        
        $appID = preg_replace('/[^A-Za-z0-9]/i', '', $appID);
      }  
      
      $apiKey = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));
      
      $this->exec('sudo sed -i "s>administer = \"some-key\">'.$appID.' = \"'.$apiKey.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/keys.ini"');              
      
      $this->cecho("\n................................................................\n", 'CYAN');
      $this->cecho("The API Key of the '".$appID."' application ID is: '".$apiKey."'  \n", 'CYAN');
      $this->cecho("................................................................\n\n", 'CYAN');

      $this->application_id = $appID;
      $this->api_key = $apiKey;
      
      $this->cecho("Configuring the application ID and the API Key for the PMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>application-id = \"administer\">application-id = \"'.$appID.'\">" "'.$this->permissions_management_tool_folder.'/pmt.ini"');
      $this->exec('sudo sed -i "s>api-key = \"some-key\">api-key = \"'.$apiKey.'\">" "'.$this->permissions_management_tool_folder.'/pmt.ini"');
      $this->exec('sudo sed -i "s>user = \"http://localhost/wsf/users/admin\">user = \"http://'.$this->osf_web_services_domain.'/wsf/users/admin\">" "'.$this->permissions_management_tool_folder.'/pmt.ini"');

      $this->cecho("Configuring the application ID and the API Key for the DMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>application-id = \"administer\">application-id = \"'.$appID.'\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      $this->exec('sudo sed -i "s>api-key = \"some-key\">api-key = \"'.$apiKey.'\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      $this->exec('sudo sed -i "s>user = \"http://localhost/wsf/users/admin\">user = \"http://'.$this->osf_web_services_domain.'/wsf/users/admin\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');

      $this->cecho("Configuring the application ID and the API Key for the OMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>application-id = \"administer\">application-id = \"'.$appID.'\">" "'.$this->ontologies_management_tool_folder.'/omt.ini"');
      $this->exec('sudo sed -i "s>api-key = \"some-key\">api-key = \"'.$apiKey.'\">" "'.$this->ontologies_management_tool_folder.'/omt.ini"');      
      $this->exec('sudo sed -i "s>user = \"http://localhost/wsf/users/admin\">user = \"http://'.$this->osf_web_services_domain.'/wsf/users/admin\">" "'.$this->ontologies_management_tool_folder.'/omt.ini"');
      $this->exec('sudo sed -i "s>group = \"http://localhost/wsf/groups/administrators\">group = \"http://'.$this->osf_web_services_domain.'/wsf/groups/administrators\">" "'.$this->ontologies_management_tool_folder.'/omt.ini"');
      
      $this->cecho("Move the osf.ini and keys.ini files outside of the web root...\n", 'WHITE');

      $this->exec('mv '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini '.$this->data_folder.'/osf-web-services/configs/osf.ini');
      $this->exec('mv '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/keys.ini '.$this->data_folder.'/osf-web-services/configs/keys.ini');
      
      $this->exec('chown -R www-data:www-data '.$this->data_folder.'/osf-web-services/');
      $this->exec('chmod -R 500 '.$this->data_folder.'/osf-web-services/');      
      $this->exec('chmod -R 700 '.$this->data_folder.'/osf-web-services/tmp/');      
      
      $this->cecho("Configure the WebService.php file...\n", 'WHITE');

      $this->exec('sed -i \'s>public static $osf_ini = "/usr/share/osf/StructuredDynamics/osf/ws/";>public static $osf_ini = "'.$this->data_folder.'/osf-web-services/configs/";>\' "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/WebService.php"');
      $this->exec('sed -i \'s>public static $keys_ini = "/usr/share/osf/StructuredDynamics/osf/ws/";>public static $keys_ini = "'.$this->data_folder.'/osf-web-services/configs/";>\' "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/WebService.php"');      
      
      $this->cecho("Install the Solr schema for the OSF Web Services...\n", 'WHITE');
      
      if(!file_exists('/usr/share/solr/osf-web-services/solr/conf/schema.xml'))
      {
        $this->cecho("Solr is not yet installed. Install Solr using this --install-solr option and then properly configure its schema by hand.\n", 'WHITE');
      }
      else
      {
        $this->exec('cp -f '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/solr_schema_v1_3_2.xml /usr/share/solr/osf-web-services/solr/conf/schema.xml');
        
        $this->cecho("Restarting Solr...\n", 'WHITE');
        $this->exec('/etc/init.d/solr stop');
        $this->exec('/etc/init.d/solr start');
      }
      
      $this->cecho("Installing ARC2...\n", 'WHITE');
      
      $this->chdir($this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/arc2/');
      
      $this->wget('https://github.com/semsol/arc2/archive/v2.1.1.zip');
      
      $this->exec('unzip v2.1.1.zip');
      
      $this->chdir($this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/arc2/arc2-2.1.1/');
      
      $this->exec('mv * ../');
      
      $this->chdir($this->osf_web_services_folder.$this->osf_web_services_ns.'/framework/arc2/');
      
      $this->exec('rm -rf arc2-2.1.1');
      
      $this->exec('rm v*.zip*');
      
      $this->chdir($this->currentWorkingDirectory);
      
      
      $this->cecho("Installing OWLAPI requirements...", 'WHITE');
      
      $this->exec('apt-get -y install tomcat6');
      
      $this->exec('/etc/init.d/tomcat6 stop');
      
      $this->cecho("Downloading OWLAPI...\n", 'WHITE');
      
      $this->chdir('/var/lib/tomcat6/webapps/');
      
      $this->wget('http://wiki.opensemanticframework.org/files/OWLAPI.war');
      
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
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('sed -i \'s>server_address = "">server_address = "http://'.$this->osf_web_services_domain.'">\' "resources/virtuoso/initialize_osf_web_services_network.php"');
      $this->exec('sed -i \'s>appID = "administer">appID = "'.$this->application_id.'">\' "resources/virtuoso/initialize_osf_web_services_network.php"');
      
      $errors = shell_exec('php resources/virtuoso/initialize_osf_web_services_network.php');
      
      if(!$this->init_osf($this->dbaPassword))
      {
        $this->cecho("\n\nThe OSF Web Services Network couldn't be created. Major Error.\n", 'RED');
      }        
      
      $this->cecho("Commit transactions to Virtuoso...\n", 'WHITE');      

      if(!$this->commit($this->dbaPassword))
      {
        $this->cecho("Couldn't commit triples to the Virtuoso triples store...\n", 'YELLOW');
      }
      
      $this->cecho("Create Data & Ontologies folders...\n", 'WHITE');
      
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/files/"');
      $this->exec('mkdir -p "'.$this->data_folder.'/ontologies/structure/"');

      $this->cecho("Download the core OSF ontologies files...\n", 'WHITE');

      $this->chdir($this->data_folder.'/ontologies/files');
            
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/aggr/aggr.owl');      
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/iron/iron.owl');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/owl/owl.rdf');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdf.xml');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdfs.xml');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/sco/sco.owl');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wgs84/wgs84.owl');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wsf/wsf.owl');
      $this->wget('https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/drupal/drupal.owl');
      
      // Need to setup the initial classes & properties hierarchies serialization files
      $this->chdir($this->data_folder.'/ontologies/structure/');

      $this->exec('cp '.rtrim($this->currentWorkingDirectory, '/').'/resources/osf-web-services/classHierarchySerialized.srz classHierarchySerialized.srz');      
      $this->exec('cp '.rtrim($this->currentWorkingDirectory, '/').'/resources/osf-web-services/propertyHierarchySerialized.srz propertyHierarchySerialized.srz');      
      
      $this->cecho("Load ontologies...\n", 'WHITE');
      
      $this->chdir($this->ontologies_management_tool_folder);

      
      $this->exec('sudo sed -i "s>file://localhost/data>file://localhost'.rtrim($this->data_folder, '/').'>g" "'.rtrim($this->currentWorkingDirectory, '/').'/resources/osf-web-services/ontologies.lst"');
      
      $this->exec('omt --load-advanced-index="true" --load-all --load-list="'.rtrim($this->currentWorkingDirectory, '/').'/resources/osf-web-services/ontologies.lst" --osf-web-services="http://'.$this->osf_web_services_domain.'/ws/"');

      $this->cecho("Create underlying ontological structures...\n", 'WHITE');
      
      $this->exec('omt --generate-structures="'.$this->data_folder.'/ontologies/structure/" --osf-web-services="http://'.$this->osf_web_services_domain.'/ws/"');

      $this->installOSFTestsSuites();

      $this->chdir($this->currentWorkingDirectory);

      
      $this->cecho("Set files owner permissions...\n", 'WHITE');
      
      $this->exec('chown -R www-data:www-data '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/');
      $this->exec('chmod -R 755 '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/osf-web-services-install/');  
      
      //PHPUnit
      // Get name, version and paths
      $pkgName = "PHPUnit";
      $installPath = "/usr/local/bin";
      $tmpPath = "/tmp/osf/phpunit";

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://phar.phpunit.de/phpunit.phar", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->cp("{$tmpPath}/phpunit.phar", "{$installPath}/phpunit", FALSE);
      $this->chmod("{$installPath}/phpunit", "+x");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
      
      $this->runOSFTestsSuites($this->osf_web_services_folder);
    }    

    /**
    * Install the OSF PHPUNIT Tests Suites
    */
    public function installOSFTestsSuites($pkgVersion = '')
    {
      // Get name, version and paths
      $pkgName = "Tests suites";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->osf_tests_suites_version;
          break;
      }
      $installPath = "{$this->osf_web_services_folder}/{$this->osf_tests_suites_folder}";
      $tmpPath = "/tmp/osf/tests";

      $this->h1("Installing {$pkgName} {$pkgVersion}");
      // Check if is installed
      if (is_dir("{$installPath}/")) {
        $this->cecho("The {$pkgName} {$pkgVersion} is already installed. Consider upgrading it with the option: --upgrade-osf-tests-suites\n", 'YELLOW');
        return;
      }

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Tests-Suites/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Tests-Suites-{$pkgVersion}/StructuredDynamics/osf/tests/.", "{$installPath}/", TRUE);

      // Configure
      $this->h2("Configuring...");
      $this->sed("REPLACEME", "{$osf_web_services_folder}/StructuredDynamics/osf", "{$installPath}/phpunit.xml");
      $this->sed("\$this-\>osfInstanceFolder = \".*\";", "\$this-\>osfInstanceFolder = \"{$osf_web_services_folder}/{$osf_web_services_ns}/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>endpointUrl = \".*/ws/\";", "\$this-\>endpointUrl = \"http://{$osf_web_services_domain}/ws/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>endpointUri = \".*/wsf/ws/\";", "\$this-\>endpointUri = \"http://{$osf_web_services_domain}/wsf/ws/\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>userID = '.*/wsf/users/tests-suites';", "\$this-\>userID = 'http://{$osf_web_services_domain}/wsf/users/tests-suites';", "{$installPath}/Config.php");
      $this->sed("\$this-\>adminGroup = '.*/wsf/groups/administrators';", "\$this-\>adminGroup = 'http://{$osf_web_services_domain}/wsf/groups/administrators';", "{$installPath}/Config.php");
      $this->sed("\$this-\>testGroup = \".*/wsf/groups/unittests\";", "\$this-\>testGroup = \"http://{$osf_web_services_domain}/wsf/groups/unittests\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>testUser = \".*/wsf/users/unittests\";", "\$this-\>testUser = \"http://{$osf_web_services_domain}/wsf/users/unittests\";", "{$installPath}/Config.php");
      $this->sed("\$this-\>applicationID = '.*';", "\$this-\>applicationID = '{$appID}';", "{$installPath}/Config.php");
      $this->sed("\$this-\>apiKey = '.*';", "\$this-\>apiKey = '{$apiKey}';", "{$installPath}/Config.php");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
    * Switch for Ontologies Management Tool
    */
    public function switchOntologiesManagementTool($op = 'install', $pkgVersion = '')
    {
      // Get package info
      $pkgName = "Ontologies Management Tool";
      switch ($pkgVersion) {
        case 'dev':
          $pkgVersion = 'master';
          break;
        default:
          $pkgVersion = $this->ontologies_management_tool_version;
          break;
      }
      $installPath = $this->ontologies_management_tool_folder;

      // Check operation mode
      switch ($op) {
        case 'install':
          $this->h1("Installing {$pkgName} {$pkgVersion}");
          // Check if is installed
          if (is_dir("{$installPath}/")) {
            $this->cecho("The package is already installed. Consider upgrading it with the option: --upgrade-ontologies-management-tool\n", 'YELLOW');
            return;
          }
          $this->installOntologiesManagementTool($pkgVersion);
          $this->configOntologiesManagementTool();
          break;
        case 'upgrade':
          $this->h1("Upgrading {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->cecho("The package is not installed. Consider installing it with the option: --install-ontologies-management-tool\n", 'YELLOW');
            return;
          }
          $this->upgradeOntologiesManagementTool($pkgVersion);
          break;
        case 'uninstall':
          $this->h1("Uninstalling {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->cecho("The package is not installed.\n", 'YELLOW');
            return;
          }
          $this->uninstallOntologiesManagementTool($pkgVersion);
          break;
        case 'configure':
          $this->h1("Configuring {$pkgName} {$pkgVersion}");
          // Check if is not installed
          if (!is_dir("{$installPath}/")) {
            $this->cecho("The package is not installed.\n", 'YELLOW');
            return;
          }
          $this->configOntologiesManagementTool($pkgVersion);
          break;
        default:
          break;
      }
    }

    /**
    * Install Ontologies Management Tool
    */
    private function installOntologiesManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = $this->ontologies_management_tool_folder;
      $tmpPath = "/tmp/osf/omt";

      // Download
      $this->h2("Downloading...");
      $this->mkdir("{$tmpPath}/");
      $this->wget("https://github.com/structureddynamics/OSF-Ontologies-Management-Tool/archive/${pkgVersion}.zip", "{$tmpPath}/");

      // Install
      $this->h2("Installing...");
      $this->unzip("{$tmpPath}/{$pkgVersion}.zip", "{$tmpPath}/");
      $this->mkdir("{$installPath}/");
      $this->cp("{$tmpPath}/OSF-Ontologies-Management-Tool-{$pkgVersion}/.", "{$installPath}/", TRUE);
      $this->chmod("{$installPath}/omt", 755);
      $this->ln("{$installPath}/omt", "/usr/bin/omt");

      // Cleanup
      $this->h2("Cleaning...");
      $this->rm("{$tmpPath}/", TRUE);
    }

    /**
    * Upgrade Ontologies Management Tool
    */
    private function upgradeOntologiesManagementTool($pkgVersion = '')
    {
      // Get package info
      $installPath = $this->ontologies_management_tool_folder;
      $bckPath = "/tmp/osf/omt-" . date('Y-m-d_H-i-s');

      // Backup
      $this->h2("Making backup...");
      $this->mkdir("{$bckPath}/");
      $this->mv("{$installPath}/.", "{$bckPath}/.");

      // Install
      $this->installOntologiesManagementTool($pkgVersion);

      // Restore
      $this->h2("Restoring backup...");
      $this->mv("{$bckPath}/omt.ini", "{$installPath}/");

      // Cleanup
      $this->h2("Cleaning backup...");
      $this->rm("{$bckPath}/", TRUE);
    }

    /**
    * Uninstall Ontologies Management Tool
    */
    private function uninstallOntologiesManagementTool()
    {
      // Get package info
      $installPath = $this->ontologies_management_tool_folder;

      // Uninstall
      $this->h2("Uninstalling...");
      $this->rm("{$installPath}/", TRUE);
      $this->rm("/usr/bin/omt");
    }

    /**
    * Configure Ontologies Management Tool
    */
    private function configOntologiesManagementTool()
    {
      // Get package info
      $installPath = $this->ontologies_management_tool_folder;

      // Configure
      $this->h2("Configuring...");
      $this->sed("osfWebServicesFolder = \".*\"", "osfWebServicesFolder = \"{$this->osf_web_services_folder}/\"", "{$installPath}/dmt.ini");
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
