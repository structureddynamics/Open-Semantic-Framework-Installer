The Open Semantic Framework Installer script is used to install and deploy a OSF stack. It can also be used to install, upgrade and configure parts of the stack, or related external tools such as the Datasets Management Tool, the Ontologies Management Tool, the structWSF-PHP-API, etc.

== Requirements ==
# Ubuntu 12.04 LTS (Precise Pangolin)
# PHP 5.3
# 32 or 64 Bits Operating System
# Access to internet from your server
# 5 GB of disk space on the partition where you are installing OSF

== Installing the Open Semantic Framework ==
The only steps needed to install the Open Semantic Framework are to:

== Usage ==
```
Usage: osf-install [OPTIONS]

General Options:
-h, --help                              Show this help section
-v, --verbose                           Make this insaller verbose

Installation Options:
--install-osf                           Install the Open Semantic Framework
--install-apache2                       Install Apache2
--install-structwsf-php-api             Install the structWSF-PHP-API library
--install-datasets-management-tool      Install the Datasets Management Tool
--install-ontologies-management-tool    Install the Ontologies Management Tool

Upgrade Options:
--upgrade-structwsf-php-api             Upgrade the structWSF-PHP-API library
--upgrade-datasets-management-tool      Upgrade the Datasets Management Tool
--upgrade-ontologies-management-tool    Upgrade the Ontologies Management Tool
```

== Next Steps ==
Once you have installed the OSF stack, you next query the [http://techwiki.openstructs.org/index.php/StructWSF structWSF] Web service endpoints, and import datasets using [http://techwiki.openstructs.org/index.php/ConStruct conStruct]. Here are a few things you can do to start exploring the Open Semantic Framework:

# Start exploring [http://techwiki.openstructs.org/index.php/Category:StructWSF structWSF]
# Start exploring [http://techwiki.openstructs.org/index.php/Category:ConStruct conStruct]
# Start exploring [http://techwiki.openstructs.org/index.php/Category:Ontologies Ontologies usage in OSF]
# Start [http://techwiki.openstructs.org/index.php/Category:Datasets importing and manipulating datasets]
# Start exploring the [http://techwiki.openstructs.org/index.php/Category:Open_Semantic_Framework Open Semantic Framework architecture]
# [http://techwiki.openstructs.org/index.php/StructWSF_Web_Services_Tutorial Start  playing with the structWSF web service endpoints].

When you are ready to begin developing and configuring your new instance in earnest, the best place to start is [http://techwiki.openstructs.org/index.php/A_Basic_Guide_to_Content A Basic Guide to Content] on the TechWiki.

== For More Help ==
If you are experiencing issues with this installation process, please do make an outreach to the [http://groups.google.com/group/open-semantic-framework Open Semantic Web Mailing List].

Describe the specifications of the server where you are trying to install OSF. Tell us where the issue happens in the installation process. Also add any logs that could be helpful in debugging the issue.

