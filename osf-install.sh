#!/bin/bash

# Default values for some of the core settings
VIRTUOSOVERSION="6.1.4"
DRUPALVERSION="6.26"
STRUCTWSFVERSION="1.1.0"
CONSTRUCTVERSION="6.x-1.0-beta10"
DATAFOLDER="/data"

INSTALLDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
NONFATALERRORS=""

VIRTUOSODOWNLOADURL="http://downloads.sourceforge.net/project/virtuoso/virtuoso/$VIRTUOSOVERSION/virtuoso-opensource-$VIRTUOSOVERSION.tar.gz"
STRUCTWSFDOWNLOADURL="https://github.com/downloads/structureddynamics/structWSF-Open-Semantic-Framework/structWSF-v$STRUCTWSFVERSION.zip"

# From: http://tldp.org/LDP/abs/html/colorizing.html
# Colorizing the installation process.

black='\E[1;30;40m'
red='\E[1;31;40m'
green='\E[1;32;40m'
yellow='\E[1;33;40m'
blue='\E[1;34;40m'
magenta='\E[1;35;40m'
cyan='\E[1;36;40m'
white='\E[1;37;40m'

cecho ()                     # Color-echo.
                             # Argument $1 = message
                             # Argument $2 = color
{
  local default_msg="No message passed."
                             # Doesn't really need to be a local variable.

  message=${1:-$default_msg}   # Defaults to default message.
  color=${2:-$white}           # Defaults to white, if not specified.

  echo -e "$color"
  echo -e "$message"
  
  tput sgr0                     # Reset to normal.

  return
}

# Current location: {install-dir}/

echo -e "\n\n"
cecho "----------" 
cecho " Welcome! "
cecho "----------"
echo -e "\n\n"

cecho "You are about to install the Open Semantic Framework.\n"
cecho "This installation process will install all the softwares that are part of the OSF stack. It will take 10 minutes of your time, but the process will go on for a few hours because all pieces of software that get compiled.\n\n"

cecho "  -> Everything that appears in **cyan** in the installation process, are important remarks and things to consider" $cyan
cecho "  -> Everything that appears in **purple** in the installation process, are questions requested by the installation manual." $magenta
cecho "  -> Everything that appears in **white** in the installation process, are processed undertaken by the installation script." $green
cecho "  -> Everything that appears in **green** in the installation process, are processes that successfully ended." $green
cecho "  -> Everything that appears in **yellow** in the installation process, are things that may have gone wrong and that needs investigation." $yellow
cecho "  -> Everything that appears in **red** in the installation process, are things that goes wrong and that needs immediate investigation in order to OSF to operate normally." $red
echo "  -> Everything else are processed ran by the installation script but that are performed by external installation programs and scripts."
cecho "\n\nCopyright 2008-12. Structured Dynamics LLC. All rights reserved.\n\n"

cecho "---------------------------------" 
cecho " General Settings Initialization " 
cecho "---------------------------------" 

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


echo -e "\n\n"
cecho "---------------------------"
cecho " 1. Installing prerequires "
cecho "---------------------------"
echo -e "\n\n"

cecho "We recommand you to upgrade all softwares of the server. Would you like to do this right now? (y/n):" $magenta

read

if [ ${REPLY,,} == "y" ]; then
  cecho "1.1) Updating the package registry\n"
  
  sudo apt-get update
  
  cecho "1.2) Upgrading the server\n"
  
  sudo apt-get upgrade
fi

# General requirements

sudo apt-get -y install curl gcc iodbc libssl-dev openssl unzip gawk vim default-jdk ftp-upload

echo -e "\n\n"
cecho "---------------------"
cecho " 2. Installing PHP 5 "
cecho "---------------------"
echo -e "\n\n"

cecho "\n\n2.1) Install required packages by PHP5...\n"

sudo apt-get -y install devscripts gcc debhelper fakeroot apache2-mpm-prefork hardening-wrapper libdb-dev libenchant-dev libglib2.0-dev libicu-dev libsqlite0-dev

# Make sure to fix a problem that throws unnecessary PHP notices for a badly commented line in mcrypt
sudo sed -i 's># configuration for php MCrypt module>; configuration for php MCrypt module>' /etc/php5/cli/conf.d/mcrypt.ini

cecho "2.2) Creating the folders for the compilation of PHP5...\n"

sudo mkdir update
cd update

# Current location: {install-dir}/update/

sudo mkdir build
cd build

# Current location: {install-dir}/update/build/

cecho "\n\n2.3) Getting the php5 source...\n"

sudo apt-get -y source php5

cd `ls -d php5*/`

# Current location: {install-dir}/update/build/php5-5.*/

# control files (from the tarball in progress)
# this way build-dep will

cecho "\n\n2.4) Copying the control.txt and the rules.txt files...\n"

sudo cp "$INSTALLDIR/php5/control.txt" ./debian/control && sudo cp "$INSTALLDIR/php5/rules.txt" ./debian/rules

cecho "\n\n2.5) Getting build-dep and php5...\n"

sudo apt-get -y build-dep php5

# conflict (from the build-dev) -> paranoid - next line removes it anyways

cecho "\n\n2.6) Removing unixodbc-dev...\n"

sudo apt-get -y remove unixodbc-dev

# and the object of the exercise

cecho "\n\n2.7) Installing iODBC...\n"

sudo apt-get -y install iodbc libiodbc2 libiodbc2-dev

# debuild flags added = -us -uc
# href=https://help.ubuntu.com/community/UpdatingADeb
# -us -uc bypasses signing of source package and .changes files

cecho "\n\n2.8) Running debuild...\n"

sudo debuild -us -uc

cecho "\n\n2.9) Moving to the php5/deb/ folder...\n"

cd ..

# Current location: {install-dir}/update/build/

cecho "\n\n2.10) Building the PHP5 packages...\n"

