<?php
  
  class OSFWebServicesUpgrader extends OSFConfigurator
  {
    private $latestVersion = '3.3.0';
    
    private $currentInstalledVersion = '';
    
    function __construct($configFile, $codeBase = FALSE)
    {
      parent::__construct($configFile);

      $versionIni = parse_ini_file($this->osf_web_services_folder.$this->osf_web_services_ns.'/VERSION.ini', TRUE);
      
      $this->currentInstalledVersion = $versionIni['version']['version'];
      
      if($codeBase)
      {
        $this->backupInstalledVersion();
        
        $this->upgradeCodebase('3.3');
        
        $this->upgradeOSFTestsSuites('3.3');
        
        $this->runOSFTestsSuites();
      }
      else
      {
        // Find the current version of the OSF Web Services
        $this->backupInstalledVersion();         
        
        switch($this->currentInstalledVersion)
        {
          case '3.0.0':
            $this->upgradeTo_3_0_1();
          break;
          
          case '3.0.1':
            $this->upgradeTo_3_1_0();
          break;

          case '3.1.0':
            $this->upgradeTo_3_2_0();
          break;

          case '3.2.0':
            $this->upgradeTo_3_3_0();
          break;

          case '3.3.0':
            $this->upgradeTo_3_4_0();
          break;

          case '3.4.0':
            $this->latestVersion();
            //$this->upgradeTo_3_5_0();
          break;
          
          default:
            $this->span("You are running an unknown version of the OSF Web Services: ".$this->currentInstalledVersion.". The OSF Web Services cannot be upgraded using this upgrade tool.", 'warn');
          break;
        }
        
        $this->upgradeOSFTestsSuites($this->currentInstalledVersion);
        $this->runOSFTestsSuites();     
      } 
    } 
    
    private function backupInstalledVersion()
    {
      $backupFolder = '/tmp/osf-web-services-backup-'.$this->currentInstalledVersion.'-'.md5(microtime());  
      
      $this->span("Backuping the current version of the files into: ".$backupFolder."/ ...");
      
      $this->mkdir($backupFolder);
      
      $this->cp($this->osf_web_services_folder.$this->osf_web_services_ns.'/', $backupFolder);
      
      // Backup the osf.ini config file
      $this->chdir($this->osf_web_services_folder.$this->osf_web_services_ns.'/ws/framework/');
      
      $wsFile = file_get_contents('WebService.php');
      
      // Save previous WebService.php settings
      preg_match('/osf_ini = "(.*)"/', $wsFile, $matches);
      $osf_ini = $matches[1];
      
      $this->cp($osf_ini, $backupFolder.'/osf.ini');
    }   
    
    private function upgradeCodebase($version)
    {
      $this->span("Upgrading codebase...");
      
      $this->mkdir("/tmp/osf-web-services-{$version}/");
      
      $this->chdir('/tmp/osf-web-services-'.$version.'/');
      
      $this->span("Download the OSF Web Services version {$version}...");
      
      $this->wget("https://github.com/structureddynamics/OSF-Web-Services/archive/{$version}.zip");

      $this->span("Preparing the OSF Web Services version {$version}...");
      
      $this->exec("unzip {$version}.zip");

      $this->chdir('OSF-Web-Services-'.$version);

      $this->span("Remove default settings...");
      
      $this->rm(ltrim($this->osf_web_services_ns, '/').'/osf.ini', TRUE);
      $this->rm(ltrim($this->osf_web_services_ns, '/').'/keys.ini', TRUE);
      $this->rm(ltrim($this->osf_web_services_ns, '/').'/framework/WebService.php', TRUE);
      $this->rm(ltrim($this->osf_web_services_ns, '/').'/index.php', TRUE);
      $this->rm(ltrim($this->osf_web_services_ns, '/').'/scones/config.ini', TRUE);
      
      $this->span("\n\nMove new files to the current OSF Web Services installation folder...");

      $this->cp('*', rtrim($this->osf_web_services_folder, '/').'/');

      $this->chdir('/tmp/');

      $this->rm("osf-web-services-{$version}/", TRUE);

      $this->span("Codebase upgraded...", 'good');
    }
    
    /**
    * Upgrade a OSF Web Services to the latest development code
    */
    public function upgradeOSFWebServicesCodeBase()
    {
      $this->h1("Upgrading OSF Web Services Code Base"); 

      $yes = $this->isYes($this->getInput("You are about to upgrade your OSF Web Services instance using 
                                           the latest development code base. This installation only upgrade the
                                           code base of OSF Web Services and doesn't configure features that may
                                           have been changed. You have to know what you are doing 
                                           and make sure that you understand the latest changes that occured 
                                           in OSF before performing this action. If major changes occured,
                                           wait until an official release get created to use the normal
                                           upgrade option. Are you sure you want to continue? (yes/no)\n"));             
      
      if(!$yes)
      {
        exit(1);
      }      

      $this->upgradeCodebase('3.1');
    }    
    
    private function latestVersion()
    {                    
      $this->span("Upgrade finished, latest version installed: OSF Web Services ".$this->latestVersion);
    }    
    
    private function upgradeTo_3_0_1()
    {                    
      $this->upgradeCodebase('3.0.1');
      
      $this->currentInstalledVersion = '3.0.1';
    }
        
    private function upgradeTo_3_1_0()
    {                    
      $this->upgradeCodebase('3.1.0');
      
      $this->chdir($this->osf_web_services_folder.'/StructuredDynamics/osf/ws/framework/');
      
      $wsFile = file_get_contents('WebService.php');
      
      // Save previous WebService.php settings
      preg_match('/osf_ini = "(.*)"/', $wsFile, $matches);
      $osf_ini = $matches[1];

      preg_match('/keys_ini = "(.*)"/', $wsFile, $matches);
      $keys_ini = $matches[1];
      
      // Replace the WebService.php file with the latest version in 3.1.0
      $this->rm('WebService.php');
      
      $this->wget('https://raw.githubusercontent.com/structureddynamics/OSF-Web-Services/3.1/StructuredDynamics/osf/ws/framework/WebService.php');
      
      // Reconfigure the default WebService.php file
      $wsFile = file_get_contents('WebService.php');
      
      $wsFile = str_replace('osf_ini = "/usr/share/osf/StructuredDynamics/osf/ws/"', 'osf_ini = "'.$osf_ini.'"');
      $wsFile = str_replace('keys_ini = "/usr/share/osf/StructuredDynamics/osf/ws/"', 'keys_ini = "'.$keys_ini.'"');
      
      file_put_contents('WebService.php', $wsFile);
      
      $this->currentInstalledVersion = '3.1.0';
    }    
        
    private function upgradeTo_3_2_0()
    {           
      $this->span("Can't upgrade the OSF codebase version 3.1.0 to 3.2.0 automatically...", 'warn');        
    }    
        
    private function upgradeTo_3_3_0()
    {           
      $this->span("Can't upgrade the OSF codebase version 3.2.0 to 3.3.0 automatically...", 'warn');        
    }    
        
    private function upgradeTo_3_4_0()
    {                    
      $this->upgradeCodebase('3.4.0');
      
      $this->chdir($this->osf_web_services_folder.$this->osf_web_services_ns.'/ws/framework/');
      
      $wsFile = file_get_contents('WebService.php');
      
      // Save previous WebService.php settings
      preg_match('/osf_ini = "(.*)"/', $wsFile, $matches);
      $osf_ini = $matches[1];

      preg_match('/keys_ini = "(.*)"/', $wsFile, $matches);
      $keys_ini = $matches[1];
      
      // Replace the WebService.php file with the latest version in 3.4.0
      $this->rm('WebService.php');
      
      $this->wget('https://raw.githubusercontent.com/structureddynamics/OSF-Web-Services/3.4/StructuredDynamics/osf/ws/framework/WebService.php');
      
      // Reconfigure the default WebService.php file
      $wsFile = file_get_contents('WebService.php');
      
      $wsFile = str_replace('osf_ini = "/usr/share/osf/StructuredDynamics/osf/ws/"', 'osf_ini = "'.$osf_ini.'"');
      $wsFile = str_replace('keys_ini = "/usr/share/osf/StructuredDynamics/osf/ws/"', 'keys_ini = "'.$keys_ini.'"');
      
      file_put_contents('WebService.php', $wsFile);
      
      // Add new configuration option "virtuoso-disable-transaction-log"
      $ini = file_get_contents($osf_ini);
      
      $ini = str_replace("[triplestore]", "[triplestore]\n\nvirtuoso-disable-transaction-log = \"true\"\n\n", $ini);
      
      file_put_contents($osf_ini, $ini);
      
      $this->currentInstalledVersion = '3.4.0';
      
      
      //
      // These are the steps that needs to be performed for each upgrade.
      // Some of these steps may not apply to a specific version upgrade
      // in which case it will simply be ignored.
      //

      // 1) Delete files in the previous version of the OSF Web Services that are not needed anymore
      // 2) If the WebService.php file got modified, do re-create it using the same $data_ini and $network_ini settings
      // 3) If new osf.ini settings got added, add them to the end of the current osf.ini file
      // 4) If changes have been made to the Triple Store, do perform these changes
      // 5) If the Solr schema changed, update the schema, restart Solr, and re-load data into Solr using the Dataset Management Tool
      // 6) If new software or libraries are needed for this upgrade, then simply install and configure them.      
      
      /*
      $this->currentInstalledVersion = '3.3.1';
      */
    } 
            
    private function upgradeTo_3_5_0()
    {                    
      // $this->upgradeCodebase('3.5.0');
      
      //
      // These are the steps that needs to be performed for each upgrade.
      // Some of these steps may not apply to a specific version upgrade
      // in which case it will simply be ignored.
      //

      // 1) Delete files in the previous version of the OSF Web Services that are not needed anymore
      // 2) If the WebService.php file got modified, do re-create it using the same $data_ini and $network_ini settings
      // 3) If new osf.ini settings got added, add them to the end of the current osf.ini file
      // 4) If changes have been made to the Triple Store, do perform these changes
      // 5) If the Solr schema changed, update the schema, restart Solr, and re-load data into Solr using the Dataset Management Tool
      // 6) If new software or libraries are needed for this upgrade, then simply install and configure them.      
      
      /*
      $this->currentInstalledVersion = '3.5.0';
      */
    }    
  }
  
?>
