The Open Semantic Framework Installer script is used to install and deploy a OSF stack. It can also be used to install, upgrade and configure parts of the stack, or related external tools such as the Datasets Management Tool, the Ontologies Management Tool, the structWSF-PHP-API, etc.

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

Installation Options:
Note: the [VERSION] parameter is optional.
      If no version is specified, the latest DEV version will be used
--install-osf                           Install the Open Semantic Framework
--install-apache2                       Install Apache2
--install-mysql                         Install MySQL
--install-phpmyadmin                    Install PhpMyAdmin
--install-virtuoso                      Install Virtuoso
--install-solr                          Install Solr
--install-php5                          Install PHP5
--install-structwsf-php-api="[VERSION]             Install the structWSF-PHP-API library
--install-structwsf-tests-suites="[VERSION]        Install the structWSF tests suites
--install-datasets-management-tool="[VERSION]      Install the Datasets Management Tool
--install-ontologies-management-tool="[VERSION]    Install the Ontologies Management Tool

Upgrade Options:
Note: the [VERSION] parameter is optional.
      If no version is specified, the latest DEV version will be used
--upgrade-structwsf="[VERSION]"                     Upgrade structWSF
--upgrade-structwsf-php-api="[VERSION]"             Upgrade the structWSF-PHP-API library
--upgrade-structwsf-tests-suites="[VERSION]"        Upgrade the structWSF tests suites
--upgrade-datasets-management-tool="[VERSION]"      Upgrade the Datasets Management Tool
--upgrade-ontologies-management-tool="[VERSION]"    Upgrade the Ontologies Management Tool
```

Next Steps
----------
Once you have installed the OSF stack, you next query the [structWSF](http://techwiki.openstructs.org/index.php/StructWSF) Web service endpoints, and import datasets using [conStruct](http://techwiki.openstructs.org/index.php/ConStruct). Here are a few things you can do to start exploring the Open Semantic Framework:

* Start exploring [structWSF](http://techwiki.openstructs.org/index.php/Category:StructWSF)
* Start exploring [conStruct](http://techwiki.openstructs.org/index.php/Category:ConStruct)
* Start exploring [Ontologies usage in OSF](http://techwiki.openstructs.org/index.php/Category:Ontologies)
* Start [importing and manipulating datasets](http://techwiki.openstructs.org/index.php/Category:Datasets)
* Start exploring the [Open Semantic Framework architecture](http://techwiki.openstructs.org/index.php/Category:Open_Semantic_Framework)
* [Start playing with the structWSF web service endpoints](http://techwiki.openstructs.org/index.php/StructWSF_Web_Services_Tutorial).

When you are ready to begin developing and configuring your new instance in earnest, the best place to start is [A Basic Guide to Content](http://techwiki.openstructs.org/index.php/A_Basic_Guide_to_Content) on the TechWiki.

For More Help
-------------
If you are experiencing issues with this installation process, please do make an outreach to the [Open Semantic Web Mailing List](http://groups.google.com/group/open-semantic-framework).

Describe the specifications of the server where you are trying to install OSF. Tell us where the issue happens in the installation process. Also add any logs that could be helpful in debugging the issue.

