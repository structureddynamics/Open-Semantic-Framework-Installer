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
      
      $this->installMemcached();
      
      $this->installOSFWSPHPAPI();
      $this->installPermissionsManagementTool();
      $this->installDatasetsManagementTool();
      $this->installOntologiesManagementTool();

      $this->installOSFWebServices();      
      
      $this->cecho("Now the the OSF instance is installed, you can install OSF for Drupal on the same server using this command:\n\n", 'CYAN');
      $this->cecho("    ./osf-installer --install-osf-drupal\n\n", 'CYAN');
      
      
    }
    
    /**
    * Install Drupal and the OSF Drupal modules
    */
    public function installOSFDrupal()
    {
      // Install Pear

      // First check if Pear is installed
      if($this->exec('pear', 'ignore') === FALSE)
      {
        $this->cecho("\n\n", 'WHITE');
        $this->cecho("-----------------\n", 'WHITE');
        $this->cecho(" Installing Pear \n", 'WHITE');
        $this->cecho("-----------------\n", 'WHITE');
        $this->cecho("\n\n", 'WHITE');

        $this->chdir('/tmp/');
                 
        $this->wget('http://pear.php.net/go-pear.phar');
      
        passthru('php go-pear.phar');
      }
      
      // Install Drush
      
      // Check if Drush is installed
      if($this->exec('drush', 'ignore') === FALSE)
      {
        $this->cecho("\n\n", 'WHITE');
        $this->cecho("------------------\n", 'WHITE');
        $this->cecho(" Installing Drush\n", 'WHITE');
        $this->cecho("------------------\n", 'WHITE');
        $this->cecho("\n\n", 'WHITE');

        $this->exec('pear upgrade --force Console_Getopt', 'warning');
        $this->exec('pear upgrade --force pear', 'warning');
        $this->exec('pear upgrade-all', 'warning');
        
        $this->exec('pear channel-discover pear.drush.org', 'warning');
        
        $this->exec('pear install drush/drush', 'warning');
      }
      
      // Install Drupal            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("--------------------------------\n", 'WHITE');
      $this->cecho(" Installing Drupal & OSF Drupal\n", 'WHITE');
      $this->cecho("--------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');      
      
      $this->chdir($this->currentWorkingDirectory);
      
      if($this->exec('dpkg-query -l git', 'ignore') === FALSE)
      {
        $this->exec('apt-get -y install git', 'error');
      }
      
      if($this->exec('dpkg-query -l php5-curl', 'ignore') === FALSE)
      {
        $this->exec('apt-get -y install php5-curl', 'error');
      }
      
      $this->exec('drush make --prepare-install resources/osf-drupal/osf_drupal.make '.$this->drupal_folder, 'error');
      
      // Configure/install Drupal   
      $mysqlUsername = 'root';     
      
      $return = $this->getInput("What is the username that Drupal should use connect to MySQL (default: $mysqlUsername)");

      if($return != '')
      {
        $mysqlUsername = $return;
      }  
      
      $mysqlPassword = 'root';     
      
      $return = $this->getInput("What is the password of the $mysqlUsername user to connect to MySQL (default: $mysqlPassword)");

      if($return != '')
      {
        $mysqlPassword = $return;
      }
      
      $mysqlDatabaseName = 'drupal7';     
      
      $return = $this->getInput("What is the name of the database to use to install Drupal in MySQL (default: $mysqlDatabaseName)");

      if($return != '')
      {
        $mysqlDatabaseName = $return;
      }      
      
      $drupalUsername = 'admin';     
      
      $return = $this->getInput("What is the username to use to connect to Drupal (default: $drupalUsername)");

      if($return != '')
      {
        $drupalUsername = $return;
      }  
      
      $drupalPassword = 'admin';     
      
      $return = $this->getInput("What is the password of the $drupalUsername user to connect to Drupal (default: $drupalPassword)");

      if($return != '')
      {
        $drupalPassword = $return;
      } 
      
      $this->chdir($this->drupal_folder);     
      
      passthru("drush site-install standard --account-name=$drupalUsername --account-pass=$drupalPassword --db-url=mysql://$mysqlUsername:$mysqlPassword@localhost/$mysqlDatabaseName -y");
      
      $this->cecho("\n", 'WHITE');
      
      $domainName = $this->getInput("What is the domain name where this Drupal portal will be accessible? (examples: mydomain.com, www.mydomain.com)");      
      
      $this->chdir($this->currentWorkingDirectory);
      
      // Configuring Apache2 for Drupal      
      $this->cecho("Configure Apache2 for Drupal...\n", 'WHITE');
      
      $this->exec('cp resources/osf-drupal/drupal /etc/apache2/sites-available/');

      $this->exec('sudo ln -s /etc/apache2/sites-available/drupal /etc/apache2/sites-enabled/drupal');
      
      // Fix the OSF Web Services path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/drupal>'.$this->drupal_folder.'>" "/etc/apache2/sites-available/drupal"');
      
      // Delete the default Apache2 enabled site file
      if(file_exists('/etc/apache2/sites-enabled/000-default'))
      {
        $this->exec('rm /etc/apache2/sites-enabled/000-default', 'warning');
      }
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');      

      // Install required file for OSF Ontology
      $this->exec('cp -af resources/osf-drupal/new.owl '.$this->data_folder.'/ontologies/files/new.owl');
      
      // Install the required files for the colorpicker module
      $this->chdir($this->drupal_folder.'/sites/all/libraries/');
      
      $this->exec('mkdir -p colorpicker');
      
      $this->chdir('colorpicker');
      
      $this->wget('http://www.eyecon.ro/colorpicker/colorpicker.zip');
      
      $this->exec('unzip colorpicker.zip');
      
      $this->exec('rm colorpicker.zip');

      // Creating OSF core Groups, Users and Permissions
      $this->cecho("Creating core Groups, Users and Permissions for Drupal in OSF...\n", 'WHITE');

      $appID = 'administer';
      
      $return = $this->getInput("What is the APP ID of the OSF Web Services network you want to use for this Drupal instance (default: ".$appID.")");
      
      if($return != '')
      {
        $appID = $return;
      }  
      
      // Create Drupal administrators group
      passthru('pmt --create-group="http://'.$domainName.'/role/3/administrator" --app-id="'.$appID.'"');
      
      // Create the Drupal administrator user
      passthru('pmt --register-user="http://'.$domainName.'/user/1" --register-user-group="http://'.$domainName.'/role/3/administrator"');
      
      // Create the permissions to the core datasets
      passthru('pmt --create-access --access-dataset="http://'.$this->osf_web_services_domain.'/wsf/" --access-group="http://'.$domainName.'/role/3/administrator" --access-perm-create="true"  --access-perm-read="true"  --access-perm-update="true"  --access-perm-delete="true" --access-all-ws');
      passthru('pmt --create-access --access-dataset="http://'.$this->osf_web_services_domain.'/wsf/datasets/" --access-group="http://'.$domainName.'/role/3/administrator" --access-perm-create="true"  --access-perm-read="true"  --access-perm-update="true"  --access-perm-delete="true" --access-all-ws');
      passthru('pmt --create-access --access-dataset="http://'.$this->osf_web_services_domain.'/wsf/ontologies/" --access-group="http://'.$domainName.'/role/3/administrator" --access-perm-create="true"  --access-perm-read="true"  --access-perm-update="true"  --access-perm-delete="true" --access-all-ws');
      
      // Create accesses to all loaded ontologies      
      $loadedOntologies = file_get_contents(rtrim($this->currentWorkingDirectory, '/').'/resources/osf-web-services/ontologies.lst');
      
      $loadedOntologies = explode(' ', $loadedOntologies);
      
      foreach($loadedOntologies as $loadedOntology)
      {
        passthru('pmt --create-access --access-dataset="'.$loadedOntology.'" --access-group="http://'.$domainName.'/role/3/administrator" --access-perm-create="true"  --access-perm-read="true"  --access-perm-update="true"  --access-perm-delete="true" --access-all-ws');
      }
      
      // Enable OSF Drupal modules      
      $this->chdir($this->drupal_folder);     
      
      $this->cecho("Enable OSF Drupal modules...\n", 'WHITE');
      passthru("drush en devel -y");
      passthru("drush en ctools -y");
      passthru("drush en entity -y");
      passthru("drush en entitycache -y");
      passthru("drush en entitycache -y");
      passthru("drush en features -y");
      passthru("drush en libraries -y");
      passthru("drush en jquery_colorpicker -y");
      passthru("drush en views -y");
      passthru("drush en context -y");
      passthru("drush en boxes -y");
      passthru("drush en xautoload -y");
      passthru("drush en search_api -y");
      passthru("drush en search_api_facetapi -y");
      passthru("drush en search_api_page -y");
      passthru("drush en diff -y");
      passthru("drush en entityreference -y");
      passthru("drush en revisioning -y");
      passthru("drush en osf -y");
      passthru("drush en osf_configure -y");
      /*
      // Registering the default endpoint
      $this->cecho("Registering the default OSF Web Services endpoint...\n", 'WHITE');
      
      $return = $this->getInput("What is the name of the OSF Web Services network to register (default: Local OSF Instance)");

      $registeredName = "Local OSF Instance";
      
      if($return != '')
      {
        $registeredName = $return;
      }       
      
      $return = $this->getInput("What is the base URL of the OSF Web Services endpoints you want to register (default: http://localhost/ws/)");

      $osfWebServicesEndpoint = "http://localhost/ws/";
      
      if($return != '')
      {
        $osfWebServicesEndpoint = $return;
      }   
           
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $osfWebServicesEndpoint);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $sid = curl_exec($ch);
      curl_close($ch);     
      
      if(!empty($sid))
      {
        $return = $this->getInput("What is the APP ID of the OSF Web Services network you want to use for this Drupal instance (default: administer)");

        $appID = 'administer';
        
        if($return != '')
        {
          $appID = $return;
        }  
        
        $return = $this->getInput("What is the APP KEY of the OSF Web Services network you want to use for this Drupal instance (default: some-key)");

        $appKey = 'some-key';
        
        if($return != '')
        {
          $appKey = $return;
        }                

        // Register the default network        
        passthru('drush sqlq \'INSERT INTO `drupal7`.`osf_configure_endpoints` (`sceid`, `label`, `machine_name`, `uri`, `sid`, `color`, `app_id`, `api_key`, `is_default`) VALUES (NULL, "'.$registeredName.'", "local_osf_webservices_instance", "'.$osfWebServicesEndpoint.'", "'.$sid.'", "A9C5EB", "'.$appID.'", "'.$appKey.'", "1");\'');
      }
      else
      {
        $this->cecho("The OSF Web Services base URL is not a valid endpoint. You will have to use Drupal's user interface to register one by hands...\n", 'YELLOW');
      }      
      */
      
      $this->cecho("You can safely ignore the following 4 errors related to 'illegal choice detected'...\n", 'YELLOW');
      passthru("drush en osf_searchapi -y");
      passthru("drush en osf_entities -y");
      passthru("drush en osf_permissions -y");
      passthru("drush en osf_fieldstorage -y");
      passthru("drush en osf_export -y");
      passthru("drush en osf_import -y");
      passthru("drush en osf_ontology -y");
      passthru("drush en osf_querybuilder -y");
      passthru("drush en osf_searchprofiles -y");
      passthru("drush en osf_field -y");
      passthru("drush dis overlay -y");
      
      // Create Drupal roles for OSF Drupal
      $this->cecho("Create Drupal roles for OSF Drupal...\n", 'WHITE');
      
      passthru("drush role-create 'contributor' -y");
      passthru("drush role-create 'owner/curator' -y");
      
      // Change the namespaces.csv file permissions on the server
      $this->exec("chmod 777 ".$this->drupal_folder."/sites/all/libraries/OSF-WS-PHP-API/StructuredDynamics/osf/framework/namespaces.csv", 'warning');

      // Setup OSF Ontology settings
      $this->cecho("Configure error level settings...\n", 'WHITE');
      passthru('drush vset error_level 1');
      
      // Configure domain name
      passthru('drush vset osf_UrisDomain "'.str_replace('http://', '', trim($domainName)).'"');
      
      // Setup OSF Ontology settings
      $this->cecho("Configure OSF Ontology settings...\n", 'WHITE');
      
      // Create the schemas folder used by OSF Ontology
      $this->exec('mkdir -p '.$this->drupal_folder.'/schemas/', 'warning');
      $this->exec('chmod 777 '.$this->drupal_folder.'/schemas/', 'warning');
      $this->exec('chmod 777 -R '.$this->data_folder.'/ontologies/', 'warning');
      
      // Configure OSF Entities
      $this->cecho("Configure OSF Entities settings...\n", 'WHITE');
      
      // Configure OSF SearchAPI settings
      $this->cecho("Configure OSF Search API settings...\n", 'WHITE');
      
      // Setup default interface
      passthru('drush vset osf_searchapi_settings_interface_name "DefaultSourceInterface"');
      passthru('drush vset osf_searchapi_settings_interface_version "3.0"');
      
      // Create the default search index, page and server
      passthru('drush sqlq \'INSERT INTO `search_api_server` (`id`, `name`, `machine_name`, `description`, `class`, `options`, `enabled`, `status`, `module`) VALUES (1, "OSF Search", "osf_search", "The server handler for OSF SearchAPI", "osf_searchapi_service", "a:1:{s:7:\"network\";s:'.strlen('http://'.$this->osf_web_services_domain.'/ws/').':\"http://'.$this->osf_web_services_domain.'/ws/\";}", 1, 1, NULL);\'');
      passthru('drush sqlq \'INSERT INTO `search_api_index` (`id`, `name`, `machine_name`, `description`, `server`, `item_type`, `options`, `enabled`, `read_only`, `status`, `module`) VALUES (2, "OSF Search Index", "osf_search_index", NULL, "osf_search", "osf", "a:3:{s:14:\"index_directly\";i:0;s:10:\"cron_limit\";s:2:\"50\";s:6:\"fields\";a:2:{s:2:\"id\";a:1:{s:4:\"type\";s:6:\"string\";}s:19:\"search_api_language\";a:1:{s:4:\"type\";s:6:\"string\";}}}", 1, 0, 1, NULL);\'');
      passthru('drush sqlq \'INSERT INTO `search_api_page` (`id`, `index_id`, `path`, `name`, `machine_name`, `description`, `options`, `enabled`, `status`, `module`) VALUES (1, "osf_search_index", "lookup", "OSF Search", "osf_search", "", "a:5:{s:4:\"mode\";s:5:\"terms\";s:6:\"fields\";a:0:{}s:8:\"per_page\";s:2:\"10\";s:12:\"get_per_page\";i:1;s:9:\"view_mode\";s:22:\"search_api_page_result\";}", 1, 1, NULL);\'');
      
      // Make sure the read mode is OFF
      passthru('drush vset osf_OntologySettings_read_mode 0');
      
      // Configure: "Ontologies Files Path folder"
      passthru('drush vset osf_OntologySettings_ontologies_files_folder "'.$this->data_folder.'/ontologies/files/"');

      // Configure: "Ontologies Cache Path folder"
      passthru('drush vset osf_OntologySettings_ontologies_cache_folder "'.$this->data_folder.'/ontologies/structure/"');
      
      // Configure: "Ontologies ironXML Schema Cache Path folder"
      passthru('drush vset osf_OntologySettings_ontologies_ironxml_cache_folder "'.$this->drupal_folder.'/schemas/"');
      
      // Configure: "Ontologies ironJSON Schema Cache Path folder"
      passthru('drush vset osf_OntologySettings_ontologies_ironjson_cache_folder "'.$this->drupal_folder.'/schemas/"');
      /*
      $this->cecho("Generating all the ontologies structures in Drupal (may take a few minutes)...\n", 'WHITE');
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $osfWebServicesEndpoint);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, 'network='.urlencode($osfWebServicesEndpoint).'&updateCachesInput=');
      curl_exec($ch);
      curl_close($ch); 
      */
      
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Now that OSF for Drupal is installed, the next steps would be to follow the Initial OSF for Drupal Configuration Guide:\n\n", 'CYAN');
      $this->cecho("    http://wiki.opensemanticframework.org/index.php/Initial_OSF_for_Drupal_Configuration\n\n", 'CYAN');
    }    
    
    /**
    * Install the OSF-WS-PHP-API library
    * 
    */
    public function installOSFWSPHPAPI($version = '')
    {                                                  
      if($version == '')
      {
        $version = $this->osf_ws_php_api_version;
      }
                    
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("---------------------------\n", 'WHITE');
      $this->cecho(" Installing OSF-WS-PHP-API \n", 'WHITE');
      $this->cecho("---------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      if(is_dir($this->osf_web_services_folder.'/StructuredDynamics/osf/php/'))                
      {
        $this->cecho("The OSF-WS-PHP-API is already installed. Consider upgrading it with the option: --upgrade-osf-ws-php-api\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/osfwsphpapi');

      $this->cecho("Downloading the OSF-WS-PHP-API...\n", 'WHITE');
      $this->wget('https://github.com/structureddynamics/OSF-Web-Services-PHP-API/archive/'.$version.'.zip','/tmp/osfwsphpapi');

      $this->cecho("Installing the OSF-WS-PHP-API...\n", 'WHITE');
      $this->exec('unzip -o /tmp/osfwsphpapi/'.$version.'.zip -d /tmp/osfwsphpapi/');      
      
      if(!is_dir($this->osf_web_services_folder.'/'))
      {
        $this->exec('mkdir -p '.$this->osf_web_services_folder.'/');      
      }
      
      $this->exec('cp -af /tmp/osfwsphpapi/OSF-Web-Services-PHP-API-'.$version.'/StructuredDynamics '.$this->osf_web_services_folder.'/');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/osfwsphpapi/');
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
      
      $this->exec('cp -af /tmp/osfwsphpapi/OSF-Web-Services-PHP-API-'.$version.'/StructuredDynamics '.$this->osf_web_services_folder.'/');

      $this->cecho("Cleaning upgrade folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/osfwsphpapi/');   
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
      $this->wget('https://github.com/structureddynamics/OSF-Datasets-Management-Tool/archive/'.$version.'.zip', '/tmp/dmt');

      $this->cecho("Installing the Datasets Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dmt/'.$version.'.zip -d /tmp/dmt/');      
      
      $this->exec('mkdir -p '.$this->datasets_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/dmt/OSF-Datasets-Management-Tool-'.$version.'/* '.$this->datasets_management_tool_folder.'/');

      $this->exec('chmod 755 '.$this->datasets_management_tool_folder.'/dmt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$this->datasets_management_tool_folder.'/dmt dmt');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Configuring the the DMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>osfWebServicesFolder = \"/usr/share/osf/\">osfWebServicesFolder = \"'.rtrim($this->osf_web_services_folder, '/').'/\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      $this->exec('sudo sed -i "s>indexesFolder = \"/usr/share/datasets-management-tool/datasetIndexes/\">indexesFolder = \"'.rtrim($this->datasets_management_tool_folder, '/').'/datasetIndexes/\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      $this->exec('sudo sed -i "s>ontologiesStructureFiles = \"/data/ontologies/structure/\">ontologiesStructureFiles = \"'.rtrim($this->data_folder, '/').'/ontologies/structure/\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      $this->exec('sudo sed -i "s>missingVocabulary = \"/usr/share/datasets-management-tool/missing/\">missingVocabulary = \"'.rtrim($this->datasets_management_tool_folder, '/').'/missing/\">" "'.$this->datasets_management_tool_folder.'/dmt.ini"');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/dmt/');      
    }
    
    /**
    * Install the Permissions Management Tool
    */
    public function installPermissionsManagementTool($version = '')
    {
      if($version == '')
      {
        $version = $this->permissions_management_tool_version;
      }
            
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("----------------------------------------\n", 'WHITE');
      $this->cecho(" Installing Permissions Management Tool \n", 'WHITE');
      $this->cecho("----------------------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');          
      
      if(is_dir($this->permissions_management_tool_folder.'/'))                
      {
        $this->cecho("The Permissions Management Tool is already installed. Consider upgrading it with the option: --upgrade-permissions-management-tool\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/pmt');

      $this->cecho("Downloading the Permissions Management Tool...\n", 'WHITE');
      $this->wget('https://github.com/structureddynamics/OSF-Permissions-Management-Tool/archive/'.$version.'.zip', '/tmp/pmt');

      $this->cecho("Installing the Permissions Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/pmt/'.$version.'.zip -d /tmp/pmt/');      
      
      $this->exec('mkdir -p '.$this->permissions_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/pmt/OSF-Permissions-Management-Tool-'.$version.'/* '.$this->permissions_management_tool_folder.'/');

      $this->exec('chmod 755 '.$this->permissions_management_tool_folder.'/pmt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$this->permissions_management_tool_folder.'/pmt pmt');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Configuring the the PMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>osfWebServicesFolder = \"/usr/share/osf/\">osfWebServicesFolder = \"'.rtrim($this->osf_web_services_folder, '/').'/\">" "'.$this->permissions_management_tool_folder.'/pmt.ini"');
      $this->exec('sudo sed -i "s>osfWebServicesEndpointsUrl = \"http://localhost/ws/\">osfWebServicesEndpointsUrl = \"http://'.$this->osf_web_services_domain.'/ws/\">" "'.$this->permissions_management_tool_folder.'/pmt.ini"');

      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/pmt/');      
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
      
      $dataValidatorFolder = $this->osf_web_services_folder.'/StructuredDynamics/osf/validator/';
      
      if(is_dir($dataValidatorFolder))                
      {
        $this->cecho("The Data Validator Tool is already installed. Consider upgrading it with the option: --upgrade-data-validator-tool\n", 'YELLOW');
        
        return;
      }
                                              
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/dvt');

      $this->cecho("Downloading the Data Validator Tool...\n", 'WHITE');
      $this->exec('wget -q -P /tmp/dvt https://github.com/structureddynamics/OSF-Data-Validator-Tool/archive/'.$version.'.zip');

      $this->cecho("Installing the Data Validator Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/dvt/'.$version.'.zip -d /tmp/dvt/');      
      
      $this->exec('mkdir -p '.$dataValidatorFolder);      
      
      $this->exec('cp -af /tmp/dvt/OSF-Data-Validator-Tool-'.$version.'/StructuredDynamics/osf/validator/* '.$dataValidatorFolder);

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
      
      $this->exec('cp resources/osf-web-services/osf-web-services /etc/apache2/sites-available/');

      $this->exec('sudo ln -s /etc/apache2/sites-available/osf-web-services /etc/apache2/sites-enabled/osf-web-services');
      
      // Fix the OSF Web Services path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/osf>'.$this->osf_web_services_folder.$this->osf_web_services_ns.'>" "/etc/apache2/sites-available/osf-web-services"');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');
      
      $this->cecho("Configure the osf.ini configuration file...\n", 'WHITE');

      $dbaPassword = 'dba';     
      
      $return = $this->getInput("What is the password of the DBA user in Virtuoso (default: dba)");

      if($return != '')
      {
        $dbaPassword = $return;
      }     

      $this->cecho("Make sure the OSF Web Services are aware of themselves by changing the hosts file...\n", 'WHITE');
      
      if(stripos(file_get_contents('/etc/hosts'), 'OSF-Installer') == FALSE)
      {
        file_put_contents('/etc/hosts', "\n\n# Added by the OSF-Installer to make the OSF Web Services are aware of themselves\n127.0.0.1 ".$this->osf_web_services_domain, FILE_APPEND);
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
      $this->exec('sudo sed -i "s>password = \"dba\">password = \"'.$dbaPassword.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix host
      $this->exec('sudo sed -i "s>host = \"localhost\">host = \"'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');
      $this->exec('sudo sed -i "s>solr_host = \"localhost\">solr_host = \"'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix fields_index_folder
      $this->exec('sudo sed -i "s>fields_index_folder = \"/tmp/\">fields_index_folder = \"'.$this->data_folder.'/osf-web-services/tmp/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');
      
      // fix wsf_base_url
      $this->exec('sudo sed -i "s>wsf_base_url = \"http://localhost\">wsf_base_url = \"http://'.$this->osf_web_services_domain.'\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      // fix wsf_base_path
      $this->exec('sudo sed -i "s>wsf_base_path = \"/usr/share/osf/StructuredDynamics/osf/ws/\">wsf_base_path = \"'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

      $this->exec('sudo sed -i "s>enable_lrl = \"FALSE\">enable_lrl = \"TRUE\">" "'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/osf.ini"');

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
      
      $dbaPassword = $this->getInput("What is the password of the DBA user in Virtuoso? ");
      
      $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/initialize_osf_web_services_network.php"');
      $this->exec('sed -i \'s>server_address = "">server_address = "http://'.$this->osf_web_services_domain.'">\' "resources/virtuoso/initialize_osf_web_services_network.php"');
      $this->exec('sed -i \'s>appID = "administer">appID = "'.$this->application_id.'">\' "resources/virtuoso/initialize_osf_web_services_network.php"');
      
      $errors = shell_exec('php resources/virtuoso/initialize_osf_web_services_network.php');
      
      if($errors == 'errors')
      {
        $this->cecho("\n\nThe OSF Web Services Network couldn't be created. Major Error.\n", 'RED');
      }        
      
      $this->cecho("Commit transactions to Virtuoso...\n", 'WHITE');      
      
      $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/commit.php"');
      
      $return = shell_exec('php resources/virtuoso/commit.php');
      
      if($return == 'errors')
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
      
      $this->runOSFTestsSuites($this->osf_web_services_folder);
    }    

    /**
    * Install the OSF PHPUNIT Tests Suites
    */
    public function installOSFTestsSuites($version = '')
    {
      if($version == '')
      {
        $version = $this->osf_tests_suites_version;
      }
            
      $this->cecho("Installing PHPUNIT\n", 'WHITE');

      $this->chdir('/tmp');
      
      $this->wget('http://pear.php.net/go-pear.phar');
      
      passthru('php go-pear.phar');
      
      $this->exec('pear channel-discover pear.phpunit.de', 'warning');
      
      $this->exec('pear channel-discover pear.symfony-project.com', 'warning');
      
      $this->exec('pear upgrade-all', 'warning');

      $this->exec('pear config-set auto_discover 1');
      
      $this->exec('pear install pear.phpunit.de/PHPUnit');      
      
      $this->cecho("PHPUnit Installed!\n", 'WHITE');      
      
      $this->cecho("Install tests suites...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->wget('https://github.com/structureddynamics/OSF-Tests-Suites/archive/'.$version.'.zip');
      
      $this->exec('unzip '.$version.'.zip');      
      
      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/OSF-Tests-Suites-'.$version.'/StructuredDynamics/osf/tests/');
      
      $this->exec('mv * ../../../../');

      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('rm *.zip');
            
      $this->exec('rm -rf OSF-Tests-Suites-'.$version.'');
      
      $this->cecho("Configure the tests suites...\n", 'WHITE');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->osf_web_services_folder.'/StructuredDynamics/osf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>osfInstanceFolder = \"/usr/share/osf/StructuredDynamics/osf/ws/\";>$this-\>osfInstanceFolder = \"'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->osf_web_services_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->osf_web_services_domain.'/wsf/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>userID = \'http://localhost/wsf/users/tests-suites\';>$this-\>userID = \'http://'.$this->osf_web_services_domain.'/wsf/users/tests-suites\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>adminGroup = \'http://localhost/wsf/groups/administrators\';>$this-\>adminGroup = \'http://'.$this->osf_web_services_domain.'/wsf/groups/administrators\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>testGroup = \"http://localhost/wsf/groups/unittests\";>$this-\>testGroup = \"http://'.$this->osf_web_services_domain.'/wsf/groups/unittests\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>testUser = \"http://localhost/wsf/users/unittests\";>$this-\>testUser = \"http://'.$this->osf_web_services_domain.'/wsf/users/unittests\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>testUser = \"http://localhost/wsf/users/unittests\";>$this-\>testUser = \"http://'.$this->osf_web_services_domain.'/wsf/users/unittests\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>applicationID = \'administer\';>$this-\>applicationID = \''.$this->application_id.'\';>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>apiKey = \'some-key\';>$this-\>apiKey = \''.$this->api_key.'\';>" Config.php');      
      
      $this->chdir($this->currentWorkingDirectory);
    }    
    

    /**
    * Update the OSF PHPUNIT Tests Suites
    */
    public function updateOSFTestsSuites($version = '')
    {
      if($version == '')
      {
        $version = $this->osf_tests_suites_version;
      }
      
      $this->cecho("Updating tests suites...\n", 'WHITE');
      
      $this->exec('rm -rf '.$this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('mkdir -p '.$this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->wget('https://github.com/structureddynamics/OSF-Web-Services-Tests-Suites/archive/'.$version.'.zip');
      
      $this->exec('unzip '.$version.'.zip');      
      
      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/OSF-Tests-Suites-'.$version.'/StructuredDynamics/osf/tests/');
      
      $this->exec('mv * ../../../../');

      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/tests/');
      
      $this->exec('rm *.zip');
            
      $this->exec('rm -rf OSF-Tests-Suites-'.$version.'');
      
      $this->cecho("Configure the tests suites...\n", 'WHITE');
      
      $this->exec('sed -i "s>REPLACEME>'.$this->osf_web_services_folder.'/StructuredDynamics/osf>" phpunit.xml');

      $this->exec('sudo sed -i "s>$this-\>osfInstanceFolder = \"/usr/share/osf/StructuredDynamics/osf/ws/\";>$this-\>osfInstanceFolder = \"'.$this->osf_web_services_folder.$this->osf_web_services_ns.'/\";>" Config.php');
      $this->exec('sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://'.$this->osf_web_services_domain.'/ws/\";>" Config.php');      
      $this->exec('sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://'.$this->osf_web_services_domain.'/wsf/ws/\";>" Config.php');      
      
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
      $this->wget('https://github.com/structureddynamics/OSF-Ontologies-Management-Tool/archive/'.$version.'.zip', '/tmp/omt');

      $this->cecho("Installing the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/'.$version.'.zip -d /tmp/omt/');      
      
      $this->exec('mkdir -p '.$this->ontologies_management_tool_folder.'/');      
      
      $this->exec('cp -af /tmp/omt/OSF-Ontologies-Management-Tool-'.$version.'/* '.$this->ontologies_management_tool_folder.'/');

      $this->exec('chmod 755 '.$this->ontologies_management_tool_folder.'/omt');
      
      $this->chdir('/usr/bin');
      
      $this->exec('ln -s '.$this->ontologies_management_tool_folder.'/omt omt');
      
      $this->chdir($this->currentWorkingDirectory);
            
      $this->cecho("Configuring the the OMT tool...\n", 'WHITE');
      $this->exec('sudo sed -i "s>osfWebServicesFolder = \"/usr/share/osf/\">osfWebServicesFolder = \"'.rtrim($this->osf_web_services_folder, '/').'/\">" "'.$this->ontologies_management_tool_folder.'/omt.ini"');
            
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
      $this->wget('https://github.com/structureddynamics/OSF-Ontologies-Management-Tool/archive/'.$version.'.zip', '/tmp/omt');

      $this->cecho("Upgrading the Ontologies Management Tool...\n", 'WHITE');
      $this->exec('unzip -o /tmp/omt/'.$version.'.zip -d /tmp/omt/');      
      
      // Make sure not to overwrite the data, missing and datasetIndexes folders and the omt.ini file
      $this->exec('rm -rf /tmp/omt/OSF-Ontologies-Management-Tool-'.$version.'/omt.ini');
      
      $this->exec("cp -af /tmp/omt/OSF-Ontologies-Management-Tool-".$version."/* ".$this->ontologies_management_tool_folder."/");

      // Make "omt" executable
      $this->exec('chmod 755 '.$this->ontologies_management_tool_folder.'/omt');
      
      $this->cecho("Cleaning installation folder...\n", 'WHITE');
      $this->exec('rm -rf /tmp/omt/');      
    }
  }
?>
