<?php

/*** Bootstrap CiviCRM ***/
$settingsFile = '../web/sites/default/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
$error = include_once $settingsFile;
if ($error == FALSE) {
  echo "Could not load the settings file at: {$settingsFile}\n";
  exit(1);
}

// Load class loader
global $civicrm_root;
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

CRM_Utils_System::loadBootStrap(array(), FALSE);
/*** end of bootstrap process ***/

CRM_Muntpuntconfig_Preferences::set();

$iniFile = parse_ini_file(__DIR__ . '/../settings/civi.cnf', TRUE);
CRM_Muntpuntconfig_Preferences::setSMTP($iniFile['smtpsettings']);

CRM_Muntpuntconfig_Preferences::setBackendTheme();

CRM_Muntpuntconfig_ConfigItems::load();

