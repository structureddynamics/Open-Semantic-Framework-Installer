<?php

  include_once('inc/OSFInstaller.php');

  class OSFInstallerGeneric extends OSFInstaller
  {
    /**
    * Install the entire OSF stack. Running this command will install the full stack on the server
    * according to the settings specified in the installer.ini file.
    */  
    
    /**
    * Prepare the installation for this distro
    */
    public function prepareDistro()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }       
    
    /**
    * Tries to install PHP5 using the packages available for the linux distribution
    */
    public function installPhp()
    { 
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }
    
    /**
    * Install Virtuoso as required by OSF
    */
    public function installVirtuoso()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }
    
    /**
    * Install Solr as required by OSF
    */
    public function installSolr()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }

    /**
    * Install Apache2 as required by OSF
    */
    public function installApache()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }

    /**
    * Install MySQL as required by OSF
    */
    public function installSQL()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }
    
    /**
    * Install MySQL as required by OSF
    */
    public function installPhpMyAdmin()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'RerrorED');
    }    
    
    /**
    * Install MySQL as required by OSF
    */
    public function installMemcached()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }    
    
    /**
    * Install OSF for Drupal
    */
    public function install_OSF_Drupal()
    {
      $this->span("Option not supported for this Linux distribution and version.", 'error');
    }        
  }
?>
