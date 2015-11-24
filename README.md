The Open Semantic Framework Installer script is used to install and deploy an OSF stack. It can also be used to install, upgrade, and configure parts of the stack, or related external tools such as the OSF Datasets Management Tool, the OSFOntologies Management Tool, the OSF WS PHP API, etc.

If you prefer, you can create a new OSF instance using a pre-packaged Amazon EC2 instance. Read the [Creating and Configuring an Amazon EC2 AMI OSF Instance](http://wiki.opensemanticframework.org/index.php/Creating_and_Configuring_an_Amazon_EC2_AMI_OSF_Instance) page to know how to create a OSF instance using this other method.

Requirements
------------
* Supported Linux Distributions:
	* CentOS 7
	* CentOS 6
	* Ubuntu 14.04
* PHP 5.4 or higher
* 64 Bit Operating System
* Access to internet from your server
* 5 GB of disk space on the partition where you are installing OSF
* 2 GB of RAM

Installing the Open Semantic Framework
--------------------------------------
To install OSF on your server, you first have to install the OSF Installer command line tool. You only have to run the following commands:
                       
```bash
mkdir -p /usr/share/osf-installer/

cd /usr/share/osf-installer/

wget https://raw.github.com/structureddynamics/Open-Semantic-Framework-Installer/3.4/install.sh

chmod 755 install.sh

./install.sh
```

Now that the OSF Installer is installed, you can configure the installation and install OSF:

```bash
./osf-installer -c --install-osf -v
```

Or you can simply run the installer, without configuring it, using the settings defined in the `installer.ini` configuration file:

```bash
./osf-installer --install-osf -v
```

When you install OSF using that command, then **no** input is required in the command line. This means that you can create _configuration profiles_ by updating the settings directly in the `installer.ini` file and use these values to automatically deploy OSF on your server. This kind of installation is normally used by automated deployment systems to automatically install and configure OSF on a server without requiring external inputs.


Subsequently, if you want to install OSF for Drupal on the same server, then you can run the following command:

```bash
./osf-installer -d --install-osf-drupal -v
```

Usage
-----
```
Usage: osf-install [OPTIONS]


General Options:
-h, --help                              Show this help section
-v, --verbose                           Make this installer verbose
-c, --configure-osf-installer           Configure the options used by this installer
-d, --configure-osf-drupal-installer    Configure the options used by this installer to install OSF for Drupal
--list-configurations                   List the current configuration used by the installer tool

OSF Installation Options:
Note: the [VERSION] parameter is optional.
      If no version is specified, the latest DEV version will be used

--install-osf                           Install the Open Semantic Framework
--install-apache                        Install Apache
--install-sql                           Install SQL
--install-phpmyadmin                    Install PhpMyAdmin
--install-virtuoso                      Install Virtuoso
--install-solr                          Install Solr
--install-php                           Install PHP
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

Configuration Options
---------------------

It is possible to create an *installation profile* for OSF by properly configuring the `installer.ini` file. All the options that are required by the OSF installer appears in that file. In this section, you will know how you can configure the options such that you can automatically deploy OSF on a server with this single command line:

```bash
./osf-installer --install-osf -v
```

All the options are described in the following table:

| Section          | Option                              | default value                            | note |
| -------          | ------                              | -------------                            | ---- |
| installer        |                                     |                                          |      |
|                  | version                             | _3.4.0_                                  | Current version of the OSF installer. **Do not change** |
|                  | osf-configured                      | _false_                                  | Specify if OSF as already been configured using the OSF installer on this instance. **Internal, Do not change** |
|                  | osf-drupal-configured               | _false_                                  | Specify if OSF for Drupal as already been configured using the OSF installer on this instance. **Internal, Do not change** |
|                  | auto-deploy                         | _false_                                  | Specify if the OSF Installer is used in an auto-deployment context. This is normally use if you are deploying OSF using a deployment framework such as `Chef`. When this option is configured to `true` then no question will be prompted to the terminal while installing. Also, if an error occur, the proces will die with a specific error code.|
|                  | upgrade-distro                      | _true_                                   | Specify if you want to make sure the latest version of every software of the Linux distribution are up to date. This happens before the installer starts deploying OSF on the server.|
| osf              |                                     |                                          | |
|                  | application-id                      | _administer_                             | Name of the first OSF `Application ID` to create in OSF |
|                  | api-key                             | _some-key_                               | The `API key` related ot the `application-id`. If the value `some-key` is specified, then a long random key will be generated by the installer and will be saved in the `keys.ini` file. |
|                  | data-folder                         | _/data_                                  | Main folder where most of the configuration files, ontologies files, etc. related to OSF will be saved on the server|
|                  | logging-folder                      | _/tmp_                                   | Temporary folder to be used by OSF |
| osf-web-services |                                     |                                          | |
|                  | osf-web-services-version            | _3.4.0_                                  | Version of OSF to deploy |
|                  | osf-web-services-folder             | _/usr/share/osf_                         | Folder where OSF web services will be deployed |
|                  | osf-web-services-domain             | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
| osf-components   |                                     |                                          | |
|                  | osf-ws-php-api-version              | _3.1.3_                                  | Version of the OSF PHP API to be deployed |
|                  | osf-ws-php-api-folder               | _StructuredDynamics/osf_                 | Folder where to deploy the OSF PHP API. It needs to be a subfolder of `osf-ws-php-api-folder` |
|                  | osf-tests-suites-version            | _3.4.0_                                  | Version of the OSF Tests Suites to deploy|
|                  | osf-tests-suites-folder             | _StructuredDynamics/osf/tests_           | Folder where to deploy the OSF tests suites. It needs to be a subfolder of `osf-ws-php-api-folder` |
|                  | data-validator-tool-version         | _3.1.0_                                  | Version of the data validator tool to deploy |
|                  | data-validator-tool-folder          | _StructuredDynamics/osf/validator_       | Folder where to deploy the data validator tool. It needs to be a subfolder of `osf-ws-php-api-folder` |
| osf-tools        |                                     |                                          | |
|                  | permissions-management-tool-version | _3.1.3_                                  | Version of the PMT to deploy |
|                  | permissions-management-tool-folder  | _/usr/share/permissions-management-tool_ | Folder location where to deploy the PMT |
|                  | datasets-management-tool-version    | _3.4.0_                                  | Version of the DMT to deploy |
|                  | datasets-management-tool-folder     | _/usr/share/datasets-management-tool_    | Folder location where to deploy the PMT |
|                  | ontologies-management-tool-version  | _3.1.0_                                  | Version of the OMT to deploy |
|                  | ontologies-management-tool-folder   | _/usr/share/ontologies-management-tool_  | Folder location where to deploy the PMT |
| osf-drupal       |                                     |                                          | |
|                  | drupal-version                      | _7.41_                                   | Drupal version to deploy |
|                  | drupal-folder                       | _/usr/share/drupal_                      | Folder location where to deploy Drupal|
|                  | drupal-domain                       | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | drupal-admin-username               | _admin_                                  | Name of the administrator username on Drupal |
|                  | drupal-admin-password               | _admin_                                  | Password of the administrator user on Drupal |
| sql              |                                     |                                          | |
|                  | sql-server                          | _mysql_                                  | SQL engine to use for Drupal. *should be `mysql`. Only one currently supported |
|                  | sql-host                            | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | sql-port                            | _3306_                                   | _reserved -- not yet used_ |
|                  | sql-root-username                   | _root_                                   | Name of the root user of the SQL server |
|                  | sql-root-password                   | _root_                                   | Password of the root user of the SQL server |
|                  | sql-app-username                    | _root_                                   | Username of the SQL server to use to install Drupal. By default it is root. |
|                  | sql-app-password                    | _root_                                   | Password of the SQL server to use to install Drupal. By default it is root. |
|                  | sql-app-database                    | _drupal_                                 | _reserved -- not yet used_ |
|                  | sql-app-engine                      | _innodb_                                 | _reserved -- not yet used_ |
|                  | sql-app-collation                   | _utf8_general_ci_                        | _reserved -- not yet used_ |
| sparql           |                                     |                                          | |
|                  | sparql-server                       | _virtuoso_                               | Triple store engine to use for OSF. *should be `virtuoso`. Only one currently supported |
|                  | sparql-channel                      | _odbc_                                   | Communication channel to use between the OSF web service endpoints and the triple store. Can be `odbc` or `http`|
|                  | sparql-dsn                          | _OSF-triples-store_                      | DSN name to use if `sparql-channel` is `odbc`|
|                  | sparql-host                         | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | sparql-port                         | _8890_                                   | Port of the SPARQL endpoint of the triple store|
|                  | sparql-url                          | _sparql_                                 | URL ending of the location of the SPARQL endpoint for the Triple Store|
|                  | sparql-graph-url                    | _sparql-graph-crud-auth_                 | URL ending of the location of the SPARQL graph crud auth endpoint for the Triple Store|
|                  | sparql-username                     | _dba_                                    | _reserved -- not yet used -- `dba` is currently used_ |
|                  | sparql-password                     | _dba_                                    | Password of the `sparql-username`|
| keycache         |                                     |                                          | |
|                  | keycache-enabled                    | _true_                                   | Specify if you want to enable or disable the key-caching system (like `memcached)`|
|                  | keycache-server                     | _memcached_                              | Keycaching system to use. Currently only `memecached` is supported. |
|                  | keycache-host                       | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | keycache-port                       | _11211_                                  | Port of the keycaching system|
|                  | keycache-ui-password                | _admin_                                  | Password of the keycaching system user interface administrator user|
| solr             |                                     |                                          | |
|                  | solr-host                           | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | solr-port                           | _8983_                                   | Port of the Solr server |
|                  | solr-core                           | _""_                                     | Name of the Solr core to use by this OSF instance. If this value is empty, then no core will be used. |
| owl              |                                     |                                          | |
|                  | owl-host                            | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | owl-port                            | _8080_                                   | Port of the Tomcat instance that host the OWLAPI library|
| scones           |                                     |                                          | |
|                  | scones-host                         | _localhost_                              | Domain name, or IP address of the OSF server. This domain name or IP address need to be accessible by the server that deploy OSF. |
|                  | scones-port                         | _8080_                                   | Port of the Tomcat instance that host the Scones application|
|                  | scones-url                          | _scones_                                 | URL ending where to read the Scones web service endpoints|


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