NEWVERSION=$(ls php5-common*.deb | echo $(sed s/php5-common//))
ALLVERSION=$(echo "$NEWVERSION" | echo $(sed s/i386/all/))

sudo dpkg -i php5-common"$NEWVERSION" php5-cgi"$NEWVERSION" php5-cli"$NEWVERSION" php5-curl"$NEWVERSION" libapache2-mod-php5"$NEWVERSION" php5-mysql"$NEWVERSION" php5-odbc"$NEWVERSION" php5-gd"$NEWVERSION" php5"$ALLVERSION"

cecho "\n\n2.11) Place dpkg hold on the custom packages...\n"

echo "php5-common hold " | sudo dpkg --set-selections && echo "php5-cgi hold" | sudo dpkg --set-selections && echo "php5-cli hold" | sudo dpkg --set-selections && echo "php5-curl hold" | sudo dpkg --set-selections && echo "libapache2-mod-php5 hold" | sudo dpkg --set-selections && echo "php5-mysql hold" | sudo dpkg --set-selections && echo "php5-odbc hold" | sudo dpkg --set-selections && echo "php5-gd hold" | sudo dpkg --set-selections && echo "php5 hold" | sudo dpkg --set-selections

cecho "\n\n2.12) Place aptitude/apt-get hold on the custom packages...\n"
sudo aptitude hold php5-common php5-cgi php5-cli php5-curl libapache2-mod-php5 php5-mysql php5-odbc php5-gd php5

cd ../..

# Current location: {install-dir}/

cecho "\n\n2.13) Clearning after php5 building...\n"

# clean up
sudo rm -r update




echo -e "\n\n"
cecho "-----------------------"
cecho " 3. Installing Apache2 "
cecho "-----------------------"
echo -e "\n\n"

sudo apt-get install -y apache2
sudo a2enmod rewrite
sudo /etc/init.d/apache2 restart

# some tests

cecho "\n\n3.1) Performing some tests on the new Apache2 instance...\n"

cecho "\n\n3.2) Checking if the Apache2 instance is up and running...\n"

CURLERROR=$( { curl http://localhost > outfile; } 2>&1 )

if [[ $CURLERROR = *curl:* ]]
then
  cecho "\n\nApache2 is not currently running...\n" $yellow
  NONFATALERRORS="$NONFATALERRORS \n [Error] Apache2 is not currently running...\n\n"
fi

if [[ $CURLERROR != *curl:* ]]
then
  cecho "3.3) \n\nChecking if the Apache2 instance is using IPv6...\n"

  NETSTATOUTPUT=$(netstat -tulpn | grep apache2)

  if [[ $NETSTATOUTPUT = *:::80* ]]
  then
    cecho "\n\nApache2 is running using IPv6. Check this web page for more information on what to do: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#IPv6_Not_Supported...\n" $red
	NONFATALERRORS="$NONFATALERRORS \n [Error] Apache2 is running using IPv6. Check this web page for more information on what to do: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#IPv6_Not_Supported...\n\n"
  fi
fi

# Fix a problem that throws unnecessary PHP notices for a badly commented line in mcrypt
sudo sed -i 's># configuration for php MCrypt module>; configuration for php MCrypt module>' /etc/php5/cli/conf.d/mcrypt.ini



echo -e "\n\n"
cecho "---------------------"
cecho " 4. Installing MySQL "
cecho "---------------------"
echo -e "\n\n"

cecho "\n\n4.1) Installing MySQL...\n"

sudo apt-get -y install mysql-server
sudo sed -r -i "s/; +extension=msql.so/extension=mysql.so/" /etc/php5/apache2/php.ini
sudo grep "extension=mysql.so" /etc/php5/apache2/php.ini

cecho "\n\n4.2) Restarting Apache2...\n"

sudo /etc/init.d/apache2 restart




echo -e "\n\n"
cecho "--------------------------"
cecho " 5. Installing PhpMyAdmin "
cecho "--------------------------"
echo -e "\n\n"

sudo apt-get -y install phpmyadmin




echo -e "\n\n"
cecho "------------------------"
cecho " 6. Installing Virtuoso "
cecho "------------------------"
echo -e "\n\n"

cecho "\n\n6.1) Set Virtuoso locale...\n"

sudo update-locale LANG=C LC_ALL=POSIX

cecho "\n\n6.2) Creating folder for building Virtuoso...\n"

sudo mkdir virtuoso-build

cd virtuoso-build

# Current location: {install-dir}/virtuoso-build/

# Make sure that the unixodbc-dev package is not installed.
sudo apt-get -y remove unixodbc-dev

cecho "\n\n6.3) Install required packages by Virtuso...\n"

sudo apt-get install -y curl gcc iodbc libssl-dev openssl unzip gawk vim default-jdk ftp-upload

sudo apt-get -y install libiodbc2 libreadline6 xml2 libxml2 libwbxml2-utils bison flex gperf unixodbc gawk libmagickcore2 libmagickwand2

sudo apt-get -y install build-essential libgcc1 libstdc++6 libc6 libz-dev libxml2-dev libwbxml2-dev autoconf automake libtool bison flex gperf gcc libiodbc2-dev libldap2-dev libreadline6-dev libssl-dev libmagickwand-dev libzip-dev libcsoap-dev

sudo apt-get -y install checkinstall debhelper fakeroot devscripts dpkg-dev

sudo update-alternatives --install /usr/bin/gmake gmake /usr/bin/make 10

cecho "\n\n6.4) Download Virtuoso source code...\n"

sudo wget $VIRTUOSODOWNLOADURL

cecho "\n\n6.5) Decompress Virtuoso source code...\n"

sudo tar -xzvf virtuoso-opensource-"$VIRTUOSOVERSION".tar.gz
sudo chown -R "$LOGNAME":"$LOGNAME" virtuoso-opensource-"$VIRTUOSOVERSION"

cd virtuoso-opensource-"$VIRTUOSOVERSION"

VIRTUOSOMAINVERSION=${VIRTUOSOVERSION:0:1}
VIRTUOSOMINORVERSION=${VIRTUOSOVERSION:4:1}

if [[ $VIRTUOSOMAINVERSION -eq 6 && $VIRTUOSOMINORVERSION -lt 5 ]]
then
  cecho "\n\n6.6) Patching...\n"

  cd "libsrc/Wi/"

  sudo cp "$INSTALLDIR"/virtuoso/spasql-php.diff spasql-php.diff

  sudo patch < spasql-php.diff

  cd ../..
fi

cecho "\n\n6.7) Building Virtuoso...\n"

set_flags () {
    CFLAGS="-O2"
    export CFLAGS
}

set_flags 
sudo ./autogen.sh 
set_flags 
sudo ./configure CFLAGS="$CFLAGS" --srcdir="$INSTALLDIR"/virtuoso-build/virtuoso-opensource-"$VIRTUOSOVERSION" --program-transform-name="s/isql/isql-v/" --enable-openssl=/usr --enable-openldap=/usr --disable-hslookup --enable-wbxml2=/usr --enable-imagemagick=/usr --enable-bpel-vad --with-iodbc=/usr --with-layout=debian --with-xml-prefix=/usr --with-xml-exec-prefix=/usr --without-internal-zlib --with-readline=/usr --disable-maintainer-mode 
sudo make 
set_flags 
sudo make check 
sudo make install

cecho "\n\n6.8) Cleaning Virtuoso building folder...\n"

sudo rm -rf virtuoso-build

cecho "\n\n6.9) Set Virtuoso locale...\n"

sudo update-locale LANG=en_CA.utf8 LC_ALL=en_CA.utf8

cecho "\n\n6.10) Installing odbc.ini and odbcinst.ini files...\n"

sudo cp "$INSTALLDIR"/virtuoso/odbc.ini /etc/odbc.ini
sudo cp "$INSTALLDIR"/virtuoso/odbcinst.ini /etc/odbcinst.ini

cecho "\n\n6.11) Install Virtuoso Startup Script...\n"

sudo cp "$INSTALLDIR"/virtuoso/virtuoso /etc/init.d/virtuoso

sudo chmod 744 /etc/init.d/virtuoso

cecho "\n\n6.12) Test Virtuoso startup script...\n"

sudo /etc/init.d/virtuoso stop
sudo /etc/init.d/virtuoso start

cecho "\n\n6.13) Check if Virtuoso is running...\n"

CHECKVIRTUOSO=$(ps aux | grep virtuoso)

if [[ $CHECKVIRTUOSO != *virtuoso* ]]
then
  cecho "\n\nVirtuoso is not currently running...\n" $yellow
  NONFATALERRORS="$NONFATALERRORS \n [Error] Virtuoso is not currently running...\n\n"
fi

cecho "\n\n6.14) Register Virtuoso to automatically start at the system's startup...\n"

sudo update-rc.d virtuoso defaults

cecho "\n\n6.15) Change Virtuoso Passwords...\n"

DBAPASSWORD="dba"
DAVPASSWORD="dav"

cecho "Password for the DBA user [without spaces] (default: $DBAPASSWORD):" $magenta

read -s  NEWDBAPASSWORD

if [ -n "$NEWDBAPASSWORD" ]
then

  cecho "Retype new password:" $magenta

  read -s RENEWDBAPASSWORD

  while [ $NEWDBAPASSWORD != $RENEWDBAPASSWORD ];do

    cecho "Password doesn't match, enter a new password again" $magenta

    cecho "New password:" $magenta

    read -s  NEWDBAPASSWORD

    cecho "Retype new password:" $magenta

    read -s RENEWDBAPASSWORD;done

  DBAPASSWORD=$NEWDBAPASSWORD
fi


#read NEWDBAPASSWORD

#[ -n "$NEWDBAPASSWORD" ] && DBAPASSWORD=$NEWDBAPASSWORD

cecho "Password for the DAV user [without spaces] (default: $DAVPASSWORD):" $magenta

read -s  NEWDAVPASSWORD

if [ -n "$NEWDAVPASSWORD" ]
then

  cecho "Retype new password:" $magenta

  read -s RENEWDAVPASSWORD

  while [ $NEWDAVPASSWORD != $RENEWDAVPASSWORD ];do

    cecho "Password doesn't match, enter a new password again" $magenta

    cecho "New password:" $magenta

    read -s  NEWDAVPASSWORD

    cecho "Retype new password:" $magenta

    read -s RENEWDAVPASSWORD;done

  DAVPASSWORD=$NEWDAVPASSWORD
fi

#read NEWDAVPASSWORD

#[ -n "$NEWDAVPASSWORD" ] && DAVPASSWORD=$NEWDAVPASSWORD


PHPERROR=$(php "$INSTALLDIR/virtuoso/change_passwords.php" $DBAPASSWORD $DAVPASSWORD)

if [[ $PHPERROR == errors ]]
then
  cecho "\n\nThe password for the DBA and DAV users couldn't be changed. Try to create them after this installation process referring you to these instructions: http://docs.openlinksw.com/virtuoso/newadminui.html...\n" $yellow
  NONFATALERRORS="$NONFATALERRORS \n [Error] The password for the DBA and DAV users couldn't be changed. Try to create them after this installation process referring you to these instructions: http://docs.openlinksw.com/virtuoso/newadminui.html...\n\n"
fi


cecho "\n\n6.16) Installing the exst() procedure...\n"

sudo sed -i 's>"dba", "dba">"dba", "'$DBAPASSWORD'">' "$INSTALLDIR/virtuoso/install_exst.php"

PHPERROR=$(php "$INSTALLDIR/virtuoso/install_exst.php")

if [[ $PHPERROR == errors ]]
then
  cecho "\n\nThe EXST() procedure couldn't be created. Try to create it after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Open_Virtuoso_Conductor...\n" $yellow
  NONFATALERRORS="$NONFATALERRORS \n [Error] The EXST() procedure couldn't be created. Try to create it after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Open_Virtuoso_Conductor...\n\n"
fi

cecho "\n\n6.17) Installing the logging procedures and tables...\n"

sudo sed -i 's>"dba", "dba">"dba", "'$DBAPASSWORD'">' "$INSTALLDIR/virtuoso/install_logging.php"

PHPERROR=$(php "$INSTALLDIR/virtuoso/install_logging.php")

if [[ $PHPERROR == errors ]]
then
  cecho "\n\nThe logging procedures couldn't be created. Try to create them after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Configure_Logger...\n" $yellow
  NONFATALERRORS="$NONFATALERRORS \n [Error] The logging procedures couldn't be created. Try to create them after this installation process referring you to these instructions: http://techwiki.openstructs.org/index.php/StructWSF_Installation_Guide#Configure_Logger...\n\n"
fi



echo -e "\n\n"
cecho "----------------------------"
cecho " 7. Installing Solr Locale "
cecho "----------------------------"
echo -e "\n\n"


cecho "\n\n7.1) Installing ant...\n"

sudo apt-get -y install ant 

cecho "\n\n7.2) Creating related directories...\n"

sudo mkdir /usr/share/apachesolr/
cd /usr/share/apachesolr/ 

# Current location: /usr/share/apachesolr/

cecho "\n\n7.3) Decompressing solr-locale in its system folder...\n"

sudo unzip "$INSTALLDIR/solr-locale/j-spatial.zip" 

cd geospatial-examples 

# Current location: /usr/share/apachesolr/geospatial-examples/

sudo mv * ../ 

cd .. 

# Current location: /usr/share/apachesolr/

sudo cp "$INSTALLDIR/solr-locale/sample-data.tar.gz" /var/www 

cecho "\n\n7.4) Configuring build.xml file...\n"

sudo cp "$INSTALLDIR/solr-locale/build.xml" build.xml  

cecho "\n\n7.5) Installing Solr-Locale...\n"

sudo sudo ant install  

cecho "\n\n7.6) Configuring Solr-Locale...\n"

sudo cp "$INSTALLDIR/solr-locale/schema.xml" /usr/share/apachesolr/solr/conf/schema.xml  

sudo cp "$INSTALLDIR/solr-locale/solrconfig.xml" /usr/share/apachesolr/solr/conf/solrconfig.xml  

cecho "\n\n7.7) Installing the Solr-Locale startup file...\n"

sudo cp "$INSTALLDIR/solr-locale/solr" /etc/init.d/solr  

sudo chmod 744 /etc/init.d/solr  

sudo /etc/init.d/solr restart  

cecho "\n\n7.8) Configuring Solr-Locale to start at the system's startup...\n"

sudo update-rc.d solr defaults  

sudo rm /var/www/sample-data.tar.gz 

cd $INSTALLDIR

# Current location: {install-dir}/













echo -e "\n\n"
cecho "----------------------"
cecho " 8. Installing OWLAPI "
cecho "----------------------"
echo -e "\n\n"

cecho "\n\n8.1) Installing Tomcat6...\n"

sudo apt-get -y install tomcat6  

cecho "\n\n8.2) Stopping Tomcat6...\n"

sudo /etc/init.d/tomcat6 stop  

cecho "\n\n8.3) Download the OWLAPI war installation file...\n"

cd owlapi

# Current location: {install-dir}/owlapi/

sudo wget http://techwiki.openstructs.org/files/OWLAPI.war  

cd ..

# Current location: {install-dir}/

cecho "\n\n8.4) Install the OWLAPI service in Tomcat6...\n"

sudo cp "$INSTALLDIR/owlapi/OWLAPI.war" /var/lib/tomcat6/webapps/  

cecho "\n\n8.5) Starting Tomcat6 to install the OWLAPI war installation file...\n"

sudo /etc/init.d/tomcat6 start  

# @TODO wait until /var/lib/tomcat6/webapps/OWLAPI folder is created
# ls /var/lib/tomcat6/webapps/OWLAPI

sleep 20

cecho "\n\n8.6) Configuring PHP for the OWLAPI...\n"
sudo sed -i "s/allow_url_include = Off/allow_url_include = On/" /etc/php5/apache2/php.ini 

sudo sed -i 's/allow_call_time_pass_reference = Off/allow_call_time_pass_reference = On/' /etc/php5/apache2/php.ini

cecho "\n\n8.7) Restart Apache2...\n"

sudo /etc/init.d/apache2 restart  

cecho "\n\n8.8) Create Data & Ontologies folders...\n"

sudo mkdir -p "$DATAFOLDER/ontologies/files/"
sudo mkdir -p "$DATAFOLDER/ontologies/structure/"

cecho "\n\n8.9) Download the core OSF ontologies files...\n"

cd "$DATAFOLDER/ontologies/files/"

# Current location: {data-folder}/ontologies/files/

sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/aggr/aggr.owl  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/iron/iron.owl  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/owl/owl.rdf  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdf.xml  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/rdf/rdfs.xml  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/sco/sco.owl  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wgs84/wgs84.owl  
sudo wget https://raw.github.com/structureddynamics/Ontologies-Open-Semantic-Framework/master/wsf/wsf.owl  

cecho "\n\n8.10) Move the default classes and properties PHP structure files into the proper folder...\n"

sudo cp "$INSTALLDIR/owlapi/classHierarchySerialized.srz" "$DATAFOLDER/ontologies/structure/classHierarchySerialized.srz"  
sudo cp "$INSTALLDIR/owlapi/propertyHierarchySerialized.srz" "$DATAFOLDER/ontologies/structure/propertyHierarchySerialized.srz"  
sudo cp "$INSTALLDIR/owlapi/new.owl" "$DATAFOLDER/ontologies/files/new.owl"  

cecho "\n\n8.11) Set the properly ontologies folder rights...\n"

sudo chmod -R 777 "$DATAFOLDER/ontologies"  






echo -e "\n\n"
cecho "-------------------------"
cecho " 9. Installing structWSF "
cecho "-------------------------"
echo -e "\n\n"

STRUCTWSFFOLDER="/usr/share/structwsf/"

cecho "Where do you want to install structWSF on your server (default: $STRUCTWSFFOLDER):" $magenta

read NEWSTRUCTWSFFOLDER

[ -n "$NEWSTRUCTWSFFOLDER" ] && STRUCTWSFFOLDER=$NEWSTRUCTWSFFOLDER

# Make sure there is no trailing slashes
STRUCTWSFFOLDER=$(echo "${STRUCTWSFFOLDER}" | sed -e "s/\/*$//")

cecho "\n\n9.1) Creating the structWSF installation folder...\n"

sudo mkdir -p "$DATAFOLDER/structwsf/tmp"
sudo chmod 777 "$DATAFOLDER/structwsf/tmp"

sudo mkdir -p $STRUCTWSFFOLDER

cd $STRUCTWSFFOLDER

# Current location: /usr/share/structwsf/

cecho "\n\n9.2) Download structWSF...\n"

sudo wget $STRUCTWSFDOWNLOADURL  

cecho "\n\n9.3) Decompressing structWSF...\n"

sudo unzip "structWSF-v$STRUCTWSFVERSION.zip"  

cd `ls -d structureddynamics*/`

# Current location: /usr/share/structwsf/structureddynamics-structWSF-Open-Semantic-Framework-38bc267/

sudo mv * ../

cd ../

# Current location: /usr/share/structwsf/

sudo rm -rf `ls -d structureddynamics*/`

cecho "\n\n9.4) Fixing the index.php file to refer to the proper SID folder...\n"

sudo sed -i 's>$sidDirectory = "";>$sidDirectory = "'$DATAFOLDER'/structwsf/tmp/";>' "$STRUCTWSFFOLDER/index.php"

cecho "\n\n9.5) Configure Apache2 for structWSF...\n"

sudo cp "$INSTALLDIR/structwsf/structwsf" /etc/apache2/sites-available/structwsf

sudo cp "$INSTALLDIR/structwsf/structwsf" /etc/apache2/sites-available/structwsf

sudo ln -s /etc/apache2/sites-available/structwsf /etc/apache2/sites-enabled/structwsf

cecho "\n\n9.6) Move the data.ini and the network.ini files into the data folder...\n"

sudo mv "$STRUCTWSFFOLDER/data.ini" "$DATAFOLDER/data.ini"

sudo mv "$STRUCTWSFFOLDER/network.ini" "$DATAFOLDER/network.ini"

# Fix the structWSF path in the apache config file
sudo sed -i "s>/usr/share/structwsf>"$STRUCTWSFFOLDER">" "/etc/apache2/sites-available/structwsf"


cecho "\n\n9.7) Restarting Apache2...\n"

sudo /etc/init.d/apache2 restart

cecho "\n\n9.8) Configure the WebService.php file...\n"

sudo sed -i 's>public static $data_ini = "/data/";>public static $data_ini = "'$DATAFOLDER'/";>' "$STRUCTWSFFOLDER/framework/WebService.php"
sudo sed -i 's>public static $network_ini = "/usr/share/structwsf/";>public static $network_ini = "'$DATAFOLDER'/";>' "$STRUCTWSFFOLDER/framework/WebService.php"

cecho "\n\n9.9) Configure the data.ini configuration file...\n"

DOMAINNAME="localhost"

cecho "---------------------------------" $red
cecho "NOTE: if the domain name you are about to enter here is not currently configured for this server, do take care to edit the /etc/hosts file to handle this domain, otherwise the installer will throws a series of error later in the process." $red
cecho "---------------------------------" $red

cecho "What is the domain name where the structWSF instance will be accessible (default: $DOMAINNAME):" $magenta

read NEWDOMAINNAME

[ -n "$NEWDOMAINNAME" ] && DOMAINNAME=$NEWDOMAINNAME

# fix wsf_graph
sudo sed -i "s>wsf_graph = \"http://localhost/wsf/\">wsf_graph = \"http://"$DOMAINNAME"/wsf/\">" "$DATAFOLDER/data.ini"

# fix dtd_base
sudo sed -i "s>dtd_base = \"http://localhost/ws/dtd/\">dtd_base = \"http://"$DOMAINNAME"/ws/dtd/\">" "$DATAFOLDER/data.ini"

#fix ontologies_files_folder
sudo sed -i "s>ontologies_files_folder = \"/data/ontologies/files/\">ontologies_files_folder = \""$DATAFOLDER"/ontologies/files/\">" "$DATAFOLDER/data.ini"

#fix ontological_structure_folder
sudo sed -i "s>ontological_structure_folder = \"/data/ontologies/structure/\">ontological_structure_folder = \""$DATAFOLDER"/ontologies/structure/\">" "$DATAFOLDER/data.ini"

#fix password
sudo sed -i "s>password = \"dba\">password = \""$DBAPASSWORD"\">" "$DATAFOLDER/data.ini"

#fix host
sudo sed -i "s>host = \"localhost\">host = \""$DOMAINNAME"\">" "$DATAFOLDER/data.ini"

#fix fields_index_folder
sudo sed -i "s>fields_index_folder = \"/tmp/\">fields_index_folder = \""$DATAFOLDER"/structwsf/tmp/\">" "$DATAFOLDER/data.ini"


#fix wsf_base_url
sudo sed -i "s>wsf_base_url = \"http://localhost\">wsf_base_url = \"http://"$DOMAINNAME"\">" "$DATAFOLDER/network.ini"

#fix wsf_base_path
sudo sed -i "s>wsf_base_path = \"/usr/share/structwsf/\">wsf_base_path = \""$STRUCTWSFFOLDER"/\">" "$DATAFOLDER/network.ini"

# fix virtuoso_main_version

if [[ $VIRTUOSOMAINVERSION -eq 5 ]]
then
  sudo sed -i "s>virtuoso_main_version = \"6\">virtuoso_main_version = \"5\">" "$DATAFOLDER/data.ini"
fi

if [[ $VIRTUOSOMAINVERSION -eq 4 ]]
then
  sudo sed -i "s>virtuoso_main_version = \"6\">virtuoso_main_version = \"4\">" "$DATAFOLDER/data.ini"
fi

# fix enable_lrl

if [[ $VIRTUOSOMAINVERSION -gt 5 ]]
then
  sudo sed -i "s>enable_lrl = \"FALSE\">enable_lrl = \"TRUE\">" "$DATAFOLDER/data.ini"
fi


cecho "Do you want to enable logging in structWSF? (y/n) (default: y):" $magenta

read

if [ ${REPLY,,} == "n" ]; then
  #fix log_enable
  sudo sed -i "s>log_enable = \"true\">log_enable = \"false\">" "$DATAFOLDER/network.ini"
fi

cecho "Do you want to enable changes tracking for the CRUD: Create web service endpoint? (y/n) (default: n):" $magenta

read

if [ ${REPLY,,} == "y" ]; then
  #fix track_create
  sudo sed -i "s>track_create = \"false\">track_create = \"true\">" "$DATAFOLDER/network.ini"
fi

cecho "Do you want to enable changes tracking for the CRUD: Update web service endpoint? (y/n) (default: n):" $magenta

read

if [ ${REPLY,,} == "y" ]; then
  #fix track_update
  sudo sed -i "s>track_update = \"false\">track_update = \"true\">" "$DATAFOLDER/network.ini"
fi

cecho "Do you want to enable changes tracking for the CRUD: Delete web service endpoint? (y/n) (default: n):" $magenta

read

if [ ${REPLY,,} == "y" ]; then
  #fix track_delete
  sudo sed -i "s>track_delete = \"false\">track_delete = \"true\">" "$DATAFOLDER/network.ini"
fi

cecho "Do you want to geo-enable structWSF? (y/n) (default: n):" $magenta

read

if [ ${REPLY,,} == "y" ]; then
  #fix geoenabled
  sudo sed -i "s>geoenabled = \"false\">geoenabled = \"true\">" "$DATAFOLDER/network.ini"
fi

cecho "9.9) \n\nSet files owner permissions...\n"

sudo chown -R www-data:www-data $STRUCTWSFFOLDER
sudo chmod -R 755 $STRUCTWSFFOLDER
sudo /etc/init.d/apache2 restart

cecho "\n\n9.10) Cleaning the structWSF installation folder...\n"

sudo rm "structWSF-v$STRUCTWSFVERSION.zip"


echo -e "\n\n"
cecho "---------------------"
cecho " 10. Installing ARC2 "
cecho "---------------------"
echo -e "\n\n"

cd "$STRUCTWSFFOLDER/framework/arc2"

# Current location: {structwsf}/framework/arc2/

sudo wget https://github.com/semsol/arc2/zipball/v2.0.0

sudo unzip v2.0.0

cd `ls -d semsol*/`

# Current location: {structwsf}/framework/arc2/semsol-arc2-09f16fd/

sudo mv * ../

cd ../

# Current location: {structwsf}/framework/arc2/

sudo rm -rf `ls -d semsol*/`

sudo rm v2.0.0


echo -e "\n\n"
cecho "----------------------------"
cecho " 11. Create the WSF Network "
cecho "----------------------------"
echo -e "\n\n"

cecho "\n\n11.1) Reset WSF...\n"

sudo curl 'http://'$DOMAINNAME'/ws/auth/wsf_indexer.php?action=reset'

cecho "\n\n11.2) Create WSF...\n"

sudo curl 'http://'$DOMAINNAME'/ws/auth/wsf_indexer.php?action=create_wsf&server_address=http://'$DOMAINNAME

DOMAINIP=$(ping -c 1 $DOMAINNAME | grep -E -o "[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}" | head -1)

cecho "\n\n11.3) Create user full access for (server's own external IP): "$DOMAINIP" ...\n"

sudo curl 'http://'$DOMAINNAME'/ws/auth/wsf_indexer.php?action=create_user_full_access&user_address='$DOMAINIP'&server_address=http://'$DOMAINNAME

cecho "\n\n11.4) Create user full access for: 127.0.0.1 ...\n"

sudo curl 'http://'$DOMAINNAME'/ws/auth/wsf_indexer.php?action=create_user_full_access&user_address=127.0.0.1&server_address=http://'$DOMAINNAME

cecho "\n\n11.5) Create world readable dataset read...\n"

sudo curl 'http://'$DOMAINNAME'/ws/auth/wsf_indexer.php?action=create_world_readable_dataset_read&server_address=http://'$DOMAINNAME

cecho "\n\n11.6) Commit transactions to Virtuoso...\n"

sudo sed -i 's>"dba", "dba">"dba", "'$DBAPASSWORD'">' "$INSTALLDIR/virtuoso/commit.php"

PHPERROR=$(php "$INSTALLDIR/virtuoso/commit.php")

if [[ $PHPERROR == errors ]]
then
  cecho "\n\nCouldn't commit triples to the Virtuoso triples store...\n" $red
  NONFATALERRORS="$NONFATALERRORS \n [Error] Couldn't commit triples to the Virtuoso triples store...\n\n"
fi

cecho "\n\n11.7) Rename the wsf_indexer.php script with a random name for security purposes...\n"

if [ -n "$1" ]  #  If command-line argument present,
then            #+ then set start-string to it.
  str0="$1"
else            #  Else use PID of script as start-string.
  str0="$$"
fi

POS=2  # Starting from position 2 in the string.
LEN=8  # Extract eight characters.

str1=$( echo "$str0" | md5sum | md5sum )
# Doubly scramble:     ^^^^^^   ^^^^^^

randstring="${str1:$POS:$LEN}"
# Can parameterize ^^^^ ^^^^

sudo mv $STRUCTWSFFOLDER"/auth/wsf_indexer.php" $STRUCTWSFFOLDER"/auth/wsf_indexer_$randstring.php"






echo -e "\n\n"
cecho "------------------------"
cecho " 12. Installing PHPUnit "
cecho "------------------------"
echo -e "\n\n"

sudo apt-get install -y php-pear

pear channel-discover pear.phpunit.de
pear channel-discover pear.symfony-project.com
pear upgrade-all

sudo pear install --force --alldeps phpunit/PHPUnit

cd $INSTALLDIR"/tests/"

cecho "\n\n12.1) Download the latest system integration tests for structWSF...\n"

sudo wget https://github.com/structureddynamics/structWSF-Tests-Suites/zipball/1.1

unzip 1.1

cd `ls -d structureddynamics*/`

sudo mv * ../

cd ..

sudo rm -rf `ls -d structureddynamics*/`

cecho "\n\n12.2) Configure tests...\n"

sudo sed -i "s>REPLACEME>"$INSTALLDIR">" phpunit.xml

sudo sed -i "s>$this-\>structwsfInstanceFolder = \"/usr/share/structwsf/\";>$this-\>structwsfInstanceFolder = \""$STRUCTWSFFOLDER"/\";>" Config.php

sudo sed -i "s>$this-\>endpointUrl = \"http://localhost/ws/\";>$this-\>endpointUrl = \"http://"$DOMAINNAME"/ws/\";>" Config.php

sudo sed -i "s>$this-\>endpointUri = \"http://localhost/wsf/ws/\";>$this-\>endpointUri = \"http://"$DOMAINNAME"/wsf/ws/\";>" Config.php


cecho "\n\n12.3) Run the system integration tests suites...\n"

sudo phpunit --configuration phpunit.xml --verbose --colors --log-junit log.xml

cecho "\n\n=============================\nIf errors are reported after these tests, please check the "$INSTALLDIR"/tests/log.xml file to see where the errors come from. If you have any question that you want to report on the mailing list, please do include that file in your email: http://groups.google.com/group/open-semantic-framework\n=============================\n\n"







echo -e "\n\n"
cecho "--------------------------"
cecho " 13. Installing conStruct "
cecho "--------------------------"
echo -e "\n\n"

sudo apt-get remove drush

DRUPALFOLDER="/usr/share/drupal"

cecho "Where do you want to install Drupal (and conStruct) on your server (default: $DRUPALFOLDER):" $magenta

read NEWDRUPALFOLDER

[ -n "$NEWDRUPALFOLDER" ] && DRUPALFOLDER=$NEWDRUPALFOLDER

# Make sure there is no trailing slashes
DRUPALFOLDER=$(echo "${DRUPALFOLDER}" | sed -e "s/\/*$//")

cecho "\n\n13.1) Creating the Drupal folder structure...\n"

sudo mkdir -p $DRUPALFOLDER

cd $DRUPALFOLDER

cecho "\n\n13.2) Configuring Apache2 for Drupal...\n"

sudo cp $INSTALLDIR"/drupal/default" /etc/apache2/sites-available/default

# Fix the Drupal path in the apache config file
sudo sed -i "s>/usr/share/drupal>"$DRUPALFOLDER">" "/etc/apache2/sites-available/default"

sudo cp $INSTALLDIR"/drupal/htaccess" $DRUPALFOLDER"/.htaccess"

sudo /etc/init.d/apache2 restart

# Make sure to fix a problem that throws unnecessary PHP notices for a badly commented line in mcrypt
sudo sed -i 's># configuration for php MCrypt module>; configuration for php MCrypt module>' /etc/php5/cli/conf.d/mcrypt.ini

cecho "\n\n13.3) Installing Drush...\n"

sudo pear upgrade -force Console_Getopt
sudo pear upgrade -force pear
pear upgrade-all

pear channel-discover pear.drush.org

# Make sure drush 5.0.0 is not installed
# It won't work with conStruct's module with upper case letters
sudo pear uninstall drush/drush-5.0.0

sudo pear install drush/drush-4.6.0


# Install drupal using Drush

sudo drush dl "drupal-"$DRUPALVERSION

cd "drupal-"$DRUPALVERSION

sudo sudo mv * ../

cd ..

sudo rm -rf "drupal-"$DRUPALVERSION

cecho "\n\n13.4) Fix Drupal such that updates works with conStruct (fix the upper case issue with Drupal Modules update)...\n"

sudo sed -i "s>function drupal_get_schema_versions(\$module) {>function drupal_get_schema_versions(\$module) { \$module = strtolower(\$module);>" includes/install.inc

cp sites/default/default.settings.php sites/default/settings.php

DRUPALUSERNAME="root"
DRUPALPASSWORD="root"

cecho "What is the MySQL username used for drupal (default username: $DRUPALUSERNAME):" $magenta

read NEWDRUPALUSERNAME

[ -n "$NEWDRUPALUSERNAME" ] && DRUPALUSERNAME=$NEWDRUPALUSERNAME

cecho "What is the MySQL password for that username [without spaces] (default password: $DRUPALPASSWORD):" $magenta

read -s  NEWDRUPALPASSWORD

if [ -n "$NEWDRUPALPASSWORD" ]
then

  cecho "Retype new password:" $magenta

  read -s RENEWDRUPALPASSWORD

  while [ $NEWDRUPALPASSWORD != $RENEWDRUPALPASSWORD ];do

    cecho "Password doesn't match, enter a new password again" $magenta

    cecho "New password:" $magenta

    cread -s  NEWDRUPALPASSWORD $magenta

    cecho "Retype new password:" $magenta

    read -s RENEWDRUPALPASSWORD;done

  DRUPALPASSWORD=$NEWDRUPALPASSWORD
fi

#read NEWDRUPALPASSWORD

#[ -n "$NEWDRUPALPASSWORD" ] && DRUPALPASSWORD=$NEWDRUPALPASSWORD

sudo mysqladmin -u $DRUPALUSERNAME --password=$DRUPALPASSWORD create "drupal_construct"

sudo sed -i "s>$db_url = 'mysql://username:password@localhost/databasename';>$db_url = 'mysql://"$DRUPALUSERNAME":"$NEWDRUPALPASSWORD"@localhost/drupal_construct';>" $DRUPALFOLDER"/sites/default/settings.php"

sudo chmod a+w sites/default/settings.php

sudo chmod a+w sites/default


DRUPALADMINUSERNAME="admin"
DRUPALADMINUSERNAMEPASSWORD="admin"
DRUPALSITEMAIL="webmaster@localhost"

cecho "What username do you want to use for the Drupal admin user? (default: $DRUPALADMINUSERNAME):" $magenta

read NEWDRUPALADMINUSERNAME

[ -n "$NEWDRUPALADMINUSERNAME" ] && DRUPALADMINUSERNAME=$NEWDRUPALADMINUSERNAME


cecho "What password do you want to use for the Drupal admin user? (default: $DRUPALADMINUSERNAMEPASSWORD):" $magenta

read -s  NEWDRUPALADMINUSERNAMEPASSWORD

if [ -n "$NEWDRUPALADMINUSERNAMEPASSWORD" ]
then

  cecho "Retype new password:" $magenta

  read -s RENEWDRUPALADMINUSERNAMEPASSWORD

  while [ $NEWDRUPALADMINUSERNAMEPASSWORD != $RENEWDRUPALADMINUSERNAMEPASSWORD ];do

    cecho "Password doesn't match, enter a new password again" $magenta

    cecho "New password:" $magenta

    read -s  NEWDRUPALADMINUSERNAMEPASSWORD

    cecho "Retype new password:" $magenta

    read -s RENEWDRUPALADMINUSERNAMEPASSWORD;done

  DRUPALADMINUSERNAMEPASSWORD=$NEWDRUPALADMINUSERNAMEPASSWORD
fi

#read NEWDRUPALADMINUSERNAMEPASSWORD

#[ -n "$NEWDRUPALADMINUSERNAMEPASSWORD" ] && DRUPALADMINUSERNAMEPASSWORD=$NEWDRUPALADMINUSERNAMEPASSWORD


cecho "What is the email of the drupal site? (default: $DRUPALSITEMAIL):" $magenta

read NEWDRUPALSITEMAIL

[ -n "$NEWDRUPALSITEMAIL" ] && DRUPALSITEMAIL=$NEWDRUPALSITEMAIL


sudo drush site-install --site-name=$DOMAINNAME --site-mail=$DRUPALSITEMAIL --account-name=$DRUPALADMINUSERNAME --account-pass=$DRUPALADMINUSERNAMEPASSWORD


cecho "\n\n13.5) Install a new cck-import script for Drush...\n"

sudo cp $INSTALLDIR"/drupal/cck_import.drush.inc" /usr/share/php/drush/commands/

cecho "\n\n13.6) Install all needed Drupal modules...\n"

# Install all modules

sudo drush dl permissions_api
sudo drush en -y permissions_api

sudo drush dl views
sudo drush en -y views

sudo drush dl og
sudo drush en -y og

sudo drush dl og_user_roles
sudo drush en -y og_user_roles

sudo drush dl cck
sudo drush en -y content

sudo drush en -y optionwidgets

sudo drush dl construct-$CONSTRUCTVERSION

# Rename "construct" to "conStruct" before enabling it

if [ -d sites/all/modules/construct ]
then 
  sudo mv sites/all/modules/construct sites/all/modules/conStruct
fi

sudo drush en -y conStruct
sudo drush en -y structSearch
sudo drush en -y structBrowse
sudo drush en -y structOntology
sudo drush en -y structView
sudo drush en -y structResource
sudo drush en -y structAppend
sudo drush en -y structCreate
sudo drush en -y structDataset
sudo drush en -y structDelete
sudo drush en -y structExport
sudo drush en -y structImport
sudo drush en -y structNetworks
sudo drush en -y structScones
sudo drush en -y structUpdate

cecho "\n\n13.7) Create the core Drupal roles...\n"

sudo drush -u 1 role-create 'admin'
sudo drush -u 1 role-create 'contributor'
sudo drush -u 1 role-create 'owner/curator'

cecho "\n\n13.8) Creating the permissions, per role and per module...\n"

sudo drush perm-grant --roles='anonymous user' --permissions='access comments' 
sudo drush perm-grant --roles='authenticated user' --permissions='access comments' 
sudo drush perm-grant --roles='contributor' --permissions='access comments' 
sudo drush perm-grant --roles='owner/curator' --permissions='access comments' 
sudo drush perm-grant --roles='admin' --permissions='access comments' 

sudo drush perm-grant --roles='authenticated user' --permissions='post comments' 
sudo drush perm-grant --roles='contributor' --permissions='post comments' 
sudo drush perm-grant --roles='owner/curator' --permissions='post comments' 
sudo drush perm-grant --roles='admin' --permissions='post comments' 

sudo drush perm-grant --roles='contributor' --permissions='post comments without approval' 
sudo drush perm-grant --roles='owner/curator' --permissions='post comments without approval' 
sudo drush perm-grant --roles='admin' --permissions='post comments without approval' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct' 

sudo drush perm-grant --roles='anonymous user' --permissions='access content' 
sudo drush perm-grant --roles='authenticated user' --permissions='access content' 
sudo drush perm-grant --roles='contributor' --permissions='access content' 
sudo drush perm-grant --roles='owner/curator' --permissions='access content' 
sudo drush perm-grant --roles='admin' --permissions='access content' 

# These are not currently settable using Drush.
#sudo drush perm-grant 'contributor' 'create dataset content' 
#sudo drush perm-grant 'owner/curator' 'create dataset content' 
#sudo drush perm-grant 'contributor' 'delete own dataset content' 
#sudo drush perm-grant 'owner/curator' 'delete own dataset content' 
#sudo drush perm-grant 'contributor' 'edit own dataset content' 
#sudo drush perm-grant 'owner/curator' 'edit own dataset content' 

sudo drush perm-grant --roles='admin' --permissions='access conStruct append' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct append' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct append' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct browse' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct browse' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct browse' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct browse' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct browse' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct dataset' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct dataset' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct dataset' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct create' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct create' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct create' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct delete' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct delete' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct delete' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct export' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct export' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct export' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct export' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct export' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct import' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct import' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct import' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct ontology' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct ontology' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct ontology' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct resource' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct resource' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct resource' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct resource' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct resource' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct scones' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct scones' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct scones' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct search' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct search' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct search' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct search' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct search' 

sudo drush perm-grant --roles='contributor' --permissions='access conStruct update' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct update' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct update' 

sudo drush perm-grant --roles='anonymous user' --permissions='access conStruct view' 
sudo drush perm-grant --roles='authenticated user' --permissions='access conStruct view' 
sudo drush perm-grant --roles='contributor' --permissions='access conStruct view' 
sudo drush perm-grant --roles='owner/curator' --permissions='access conStruct view' 
sudo drush perm-grant --roles='admin' --permissions='access conStruct view' 

sudo drush perm-grant --roles='authenticated user' --permissions='change own username' 
sudo drush perm-grant --roles='contributor' --permissions='change own username' 
sudo drush perm-grant --roles='owner/curator' --permissions='change own username' 
sudo drush perm-grant --roles='admin' --permissions='change own username' 

sudo drush perm-grant --roles='anonymous user' --permissions='access all views' 
sudo drush perm-grant --roles='authenticated user' --permissions='access all views' 
sudo drush perm-grant --roles='contributor' --permissions='access all views' 
sudo drush perm-grant --roles='owner/curator' --permissions='access all views' 
sudo drush perm-grant --roles='admin' --permissions='access all views' 

cecho "\n\n13.9) Import new Dataset Content Type into Drupal...\n"

sudo drush cck_import-import < $DRUPALFOLDER"/sites/all/modules/conStruct/content_types.cck"

cecho "\n\n13.10) Setup the initial WSF-Registry node...\n"

sudo drush php-eval '$wsfRegistry = array("http://'$DOMAINNAME'/ws/"); variable_set("WSF-Registry", $wsfRegistry);'


cecho "\n\n13.11) Installing YUI...\n"

cd sites/all/modules/conStruct/js

sudo wget http://yui.zenfs.com/releases/yui2/yui_2.9.0.zip

sudo unzip yui_2.9.0.zip

sudo rm yui_2.9.0.zip

cd $DRUPALFOLDER

cecho "\n\n13.12) Installing Smarty...\n"

sudo mkdir -p sites/all/modules/conStruct/framework/smarty

cd sites/all/modules/conStruct/framework/smarty

sudo wget http://www.smarty.net/files/Smarty-2.6.26.zip

sudo unzip Smarty-2.6.26.zip

sudo rm Smarty-2.6.26.zip

cd Smarty-2.6.26

cd libs

sudo mv * ../../

cd ../..

sudo rm -rf Smarty-2.6.26

cd ../..

sudo mkdir -p templates/templates_c

sudo chown www-data:www-data templates/templates_c

cd $DRUPALFOLDER

echo -e "\n\n"
cecho "------------------------"
cecho " 14. Loading Ontologies "
cecho "------------------------"
echo -e "\n\n"

# load all the ontologies in the OWLAPI instance

#perl -MCPAN -e 'install XML::Twig'
apt-get install -y xml-twig-tools

for FILE in $DATAFOLDER/ontologies/files/*
do
  if [ $FILE != $DATAFOLDER"/ontologies/files/new.owl" ]
  then
    ONTOLOGYURI="file://localhost/"$FILE
    ONTOLOGYURIENCODE="$(perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "$ONTOLOGYURI")"

    curl http://$DOMAINNAME/ws/ontology/create/ -H "Accept: text/xml" -d "advancedIndexation=true&uri=$ONTOLOGYURIENCODE&registered_ip=self"
  fi
done

cecho "\n\n14.1) Creating Classes and Properties...\n"

CLASSHIERARCHY=$(curl http://localhost/ws/ontology/read/ -v -H "Accept: text/xml" -d "function=getSerializedClassHierarchy&registered_ip=self")

echo $CLASSHIERARCHY > classHierarchy.xml

xml_grep --text_only '/resultset/subject/predicate/object' classHierarchy.xml > classHierarchySerialized.srz

PROPERTYHIERARCHY=$(curl http://localhost/ws/ontology/read/ -v -H "Accept: text/xml" -d "function=getSerializedPropertyHierarchy&registered_ip=self")

echo $PROPERTYHIERARCHY > propertyHierarchy.xml

xml_grep --text_only '/resultset/subject/predicate/object' propertyHierarchy.xml > propertyHierarchySerialized.srz

cecho "\n\n14.2) Moving the classes and properties structure file to the ontology folder...\n"

sudo mv -f classHierarchySerialized.srz $DATAFOLDER/ontologies/structure/
sudo mv -f propertyHierarchySerialized.srz $DATAFOLDER/ontologies/structure/


cecho "\n\n14.3) Create symbolic links to classes and properties structures from conStruct...\n"

sudo ln -s $DATAFOLDER"/ontologies/structure/" $DRUPALFOLDER"/sites/all/modules/conStruct/framework/ontologies"




if [[ $NONFATALERRORS != "" ]]
then

  echo -e "\n\n"
  cecho "---------------------------"
  cecho " Non-Fatal Errors to Handle "
  cecho "---------------------------"
  echo -e "\n\n"

  cecho "\n\nHere are some non fatal errors that happened in the installation process that will need to be addressed:\n"
  cecho "$NONFATALERRORS" $yellow
fi
