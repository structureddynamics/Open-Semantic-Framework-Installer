The Open Semantic Framework Installer script is used to install and deploy a OSF stack. It can also be used to install, upgrade and configure parts of the stack, or related external tools such as the OSF Datasets Management Tool, the OSFOntologies Management Tool, the OSF WS PHP API, etc.

Requirements
------------
* Ubuntu 12.10
* PHP 5.3 or higher
* 64 Bits Operating System
* Access to internet from your server
* 5 GB of disk space on the partition where you are installing OSF

Installing the Open Semantic Framework
--------------------------------------
The only steps needed to install the Open Semantic Framework are to:
                       
```
mkdir -p /usr/share/osf-installer/

cd /usr/share/osf-installer/

wget https://raw.github.com/structureddynamics/Open-Semantic-Framework-Installer/master/install.sh

chmod 755 install.sh

./install.sh

./osf-installer --install-osf -v
```

Usage
-----
```
Usage: osf-install [OPTIONS]


General Options:
-h, --help                              Show this help section
-v, --verbose                           Make this insaller verbose
-c, --configure-installer               Configure the options used by this installer
--list-configurations                   List the current configuration used by the installer tool

OSF Installation Options:
Note: the [VERSION] parameter is optional.
      If no version is specified, the latest DEV version will be used

--install-osf                           Install the Open Semantic Framework
--install-apache2                       Install Apache2
--install-mysql                         Install MySQL
--install-phpmyadmin                    Install PhpMyAdmin
--install-virtuoso                      Install Virtuoso
--install-solr                          Install Solr
--install-php5                          Install PHP5
--install-osf-ws-php-api="[VERSION]"                    Install the OSF-WS-PHP-API library
--install-osf-tests-suites="[VERSION]"                  Install the OSF Tests Suites
--install-osf-datasets-management-tool="[VERSION]"      Install the OSF Datasets Management Tool
--install-osf-permissions-management-tool="[VERSION]"   Install the OSF Permissions Management Tool
--install-osf-data-validator-tool="[VERSION]"           Install the OSF Data Validator Tool
--install-osf-ontologies-management-tool="[VERSION]"    Install the OSF Ontologies Management Tool

OSF Drupal Installation Options:

--install-osf-drupal                    Install Drupal with the OSF Drupal modules

Upgrade Options:
Note: the [VERSION] parameter is optional.
      If no version is specified, the latest DEV version will be used

--upgrade-osf-web-services="[VERSION]"               Upgrade the OSF Web Services
--upgrade-osf-ws-php-api="[VERSION]"                 Upgrade the OSF-WS-PHP-API library
--upgrade-osf-tests-suites="[VERSION]"               Upgrade the OSF Tests Suites
--upgrade-osf-datasets-management-tool="[VERSION]"   Upgrade the OSF Datasets Management Tool
--upgrade-osf-permissions-management-tool="[VERSION]"   Upgrade the OSF Permissions Management Tool
--upgrade-osf-data-validator-tool="[VERSION]"        Upgrade the OSF Data Validator Tool
--upgrade-osf-ontologies-management-tool="[VERSION]" Upgrade the OSF Ontologies Management Tool
```

Next Steps
----------
Once you have installed the OSF stack, you next query the [OSF Web Services](http://wiki.opensemanticframework.org/index.php/OSF_Web_Services)  endpoints, and import datasets using [OSF for Drupal](http://wiki.opensemanticframework.org/index.php/OSF_for_Drupal). Here are a few things you can do to start exploring the Open Semantic Framework:

* Start exploring [OSF Web Services](http://wiki.opensemanticframework.org/index.php/OSF_Web_Services)
* Start exploring [OSF for Drupal](http://wiki.opensemanticframework.org/index.php/OSF_for_Drupal)
* Start exploring [Ontologies usage in OSF](http://wiki.opensemanticframework.org/index.php/Category:Ontologies)
* Start [importing and manipulating datasets](http://wiki.opensemanticframework.org/index.php/Datasets_Management_Tool)
* Start exploring the [Open Semantic Framework architecture](http://wiki.opensemanticframework.org/index.php/OSF_Web_Service_Architecture)
* [Start using the OSF Web Services PHP API](http://wiki.opensemanticframework.org/index.php/OSF_Web_Service_PHP_API).

When you are ready to begin developing and configuring your new instance in earnest, the best place to start is [A Basic Guide to Content](http://wiki.opensemanticframework.org/index.php/A_Basic_Guide_to_Content) on the TechWiki.

For More Help
-------------
If you are experiencing issues with this installation process, please do make an outreach to the [Open Semantic Web Mailing List](http://groups.google.com/group/open-semantic-framework).

Describe the specifications of the server where you are trying to install OSF. Tell us where the issue happens in the installation process. Also add any logs that could be helpful in debugging the issue.

