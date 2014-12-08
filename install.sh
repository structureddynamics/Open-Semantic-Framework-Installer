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
                             # Does not need to be a local variable.

  message=${1:-$default_msg} # Defaults to default message.
  color=${2:-$white} # Defaults to white, if not specified.

  echo -e "$color"
  echo -e "$message"
  
  tput sgr0 # Reset to normal.

  return
}

echo -e "\n\n"
cecho "-----------------------------------"
cecho " Installing the OSF-Installer Tool "
cecho "-----------------------------------"
echo -e "\n\n"

cecho "\n\nInstalling requirements...\n"

# Make sure that apt-get exists for this Linux distribution
if which apt-get >/dev/null; then
  apt-get -y update
  apt-get -y --no-upgrade install php5
  apt-get -y install unzip wget
else
  cecho "\nMake sure that PHP5, unzip, and wget packages are installed on the server.\n" yellow
fi

cecho "\n\nDownload the latest version of the OSF Installer tool...\n"

wget https://github.com/structureddynamics/Open-Semantic-Framework-Installer/archive/3.1.zip

unzip 3.1.zip

cd Open-Semantic-Framework-Installer*

mv -f * ../

cd ..

rm -rf Open-Semantic-Framework-Installer*
rm -f 3.1.zip

chmod 755 osf-installer
chmod 755 upgrade.sh

cecho "\n\nRun the 'osf-installer' script to install OSF or any of its components.\n"


