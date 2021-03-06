<?php

set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL);

function bootstrapCiviCRM() {
  $settingsFile = __DIR__ . '/../../web/sites/default/civicrm.settings.php';
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
  $classFiles = glob(__DIR__ . "/classes/*.php");
  foreach ($classFiles as $classFile) {
    include $classFile;
  }
}

