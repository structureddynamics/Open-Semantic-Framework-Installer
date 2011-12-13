#!/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

echo 'Commands issued by auth.sh' > ~/structWSF/structWSF/auth_commands.txt

curl_action() {
 	echo $ACTION >> ~/structWSF/structWSF/auth_commands.txt
	curl $ACTION
}

echo '# purge' >> ~/structWSF/structWSF/auth_commands.txt
ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=reset
curl_action

echo '# initialize' >> ~/structWSF/structWSF/auth_commands.txt
ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_wsf\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
curl_action

echo '# local server (FQN) host name (matches network.ini which matches /etc/hosts)'  >> ~/structWSF/structWSF/auth_commands.txt
ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_user_full_access\&user_address=$(hostname -i)\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
curl_action

#echo '# localhost IP account' >> ~/structWSF/structWSF/auth_commands.txt
#ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_user_full_access#\&user_address=127.0.0.1\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
#curl_action

#echo '# linux console' >> ~/structWSF/structWSF/auth_commands.txt
#ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_user_full_access\&user_address=172.16.192.248\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
#curl_action

#echo '# local server internet address' >> ~/structWSF/structWSF/auth_commands.txt
#ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_user_full_access\&user_address=$(ifconfig eth0 | grep 'inet addr' | cut -f2 -d: | cut -f1 -d' ')\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
#curl_action

echo '# world readable' >> ~/structWSF/structWSF/auth_commands.txt
ACTION=http://$(hostname | cut -f1 -d.).$(hostname -d)/ws/auth/wsf_indexer.php?action=create_world_readable_dataset_read\&server_address=http://$(hostname | cut -f1 -d.).$(hostname -d)
curl_action

echo '# insert test data' >> ~/structWSF/structWSF/auth_commands.txt
echo '# run this as a curl command' >> ~/structWSF/structWSF/auth_commands.txt
ACTION="-H \"Accept: text/xml\" \"http://`hostname | cut -f1 -d.`.`hostname -d`/ws/dataset/create/\" -d \"uri=http://`hostname | cut -f1 -d.`.`hostname -d`/test/\&title=Test\&description=test\&creator=http://`hostname | cut -f1 -d.`.`hostname -d`/drupal/user/1/\" -v"
echo $ACTION >> ~/structWSF/structWSF/auth_commands.txt

echo '# retrieve test data' >> ~/structWSF/structWSF/auth_commands.txt
curl -F "query=SELECT * FROM <http://`hostname | cut -f1 -d.`.`hostname -d`/wsf/datasets/> {?s ?p ?o}"  http://`hostname | cut -f1 -d.`.`hostname -d`:8890/sparql
echo curl -F "query=SELECT * FROM <http://`hostname | cut -f1 -d.`.`hostname -d`/wsf/datasets/> {?s ?p ?o}"  http://`hostname | cut -f1 -d.`.`hostname -d`:8890/sparql >> ~/structWSF/structWSF/auth_commands.txt
clear
cat ~/structWSF/structWSF/auth_commands.txt
