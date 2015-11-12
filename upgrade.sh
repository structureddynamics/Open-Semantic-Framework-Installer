#!/bin/bash

INSTALLDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

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

cecho () # Color-echo.
                             # Argument $1 = message
                             # Argument $2 = color
{
  local default_msg="No message passed."
                             # Doesn't really need to be a local variable.

  message=${1:-$default_msg} # Defaults to default message.
  color=${2:-$white} # Defaults to white, if not specified.

  echo -e "$color"
  echo -e "$message"
  
  tput sgr0 # Reset to normal.

  return
}

echo -e "\n\n"
cecho "----------------------------------"
cecho " Upgrading the OSF-Installer Tool "
cecho "----------------------------------"
echo -e "\n\n"

cecho "\n\nDownload the latest version of the OSF Installer tool...\n"

sudo wget https://github.com/structureddynamics/Open-Semantic-Framework-Installer/archive/3.3.zip

while [ $? -ne 0 ]; do
  cecho "Connection error while downloading the latest version of the Open Semantic Framework Installer; retrying...\n" "yellow"
  sudo rm -rf master.zip
  sudo wget https://github.com/structureddynamics/Open-Semantic-Framework-Installer/archive/3.3.zip
done

unzip 3.4.zip

cd Open-Semantic-Framework-Installer*

rm -f installer.ini

sudo cp -af * ../

cd ..

sudo chmod 755 osf-installer
sudo chmod 755 upgrade.sh
sudo chmod 755 install.sh

sudo rm -rf Open-Semantic-Framework-Installer*
sudo rm -f 3.4.zip

cecho "\n\nThe OSD-Installer has been upgraded\n"

