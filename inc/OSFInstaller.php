<?php
  class OSFInstaller
  {
    /* Minimal Ubuntu release version supported by this OSF isntaller script  */    
    private $supported_minimal_release_version = 12.10;
    
    /* Parsed intaller.ini configuration file */
    private $config;
    
    /* Full path of the logfile */
    private $log_file = '';
    
    /* Specify if if we output everything we got from the commands */
    private $verbose = FALSE;
    
    // Configufation options
    
    /* version of virtuoso to install */
    private $virtuoso_version = "6.1.6";
    
    /* Version of drupal to install */
    private $drupal_version = "7.19";

    /* Version of structWSF to install */
    private $structwsf_version = "2.0";

    /* Version of conStruct to install */
    private $construct_version = "7.x-1.0";

    /* Folder where the data is managed */
    private $data_folder = "/data";

    /* Folder where to install structWSF */
    private $structwsf_folder = "/usr/share/structwsf";
    
    /* Folder where to install the Datasets Management Tool */
    private $datasets_management_tool_folder = "/usr/share/datasets-management-tool";

    /* Folder where to install the Ontologies Management Tool */
    private $ontologies_management_tool_folder = "/usr/share/ontologies-management-tool";

    /* Folder where to put the logging files */
    private $logging_folder = "/tmp";
    
    /* Domain name where to access the structWSF instance */
    private $structwsf_domain = "localhost";
    
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
    * Install the entire OSF stack. Running this command will install the full stack on the server
    * according to the settings specified in the installer.ini file.
    */
    public function installOSF()
    {
      if(!$this->validOS())
      {
        $this->cecho("This option is only available on Ubuntu. Aborded.\n", 'RED');
        die;
      }
      
      $this->cecho("You are about to install the Open Semantic Framework.\n", 'WHITE');
      $this->cecho("This installation process will install all the softwares that are part of the OSF stack. It will take 10 minutes of your time, but the process will go on for a few hours because all pieces of software that get compiled.\n\n", 'WHITE');
      $this->cecho("The log of this installation is available here: ".$this->log_file."\n", 'WHITE');
      $this->cecho("\n\nCopyright 2008-13. Structured Dynamics LLC. All rights reserved.\n\n", 'WHITE');
      
      $this->cecho("\n\n");
      $this->cecho("---------------------------------\n", 'WHITE');
      $this->cecho(" General Settings Initialization \n", 'WHITE'); 
      $this->cecho("---------------------------------\n", 'WHITE'); 
      $this->cecho("\n\n");

      $return = $this->getInput("What is the domain name where the structWSF instance will be accessible (default: ".$this->structwsf_domain.")");
      
      if($return != '')
      {
        $this->structwsf_domain = $return;
      }     
      
      $this->cecho("Make sure structWSF is aware of itself by changing the hosts file...\n", 'WHITE');
      
      if(stripos(file_get_contents('/etc/hosts'), 'OSF-Installer') == FALSE)
      {
        file_put_contents('/etc/hosts', "\n\n# Added by the OSF-Installer to make structWSF aware of itself\n127.0.0.1 ".$this->structwsf_domain, FILE_APPEND);
      }
      
        /*
        
        $this->strucwsf_domain
        
        cecho "\n\n**Important note**: except if you have special installation requirements, it is **strongly** suggested to use the default version numbers for your installation process. Just use the default versions that are suggested.\n\n" $cyan

        cecho "What is the Virtuoso version you want to install (default: $VIRTUOSOVERSION):" $magenta

        read NEWVIRTUOSOVERSION

        [ -n "$NEWVIRTUOSOVERSION" ] && VIRTUOSOVERSION=$NEWVIRTUOSOVERSION


        cecho "What is the structWSF version you want to install (default: $STRUCTWSFVERSION):" $magenta

        read NEWSTRUCTWSFVERSION

        [ -n "$NEWSTRUCTWSFVERSION" ] && STRUCTWSFVERSION=$NEWSTRUCTWSFVERSION

        cecho "What is the Drupal 6 version you want to install (default: $DRUPALVERSION):" $magenta

        read NEWDRUPALVERSION

        [ -n "$NEWDRUPALVERSION" ] && DRUPALVERSION=$NEWDRUPALVERSION

        cecho "What is the Drupal 6 version you want to install (default: $CONSTRUCTVERSION):" $magenta

        read NEWCONSTRUCTVERSION

        [ -n "$NEWCONSTRUCTVERSION" ] && CONSTRUCTVERSION=$NEWCONSTRUCTVERSION

        cecho "What is the location of the data folder (default: $DATAFOLDER):" $magenta

        read NEWDATAFOLDER

        [ -n "$NEWDATAFOLDER" ] && DATAFOLDER=$NEWDATAFOLDER

        # Make sure there is no trailing slashes

        DATAFOLDER=$(echo "${DATAFOLDER}" | sed -e "s/\/*$//")

      
      */
      
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
      $this->exec('apt-get -y install curl gcc iodbc libssl-dev openssl unzip gawk vim default-jdk ftp-upload');        
            
      $this->installStructWSFPHPAPI();

      $this->installVirtuoso();
      
      $this->installApache2();      
      $this->installPhp5();
      
      
      
      $this->installDatasetsManagementTool();
      $this->installOntologiesManagementTool();
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

    /**
    * Install PHP5 with the modifications required by OSF
    */
    public function installPhp5()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho(" Installing PHP5 \n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');
      
      $currentWorkingDirectory = getcwd();
      
      $this->cecho("Preparing installation...\n", 'WHITE');
      $this->exec('mkdir -p /tmp/php5-install/update/build/');

      $this->cecho("Installing required packages for installing PHP5...\n", 'WHITE');
      $this->exec('apt-get -y install devscripts gcc debhelper fakeroot apache2-mpm-prefork hardening-wrapper libdb-dev libenchant-dev libglib2.0-dev libicu-dev libsqlite0-dev');
      
      $this->cecho("Repackaging PHP5 to use iODBC instead of unixODBC...\n", 'WHITE');

      chdir('/tmp/php5-install/update/build/');
      
      $this->exec("apt-get -y source php5");            
      
      $php_folder = rtrim(rtrim(shell_exec("ls -d php5*/")), '/');

      chdir($currentWorkingDirectory);
      
      file_put_contents('/tmp/php5-install/update/build/'.$php_folder.'/debian/control', file_get_contents('resources/php5/control'));
      file_put_contents('/tmp/php5-install/update/build/'.$php_folder.'/debian/rules', file_get_contents('resources/php5/rules'));

      chdir('/tmp/php5-install/update/build/');
      
      $this->exec("apt-get -y build-dep php5");     
      $this->exec("apt-get -y remove unixodbc-dev");     
      $this->exec("apt-get -y install iodbc libiodbc2 libiodbc2-dev libt1-dev");     
      
      $this->cecho("Running debuild. This operation can take quite some time so be patient...\n", 'WHITE');
      
      chdir('/tmp/php5-install/update/build/'.$php_folder.'/');
      
      $this->exec("debuild -us -uc");     
      
      chdir('/tmp/php5-install/update/build/');
          
      $newVersion = shell_exec("(ls php5-common*.deb | echo \$(sed s/php5-common//))");
      $allVersion = shell_exec("(echo \"$newVersion\" | echo \$(sed s/amd64/all/))");

      // In case we are with a i386 server...
      $allVersion = str_replace('i386', 'all', $allVersion);
      
      $this->exec("dpkg -i php5-common".$newVersion); 
      $this->exec("dpkg -i php5-cgi".$newVersion);
      $this->exec("dpkg -i php5-cli".$newVersion);
      $this->exec("dpkg -i php5-curl".$newVersion);
      $this->exec("dpkg -i libapache2-mod-php5".$newVersion);
      $this->exec("dpkg -i php5-mysql".$newVersion);
      $this->exec("dpkg -i php5-odbc".$newVersion);
      $this->exec("dpkg -i php5-gd".$newVersion);
      $this->exec("dpkg -i php5".$allVersion);

      // Place dpkg hold on the custom packages
      $this->exec('dpkg --set-selections && "php5-common hold" | dpkg --set-selections && echo "php5-cgi hold" | dpkg --set-selections && echo "php5-cli hold" | dpkg --set-selections && echo "php5-curl hold" | dpkg --set-selections && echo "libapache2-mod-php5 hold" | dpkg --set-selections && echo "php5-mysql hold" | dpkg --set-selections && echo "php5-odbc hold" | dpkg --set-selections && echo "php5-gd hold" | dpkg --set-selections && echo "php5 hold" | dpkg --set-selections');     

      // Place aptitude/apt-get hold on the custom packages
      $this->exec('aptitude hold php5-common php5-cgi php5-cli php5-curl libapache2-mod-php5 php5-mysql php5-odbc php5-gd php5');

      chdir($currentWorkingDirectory);
      
      $this->cecho("Cleaning installation...\n", 'WHITE');
      $this->exec('rm -rf /tmp/php5-install/');
    }
    
    /**
    * Install Virtuoso as required by OSF
    */
    public function installVirtuoso()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("---------------------\n", 'WHITE');
      $this->cecho(" Installing Virtuoso \n", 'WHITE');
      $this->cecho("---------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');   
      
      // Need to use passthru because the installer promp the user with some screens
      // where they have to answer questions
      
      // This cannot be logged into the log
      passthru('apt-get -y install virtuoso-server');
      
      // Fix this this /etc/default/virtuoso-opensource-6.1
    }

    /**
    * Install Apache2 as required by OSF
    */
    public function installApache2()
    {
      if(!$this->validOS())
      {
        $this->cecho("This option is only available on Ubuntu. Aborded.\n", 'RED');
        die;
      }
      
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("--------------------\n", 'WHITE');
      $this->cecho(" Installing Apache2 \n", 'WHITE');
      $this->cecho("--------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');
      
      $this->cecho("Installing Apache2...\n", 'WHITE');
      $this->exec('apt-get -y install apache2');
      
      $this->cecho("Enabling mod-rewrite...\n", 'WHITE');
      $this->exec('a2enmod rewrite');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');
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
    
    /**
    * Validate that the OS where the script is running is a Ubuntu instance greater than supported version
    * by the script.
    */
    public function validOS()
    {
      exec('cat /etc/issue', $output);

      foreach($output as $line)
      {
        if(strpos($line, 'ubuntu') != -1)
        {
          // Validate version
          $version = (float) shell_exec('lsb_release -rs');
          
          if($version >= $this->supported_minimal_release_version)
          {
            return(TRUE);
          }
          else
          {
            return(FALSE);
          }
        }
      }
      
      return(FALSE);
    }
  }
?>
