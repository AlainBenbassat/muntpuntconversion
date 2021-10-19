<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function main() {
  try {
    loadClasses();
    bootstrapCiviCRM();

    // Step 1: prepare contact list
    $scoreGenerator = new \Muntpuntconversion\SourceContactScoreGenerator();
    $scoreGenerator->start();

    // Step 2: convert (i.e. migrate contact)
    $convertor = new Muntpuntconversion\Convertor();
    $convertor->start();
  }
  catch (Exception $e) {
    echo "==============================================\n\n";
    echo 'ERROR: ' . $e->getMessage();
    echo "\n\n";
  }
}

function bootstrapCiviCRM() {
  $settingsFile = '../web/sites/default/civicrm.settings.php';
  define('CIVICRM_SETTINGS_PATH', $settingsFile);
  require_once $settingsFile;

  global $civicrm_root;
  require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
  CRM_Core_ClassLoader::singleton()->register();

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();

  CRM_Utils_System::loadBootStrap([], FALSE);
}

function loadClasses() {
  // spl_autoload_register conflicts with civi, so we use our own loader
  $classFiles = glob("classes/*.php");
  foreach ($classFiles as $classFile) {
    include $classFile;
  }
}

main();
