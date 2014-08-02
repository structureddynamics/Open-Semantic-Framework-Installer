# build php5 from source w/ iodbc
# run as root, not sudo
# install latest updates
apt-get -y update
apt-get -y upgrade
#
cd /tmp
mkdir -p /tmp/php5-install/update/build/
apt-get -y install devscripts
apt-get -y install debhelper
cd /tmp/php5-install/update/build/
apt-get -y source php5
cd php5-5.5.9+dfsg/debian
# remove unixodbc dependencies
sed -i '/unixodbc-dev,/d' /tmp/php5-install/update/build/php5-5.5.9+dfsg/debian/control
sed -i 's/unixODBC/iodbc/' /tmp/php5-install/update/build/php5-5.5.9+dfsg/debian/rules
# must be run as root; sudo does not have permission
cat /dev/null > /tmp/php5-install/update/build/php5-5.5.9+dfsg/debian/setup-mysql.sh 
cd /tmp/php5-install/update/build/php5-5.5.9+dfsg/
apt-get -y build-dep php5
apt-get -y remove unixodbc-dev libodbc1 odbcinst odbcinst1debian2 unixodbc
apt-get -y install iodbc libiodbc2-dev
#
debuild | tee /tmp/php5BuildOutput-$(date --iso-8601=seconds).log
