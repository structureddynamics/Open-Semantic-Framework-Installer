<?php

  include_once('OSFInstaller.php');

  class OSFInstallerGeneric extends OSFInstaller
  {
    /**
    * Install the entire OSF stack. Running this command will install the full stack on the server
    * according to the settings specified in the installer.ini file.
    */  
    /**
    * Tries to install PHP5 using the packages available for the linux distribution
    */
    public function installPhp5()
    { 
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }
    
    /**
    * Install PHP5 with the modifications required by OSF, from source code.
    * 
    * Use this only if the packaged version of PHP5 is not working for you.
    */
    public function installPhp5FromSource()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }
    
    /**
    * Install Virtuoso as required by OSF
    */
    public function installVirtuoso()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }
    
    /**
    * Install Solr as required by OSF
    */
    public function installSolr()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }

    /**
    * Install Apache2 as required by OSF
    */
    public function installApache2()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }

    /**
    * Install MySQL as required by OSF
    */
    public function installMySQL()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }
    
    /**
    * Install MySQL as required by OSF
    */
    public function installPhpMyAdmin()
    {
      $this->cecho("Option not supported for this Linux distribution and version.\n", 'RED');
    }    
  }
?>
