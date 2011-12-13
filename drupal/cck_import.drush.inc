<?php

// Modified/fixed version of: http://kiku-tech-logic.blogspot.com/2010/04/drupal-drush-cck-import-module-code.html

/**
* @file
* Drush support for cck_import.
*/

/**
* Implementation of hook_drush_command().
*/
function cck_import_drush_command() {
$items = array();

$items['cck_import-import'] = array(
'callback' => 'cck_import_drush_callback_import',
'description' => "Import node code from a previous export.",
'options' => array(
'--uid' => "Uid of user to save nodes as. If not given will use 1. You may specify 0 for the Anonymous user.",
),
'examples' => array(
'drush cck_import import < filename' =>
'Import nodes from given filename.',
),
);

return $items;
}

/**
* Implementation of hook_drush_help().
*
* This function is called whenever a drush user calls
* 'drush help '
*
* @param
* A string with the help section (prepend with 'drush:')
*
* @return
* A string with the help text for your command.
*/
function cck_import_drush_help($section) {
switch ($section) {
case 'drush:cck_import-import':
return dt("Imports nodes that have been exported. Usage: 'drush cck_import import < filename'.");
}
}

/**
* Drush command callback.
*
*/
function cck_import_drush_callback_export() {
$commands = func_get_args();

$nids = array_filter($commands, 'is_numeric');

$data = cck_import_node_bulk($nids, TRUE);

$filename = drush_get_option('file');

if ($filename) {
// Output data to file. Note this only takes a flat filename for the current directory.
// If file exists, ask for whether to overwrite
if (file_exists($filename)) {
if (!drush_confirm(dt('File ' . $filename . ' exists. Do you really want to overwrite?'))) {
return;
}
}
// Write the file.
file_put_contents($filename, $data);
}
else {
// Print to terminal.
drush_print_r($data);
}
}

/**
* Drush command callback.
*
* Import nodes from data.
*/
function cck_import_drush_callback_import() {
$commands = func_get_args();

// Switch to admin or the specified user so imported nodes are not anonymous.
$uid = drush_get_option('uid');
// Test on NULL so uid may be given as 0.
if (is_null($uid)) {
$uid = 1;
}
// uid 0 is already loaded.
if ($uid != 0) {
global $user;
$user = user_load($uid);
}

$values = array();
$values['type_name'] = '<create>';
$values['macro'] = file_get_contents("php://stdin", "r");
$form_state = array();
$form_state['values'] = $values;

if (!empty($form_state)) {
$result = drupal_execute("content_copy_import_form", $form_state);
if ($result === FALSE) {
// There was a problem with types?
drush_set_error('DRUSH_NOT_COMPLETED', 'Problem found with the import file. Check the node types exist.');
}
}

}

?>