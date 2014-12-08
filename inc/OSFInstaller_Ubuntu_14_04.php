<?php

  include_once('inc/OSFInstaller.php');

  class OSFInstaller_Ubuntu_14_04 extends OSFInstaller
  {
    public function installPhp5()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho(" Installing PHP5 \n", 'WHITE');
      $this->cecho("-----------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');
      
      if(strpos(shell_exec('uname -m'), 'x86_64') === FALSE)
      {
        $this->cecho("You are trying to install PHP5 on a non-64-bit processor. Switching to compile PHP5 from the source instead of using the 64-bits deb packages...\n", 'WHITE');

        $this->installPhp5FromSource();
        
        return;
      }

      $this->cecho("Downloading required packages for installing PHP5...\n", 'WHITE');

      $this->chdir($this->currentWorkingDirectory);
      
      $this->wget('https://github.com/structureddynamics/OSF-Installer-Ext/raw/master/ubuntu-14.04/ubuntu-14.04.zip');
      $this->exec("unzip ubuntu-14.04.zip");
      
      $this->exec("rm ubuntu-14.04.zip");
      
      $this->cecho("Installing required packages for installing PHP5...\n", 'WHITE');
      
      $this->chdir('resources/php5/');
      
      $this->exec("apt-get -y remove unixodbc-dev");     
      $this->exec("apt-get -y remove php5");     
      $this->exec("apt-get -y install iodbc libiodbc2 libiodbc2-dev");     
      
      $this->exec('dpkg -i php5-common_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5-cgi_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5-cli_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5-readline_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5-curl_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i libapache2-mod-php5_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5-mysql_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      $this->exec('dpkg -i php5_5.5.9+dfsg-1ubuntu4.4_all.deb');
      $this->exec('dpkg -i php-pear_5.5.9+dfsg-1ubuntu4.4_all.deb');
      $this->exec('dpkg -i php5-gd_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      passthru('dpkg -i php5-odbc_5.5.9+dfsg-1ubuntu4.4_amd64.deb');
      
      // Place aptitude/apt-get hold on the custom packages
      $this->exec('apt-mark hold php5-common');
      $this->exec('apt-mark hold php5-cgi');
      $this->exec('apt-mark hold php5-cli');
      $this->exec('apt-mark hold php5-readline');
      $this->exec('apt-mark hold php5-curl');
      $this->exec('apt-mark hold libapache2-mod-php5');
      $this->exec('apt-mark hold php5-mysql');
      $this->exec('apt-mark hold php5-odbc');
      $this->exec('apt-mark hold php5-gd');
      $this->exec('apt-mark hold php-pear');
      $this->exec('apt-mark hold php5');
      
      // Modify /var/lib/dpkg/status such that php5-odbc is not marked as
      // dependent on libiodbc2. Otherwise it will always complain
      // and will have to be resolved in order to install anything else.
      $status = file_get_contents('/var/lib/dpkg/status');
      $status = str_replace('Depends: libc6 (>= 2.14), libiodbc2 (>= 3.52.7), phpapi-20121212, php5-common (= 5.5.9+dfsg-1ubuntu4.4), ucf',
                            'Depends: libc6 (>= 2.14), phpapi-20121212, php5-common (= 5.5.9+dfsg-1ubuntu4.4), ucf',
                            $status);
      file_put_contents('/var/lib/dpkg/status', $status);
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      $this->exec('/etc/init.d/apache2 restart');      

      $this->chdir($this->currentWorkingDirectory);
    }    
    
    /**
    * Install PHP5 with the modifications required by OSF, from source code.
    * 
    * Use this only if the packaged version of PHP5 is not working for you.
    */
    public function installPhp5FromSource()
    {
      // Need to be implemented for 14.04
      // Reference: http://wiki.opensemanticframework.org/index.php/Recompile_PHP_with_iodbc
      $this->cecho("installPhp5FromSource function not implemented for 14.04. See  http://wiki.opensemanticframework.org/index.php/Recompile_PHP_with_iodbc ....\n", 'WHITE');
    }
    
    /**
    * Install Virtuoso as required by OSF
    */
    public function installVirtuoso()
    {

      $this->cecho("\n\n", 'WHITE');
      $this->cecho("---------------------\n", 'WHITE');
      $this->cecho(" Installing Virtuoso 7 from .deb file.... \n", 'WHITE');
      $this->cecho("---------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');   

      $this->wget('https://github.com/structureddynamics/OSF-Installer-Ext/raw/master/virtuoso-opensource/virtuoso-opensource_7.1_amd64.deb');
      $this->exec('dpkg -i virtuoso-opensource_7.1_amd64.deb');

      // Re-install php-odbc
      
      $this->exec('apt-mark unhold php5-odbc');
      
      $this->exec('dpkg -i --ignore-depends=libiodbc2 resources/php5/php5-odbc_5.5.9+dfsg-1ubuntu4.4_amd64.deb');      

      $status = file_get_contents('/var/lib/dpkg/status');
      $status = str_replace('Depends: libc6 (>= 2.14), libiodbc2 (>= 3.52.7), phpapi-20121212, php5-common (= 5.5.9+dfsg-1ubuntu4.4), ucf',
                            'Depends: libc6 (>= 2.14), phpapi-20121212, php5-common (= 5.5.9+dfsg-1ubuntu4.4), ucf',
                            $status);
      file_put_contents('/var/lib/dpkg/status', $status);
      
      $this->exec('apt-mark hold php5-odbc');

      // Then install libiodbc2 to support iodbctest
      
      $this->exec('mkdir -p /tmp/libodbc2-install/');
      
      $this->chdir('/tmp/libodbc2-install/');
      
      $this->exec('apt-get download libiodbc2');
      $this->exec('ar vx *odbc*.deb');
      $this->exec('tar -Jxvf data.tar.xz');
      $this->exec('cp usr/lib/* /usr/lib/');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('rm -rf /tmp/libodbc2-install/');
      // test that iodbc is used by php5

      $this->exec('mv /etc/init.d/virtuoso-opensource /etc/init.d/virtuoso');

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
        $this->cecho('Virtuoso is not running. Check the logs, something went wrong.', 'RED');
      }
      else
      {
        $this->cecho("Register Virtuoso to automatically start at the system's startup...\n", 'WHITE');
        $this->exec('sudo update-rc.d virtuoso defaults');

        $dbaPassword = $this->getInput("Enter a password to use with the Virtuoso administrator DBA & DAV users: ");
	      $errors = shell_exec('php resources/virtuoso/change_passwords.php "'.$dbaPassword.'"');
	
        if($errors == 'errors')
        {
          $dbaPassword = 'dba';
          $this->cecho("\n\nThe Virtuoso admin password was not changed. Use the default and change it after this installation process...\n", 'YELLOW');
        }        
        
        $this->cecho("Installing the exst() procedure...\n", 'WHITE');
        $this->exec('sed -i \'s>"dba", "dba">"dba", "'.$dbaPassword.'">\' "resources/virtuoso/install_exst.php"');
        $errors = shell_exec('php resources/virtuoso/install_exst.php');
        
        if($errors == 'errors')
        {
          $this->cecho("\n\nThe EXST() procedure could not be created. Try to create it after this installation process...\n", 'YELLOW');
        }        
      }
      
      // Configuring Virtuoso to be able to access the files from the DMT tool
      $this->cecho("Configuring virtuoso.ini...\n", 'WHITE');
      
      $this->exec('sed -i \'s>DirsAllowed.*= ., /usr/share/virtuoso/vad>DirsAllowed = ., /usr/share/virtuoso/vad, /usr/share/datasets-management-tool/data>\' "/var/lib/virtuoso/db/virtuoso.ini"');
      
      $this->cecho("Restarting Virtuoso...\n", 'WHITE');
      
      $this->exec('/etc/init.d/virtuoso stop');
      
      sleep(20);
      
      $this->exec('/etc/init.d/virtuoso start');      
            
      $this->cecho("You can start Virtuoso using this command: /etc/init.d/virtuoso start\n", 'LIGHT_BLUE');
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
      
      $this->cecho("Installing prerequirements...\n", 'WHITE');
      
      $this->exec('apt-get -y install openjdk-7-jdk');
      
      $this->cecho("Preparing installation...\n", 'WHITE');

      $this->exec('mkdir -p /tmp/solr-install/');
      
      $this->chdir('/tmp/solr-install/');
      
      $this->cecho("Downloading Solr...\n", 'WHITE');
      
      $this->wget('http://archive.apache.org/dist/lucene/solr/3.6.0/apache-solr-3.6.0.tgz');
      
      $this->cecho("Installing Solr...\n", 'WHITE');

      $this->exec('tar -xzvf apache-solr-3.6.0.tgz');

      $this->exec('mkdir -p /usr/share/solr');
      
      $this->exec('cp -af /tmp/solr-install/apache-solr-3.6.0/* /usr/share/solr/');
      
      $this->cecho("Configuring Solr...\n", 'WHITE');
      
      $this->chdir($this->currentWorkingDirectory);

      $this->exec('cp -f resources/solr/solr /etc/init.d/');
      
      $this->exec('chmod 755 /etc/init.d/solr');
      
      $this->exec('mv /usr/share/solr/example/ /usr/share/solr/osf-web-services/');
      
      $this->cecho("Installing SOLR-2155...\n", 'WHITE');
      
      $this->chdir('/usr/share/solr/dist/');
      
      $this->exec('wget -q https://github.com/downloads/dsmiley/SOLR-2155/Solr2155-1.0.5.jar');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('cp -af resources/solr/solrconfig.xml /usr/share/solr/osf-web-services/solr/conf/');

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
      
      // Need to use passthru because the installer prompts the user
      // with screens requiring input.
      // This command cannot be captured in the log.
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
      
      // Need to use passthru because the installer prompts the user
      // with screens requiring input.
      // This command cannot be captured in the log.
      passthru('apt-get -y install phpmyadmin');
    }       
    
    /**
    * Install Memcached as required by OSF
    */    
    public function installMemcached()
    {
      $this->cecho("\n\n", 'WHITE');
      $this->cecho("----------------------\n", 'WHITE');
      $this->cecho(" Installing Memcached \n", 'WHITE');
      $this->cecho("----------------------\n", 'WHITE');
      $this->cecho("\n\n", 'WHITE');

      $this->cecho("Installing Memcached...\n", 'WHITE');
      
      $this->exec('apt-get -y install memcached');      
      $this->exec('apt-get -y install php5-memcache');      
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');      
      
      $this->cecho("Starting Memcached...\n", 'WHITE');

      $this->exec('/etc/init.d/memcached restart');      
   
      $this->cecho("Installing Memcached User Interface...\n", 'WHITE');
      
      $this->chdir('/usr/share/');
      
      $this->exec('mkdir -p memcached-ui');      
      
      $this->chdir('/usr/share/memcached-ui/');
      
      $this->wget('http://artur.ejsmont.org/blog/misc/uploads/memcache_stats_v0.1.tgz');
      $this->exec('tar -xvf memcache_stats_v0.1.tgz');      
      
      $this->chdir('memcache_stats_v01/');      
      
      $this->exec('mv * ../');      
      
      $this->chdir('/usr/share/memcached-ui/');
      
      $this->exec('rm -rf memcache_stats_v01');      
      $this->exec('rm -rf *.tgz');      
      $this->exec('mv memcache.php index.php');      
      
      $adminPassword = $this->getInput("What is the password you want to use to log into the Memcached user interface for the 'admin' user? ");

      $this->exec('sed -i "s>define(\'ADMIN_PASSWORD\',\'pass\');>define(\'ADMIN_PASSWORD\',\''.$adminPassword.'\');>" index.php');
      
      $this->cecho("Configuring Apache2 for the Memcached User Interface...\n", 'WHITE');
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->exec('cp resources/memcached/memcached /etc/apache2/sites-available/memcached.conf');

      $this->exec('sudo ln -s /etc/apache2/sites-available/memcached.conf /etc/apache2/sites-enabled/memcached.conf');      
      
      $this->cecho("Restarting Apache2...\n", 'WHITE');
      
      $this->exec('/etc/init.d/apache2 restart');      
    }       
    
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

      $this->exec('mv /etc/apache2/sites-available/drupal /etc/apache2/sites-available/drupal.conf');

      $this->exec('sudo ln -s /etc/apache2/sites-available/drupal.conf /etc/apache2/sites-enabled/drupal.conf');
      
      // Fix the OSF Web Services path in the apache config file
      $this->exec('sudo sed -i "s>/usr/share/drupal>'.$this->drupal_folder.'>" "/etc/apache2/sites-available/drupal.conf"');
      
      // Delete the default Apache2 enabled site file
      if(file_exists('/etc/apache2/sites-enabled/000-default.conf'))
      {
        $this->exec('rm /etc/apache2/sites-enabled/000-default.conf', 'warning');
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
      
      $this->chdir($this->currentWorkingDirectory);
      
      $this->cecho("Now that OSF for Drupal is installed, the next steps would be to follow the Initial OSF for Drupal Configuration Guide:\n\n", 'CYAN');
      $this->cecho("    http://wiki.opensemanticframework.org/index.php/Initial_OSF_for_Drupal_Configuration\n\n", 'CYAN');
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
          
          if($version >= 14.04 && $version < 14.05)
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
