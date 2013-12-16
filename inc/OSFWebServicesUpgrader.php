<?php
  
  class OSFWebServicesUpgrader extends OSFConfigurator
  {
    private $latestVersion = '2.0.0';
    
    private $currentInstalledVersion = '';
    
    function __construct($configFile)
    {
      parent::__construct($configFile);
      
      // Find the current version of the OSF Web Services
      
      $versionIni = parse_ini_file($this->osf_web_services_folder.$this->osf_web_services_ns.'/VERSION.ini', TRUE);
      
      $this->currentInstalledVersion = $versionIni['version']['version'];
      
      $this->backupInstalledVersion();         
      
      switch($this->currentInstalledVersion)
      {
        case '2.0.0':
          $this->latestVersion();
          //$this->upgradeTo_2_0_1();
        break;
        
        case '2.0.1':
          //$this->upgradeTo_2_0_2();
        break;
        
        default:
          $this->cecho("You are running an unknown version of the OSF Web Services: ".$this->currentInstalledVersion.". The OSF Web Services cannot be upgraded using this upgrade tool.\n", 'YELLOW');
        break;
      }
      
      $this->upgradeOSFTestsSuites();
      $this->runOSFTestsSuites();      
    } 
    
    private function backupInstalledVersion()
    {
      $backupFolder = '/tmp/osf-web-services-backup-'.$this->currentInstalledVersion;  
      
      $this->cecho("Backuping the current version of the files into: ".$backupFolder."/ ...\n", 'WHITE');
      
      $this->exec('mkdir -p '.$backupFolder);
      
      $this->exec('cp -af '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/ '.$backupFolder);      
    }   
    
    private function upgradeCodebase($version)
    {
      $this->cecho("Upgrading codebase...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/osf-web-services-'.$version.'/');
      
      $this->chdir('/tmp/osf-web-services-'.$version.'/');
      
      $this->cecho("Download the OSF Web Services version ".$version."...\n");
      
      $this->wget('https://github.com/structureddynamics/OSF-Web-Services/archive/version-'.$version.'.zip');

      $this->cecho("Preparing the OSF Web Services version ".$version."...\n");
      
      $this->exec('unzip version-'.$version.'.zip');

      $this->chdir('OSF-Web-Services-version-'.$version);

      $this->cecho("Remove default settings...\n");
      
      $this->exec('rm -rf osf.ini');
      $this->exec('rm -rf framework/WebService.php');
      $this->exec('rm -rf index.php');
      $this->exec('rm -rf scones/config.ini');
      
      $this->cecho("\n\nMove new files to the current OSF Web Services installation folder...\n", 'WHITE');

      $this->exec('cp -af * '.$this->osf_web_services_folder.$this->osf_web_services_ns.'/');

      $this->chdir('/tmp/');
      
      $this->exec('rm -rf osf-web-services-'.$version.'/');

      $this->cecho("Codebase upgraded...\n", 'GREEN');
    }
    
    private function latestVersion()
    {                    
      $this->cecho("Upgrade finished, latest version installed: OSF Web Services ".$this->latestVersion."\n\n", 'WHITE');
    }    
    
    private function upgradeTo_2_0_1()
    {                    
      /*
      $this->upgradeCodebase('2.0.1');
      */
      
      //
      // These are the steps that needs to be performed for each upgrade.
      // Some of these steps may not apply to a specific version upgrade
      // in which case it will simply be ignored.
      //

      // 1) Delete files in the previous version of the OSF Web Services that are not needed anymore
      // 2) If the WebService.php file got modified, do re-create it using the same $data_ini and $network_ini settings
      // 3) If new data.ini settings got added, add them to the end of the current data.ini file
      // 4) If new network.ini settings got added, add them to the end of the current network.ini file
      // 5) If changes have been made to the Triple Store, do perform these changes
      // 6) If the Solr schema changed, update the schema, restart Solr, and re-load data into Solr using the Dataset Management Tool
      // 7) If new software or libraries are needed for this upgrade, then simply install and configure them.      
      
      /*
      $this->currentInstalledVersion = '2.0.1';
      */
    }
  }
  
?>
