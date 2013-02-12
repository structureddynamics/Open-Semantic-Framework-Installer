<?php
  class OSFInstaller_Ubuntu_12_10 extends OSFInstaller
  {
    public function installPhp5()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho(" Installing PHP5 \n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');
      
      $currentWorkingDirectory = getcwd();
      
      if(strpos(shell_exec('uname -m'), 'x86_64') === FALSE)
      {
        $this->cecho("You are trying to install PHP5 on a non-64-bits processor. Switching to compile PHP5 from the source instead of using the 64-bits deb packages...\n", 'WHITE');

        $this->installPhp5FromSource();
        
        return;
      }
      
      $this->cecho("Installing required packages for installing PHP5...\n", 'WHITE');
      
      $currentWorkingDirectory = getcwd();
      
      $this->chdir('resources/php5/');
      
      $this->exec("apt-get -y remove unixodbc-dev");     
      $this->exec("apt-get -y remove php5");     
      $this->exec("apt-get -y install iodbc libiodbc2 libiodbc2-dev");     
      
      $this->exec('dpkg -i php5-common_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5-cgi_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5-cli_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5-curl_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i libapache2-mod-php5_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5-mysql_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5-mysql_5.4.6-1ubuntu1.1_amd64.deb');
      $this->exec('dpkg -i php5_5.4.6-1ubuntu1.1_all.deb');
      $this->exec('dpkg -i php5-gd_5.4.6-1ubuntu1.1_amd64.deb');
      passthru('dpkg -i php5-odbc_5.4.6-1ubuntu1.1_amd64.deb');
      
      // Place aptitude/apt-get hold on the custom packages
      $this->exec('apt-mark hold php5-common');
      $this->exec('apt-mark hold php5-cgi');
      $this->exec('apt-mark hold php5-cli');
      $this->exec('apt-mark hold php5-curl');
      $this->exec('apt-mark hold libapache2-mod-php5');
      $this->exec('apt-mark hold php5-mysql');
      $this->exec('apt-mark hold php5-odbc');
      $this->exec('apt-mark hold php5-gd');
      $this->exec('apt-mark hold php5');
      
      // Modify /var/lib/dpkg/status such that php5-odbc is not market as
      // dependant on libiodbc2. Otherwise it will always complains 
      // and we will have to resolve it in order to install anything else
      $status = file_get_contents('/var/lib/dpkg/status');
      $status = str_replace('Depends: libc6 (>= 2.14), libiodbc2 (>= 3.52.7), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            'Depends: libc6 (>= 2.14), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            $status);
      file_put_contents('/var/lib/dpkg/status', $status);
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');      

      $this->chdir($currentWorkingDirectory);
    }    
    
    /**
    * Install PHP5 with the modifications required by OSF, from source code.
    * 
    * Use this only if the packaged version of PHP5 is not working for you.
    */
    public function installPhp5FromSource()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho(" Installing PHP5 From Source \n", 'WHITE');
      $this->cecho("-----------------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');
      
      $currentWorkingDirectory = getcwd();
      
      $this->cecho("Preparing installation...\n", 'WHITE');
      
      $this->exec('mkdir -p /tmp/php5-install/update/build/');
      
      $this->cecho("Installing required packages for installing PHP5...\n", 'WHITE');
      
      $this->exec('apt-get -y install devscripts');
      $this->exec('apt-get -y install gcc debhelper fakeroot');

      $this->cecho("Repackaging PHP5 to use iODBC instead of unixODBC...\n", 'WHITE');

      $this->chdir('/tmp/php5-install/update/build/');
      
      $this->exec("apt-get -y source php5");                  
      
      $php_folder = rtrim(rtrim(shell_exec("ls -d php5*/")), '/');

      $this->chdir($currentWorkingDirectory);
      
      file_put_contents('/tmp/php5-install/update/build/'.$php_folder.'/debian/control', file_get_contents('resources/php5/control'));
      file_put_contents('/tmp/php5-install/update/build/'.$php_folder.'/debian/rules', file_get_contents('resources/php5/rules'));
      
      // Bypass the setup-mysql.sql file which is causing issues
      $this->exec('cat /dev/null > /tmp/php5-install/update/build/'.$php_folder.'/debian/setup-mysql.sh');
      
      $this->chdir('/tmp/php5-install/update/build/'.$php_folder);      
      
      $this->exec("apt-get -y build-dep php5");     
      $this->exec("apt-get -y remove unixodbc-dev libodbc1 odbcinst odbcinst1debian2 unixodbc");     
      
      $this->exec("apt-get -y install iodbc libiodbc2-dev");     
      
      $this->exec("debuild");     
      
      $this->chdir('/tmp/php5-install/update/build/');
          
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

      // Place aptitude/apt-get hold on the custom packages
      $this->exec('apt-mark hold php5-common');
      $this->exec('apt-mark hold php5-cgi');
      $this->exec('apt-mark hold php5-cli');
      $this->exec('apt-mark hold php5-curl');
      $this->exec('apt-mark hold libapache2-mod-php5');
      $this->exec('apt-mark hold php5-mysql');
      $this->exec('apt-mark hold php5-odbc');
      $this->exec('apt-mark hold php5-gd');
      $this->exec('apt-mark hold php5');
      
      // Modify /var/lib/dpkg/status such that php5-odbc is not market as
      // dependant on libiodbc2. Otherwise it will always complains 
      // and we will have to resolve it in order to install anything else
      $status = file_get_contents('/var/lib/dpkg/status');
      $status = str_replace('Depends: libc6 (>= 2.14), libiodbc2 (>= 3.52.7), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            'Depends: libc6 (>= 2.14), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            $status);
      file_put_contents('/var/lib/dpkg/status', $status);

      $this->cecho("Restarting Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');      
      
      $this->chdir($currentWorkingDirectory);
      
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
      
      $currentWorkingDirectory = getcwd();
      
      // Need to use passthru because the installer promp the user with some screens
      // where they have to answer questions
      
      // This cannot be logged into the log
      passthru('apt-get -y install virtuoso-opensource');   
      
      // Re-install php-odbc
      
      $this->exec('apt-mark unhold php5-odbc');
      
      $this->exec('dpkg -i --ignore-depends=libiodbc2 resources/php5/php5-odbc_5.4.6-1ubuntu1.1_amd64.deb');      

      $status = file_get_contents('/var/lib/dpkg/status');
      $status = str_replace('Depends: libc6 (>= 2.14), libiodbc2 (>= 3.52.7), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            'Depends: libc6 (>= 2.14), phpapi-20100525, php5-common (= 5.4.6-1ubuntu1.1), ucf',
                            $status);
      file_put_contents('/var/lib/dpkg/status', $status);
      
      $this->exec('apt-mark hold php5-odbc');
      
      // Then we have to install the libiodbc2 by hand
      
      $this->exec('mkdir /tmp/libodbc2-install/');
      
      $this->chdir('/tmp/libodbc2-install/');
      
      $this->exec('apt-get download libiodbc2');
      $this->exec('ar vx *odbc*.deb');
      $this->exec('tar -xzvf data.tar.gz');
      $this->exec('cp usr/lib/* /usr/lib/');
      
      $this->chdir($currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/libodbc2-install/');
      
      // Then we have to install the iodbc by hand
      // This is required to have the possibility
      // to test the connection with iodbctest
      
      $this->exec('mkdir /tmp/iodbc-install/');
      
      $this->chdir('/tmp/iodbc-install/');
      
      $this->exec('apt-get download iodbc');
      $this->exec('ar vx *odbc*.deb');
      $this->exec('tar -xzvf data.tar.gz');
      $this->exec('cp usr/lib/* /usr/lib/');
      $this->exec('cp usr/bin/* /usr/bin/');
      
      $this->chdir($currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/iodbc-install/');
      
      // apt-get download libiodbc2
      // ar vx *odbc*.deb
      // tar -xzvf data.tar.gz
      // cp usr/lib/* /usr/lib/
      // clean the files...
      // test that iodbc is used by php5

      $this->exec('mv /etc/init.d/virtuoso-opensource-6.1 /etc/init.d/virtuoso');

      $this->cecho("Installing odbc.ini and odbcinst.ini files...\n", 'WHITE');
      
      $this->exec('cp -f resources/virtuoso/odbc.ini /etc/odbc.ini');
      $this->exec('cp -f resources/virtuoso/odbcinst.ini /etc/odbcinst.ini');

      $this->cecho("Test Virtuoso startup...\n", 'WHITE');
      
      $this->exec('/etc/init.d/virtuoso stop');
      
      sleep(20);
      
      $this->exec('/etc/init.d/virtuoso start');
      
      $isVirtuosoRunning = shell_exec('ps aux | grep virtuoso');
      
      if(strpos($isVirtuosoRunning, '/usr/bin/virtuoso') === FALSE)
      {
        $this->cecho('Virtuoso is not running. Check the logs, something did go bad.', 'RED');
      }
      else
      {
        $this->cecho("Register Virtuoso to automatically start at the system's startup...\n", 'WHITE');
        
        $this->exec('sudo update-rc.d virtuoso defaults');
        
        $this->cecho("Installing the exst() procedure...\n", 'WHITE');
        
        $dbaPassword = $this->getInput("What is the password of the DBA user in Virtuoso? ");
        
        $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/install_exst.php"');
        
        $errors = shell_exec('php resources/virtuoso/install_exst.php');
        
        if($errors == 'errors')
        {
          $this->cecho("\n\nThe EXST() procedure couldn't be created. Try to create it after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Open_Virtuoso_Conductor...\n", 'YELLOW');
        }
        
        
        $this->cecho("Installing the logging procedures and tables...\n", 'WHITE');
        
        $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/install_logging.php"');
        
        $errors = shell_exec('php resources/virtuoso/install_logging.php');
        
        if($errors == 'errors')
        {
          $this->cecho("\n\nThe logging procedures couldn't be created. Try to create them after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Configure_Logger...\n", 'YELLOW');
        }
      }
      
      $this->cecho("You can start Virtuoso using this command: /etc/default/virtuoso start\n", 'LIGHT_BLUE');
    }
    
    /**
    * Install Solr as required by OSF
    */
    public function installSolr()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho(" Installing Solr \n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');   
      
      $currentWorkingDirectory = getcwd();
      
      $this->cecho("Installing prerequirements...\n", 'WHITE');
      
      $this->exec('apt-get -y install openjdk-7-jdk');
      
      $this->cecho("Preparing installation...\n", 'WHITE');

      $this->exec('mkdir /tmp/solr-install/');
      
      $this->chdir('/tmp/solr-install/');
      
      $this->cecho("Downloading Solr...\n", 'WHITE');
      
      $this->exec('wget -q http://archive.apache.org/dist/lucene/solr/3.6.0/apache-solr-3.6.0.tgz');
      
      $this->cecho("Installing Solr...\n", 'WHITE');

      $this->exec('tar -xzvf apache-solr-3.6.0.tgz');

      $this->exec('mkdir /usr/share/solr');
      
      $this->exec('cp -af /tmp/solr-install/apache-solr-3.6.0/* /usr/share/solr/');
      
      $this->cecho("Configuring Solr...\n", 'WHITE');
      
      $this->chdir($currentWorkingDirectory);

      $this->exec('cp -f resources/solr/solr /etc/init.d/');
      
      $this->exec('chmod 755 /etc/init.d/solr');
      
      $this->exec('mv /usr/share/solr/example/ /usr/share/solr/structwsf/');      
      
      $this->cecho("Installing SOLR-2155...\n", 'WHITE');
      
      $this->chdir('/usr/share/solr/dist/');
      
      $this->exec('wget -q https://github.com/downloads/dsmiley/SOLR-2155/Solr2155-1.0.5.jar');
      
      $this->chdir($currentWorkingDirectory);
      
      $this->exec('cp -af resources/solr/solrconfig.xml /usr/share/solr/structwsf/solr/conf/');

      $this->cecho("Starting Solr...\n", 'WHITE');

      $this->exec('/etc/init.d/solr start');
      
      $this->cecho('Register Solr to automatically start at the system\'s startup...', 'WHITE');
      
      $this->exec('sudo update-rc.d solr defaults');      
      
      $this->cecho("You can start Solr using this command: /etc/init.d/solr start\n", 'LIGHT_BLUE');
    }    

    /**
    * Install Apache2 as required by OSF
    */
    public function installApache2()
    {
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

      $this->cecho("Performing some tests on the new Apache2 instance...\n", 'WHITE');
      $this->cecho("Checking if the Apache2 instance is up and running...\n", 'WHITE');
      
      if(strpos(shell_exec('curl -s http://localhost'), 'It works!') === FALSE)
      {
        $this->cecho("[Error] Apache2 is not currently running...\n", 'YELLOW');
      }
      else
      {
        $this->cecho("Checking if the Apache2 instance is using IPv6...\n", 'WHITE');
        
        if(strpos(shell_exec('netstat -tulpn | grep apache2'), ':::80') !== FALSE)
        {
          $this->cecho("Apache2 is running using IPv6. Check this web page for more information on what to do: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#IPv6_Not_Supported...\n", 'YELLOW');
        }
      }
    }

    /**
    * Install MySQL as required by OSF
    */
    public function installMySQL()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("------------------\n", 'WHITE');
      $this->cecho(" Installing MySQL \n", 'WHITE');
      $this->cecho("------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');

      $this->cecho("Installing MySQL...\n", 'WHITE');
      
      // Need to use passthru because the installer promp the user with some screens
      // where they have to answer questions
      
      // This cannot be logged into the log
      passthru('apt-get -y install mysql-server');
      
      $this->cecho("Updating php.ini to enable mysql...\n", 'WHITE');
      $this->exec('sed -r -i "s/; +extension=msql.so/extension=mysql.so/" /etc/php5/apache2/php.ini');
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');

      $this->cecho("Performing some tests on the new Apache2 instance...\n", 'WHITE');
      $this->cecho("Checking if the Apache2 instance is up and running...\n", 'WHITE');
      
      if(strpos(shell_exec('curl -s http://localhost'), 'It works!') === FALSE)
      {
        $this->cecho("[Error] Apache2 is not currently running...\n", 'YELLOW');
      }
    }    
    
    /**
    * Install MySQL as required by OSF
    */
    public function installPhpMyAdmin()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------------\n", 'WHITE');
      $this->cecho(" Installing PhpMyAdmin \n", 'WHITE');
      $this->cecho("-----------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');

      $this->cecho("Installing PhpMyAdmin...\n", 'WHITE');
      
      // Need to use passthru because the installer promp the user with some screens
      // where they have to answer questions
      
      // This cannot be logged into the log
      passthru('apt-get -y install phpmyadmin');
    }          
    
    /**
    * Validate that the OS where the script is running is a Ubuntu instance greater than supported version
    * by the script.
    */
    public static function isWorkingInstaller()
    {
      exec('cat /etc/issue', $output);

      foreach($output as $line)
      {
        if(strpos($line, 'ubuntu') != -1)
        {
          // Validate version
          $version = (float) shell_exec('lsb_release -rs');
          
          if($version >= 12.10)
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
