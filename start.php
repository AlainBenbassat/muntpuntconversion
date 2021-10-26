<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function main($task) {
  $BATCH_LIMIT = 200;

  try {
    loadClasses();
    bootstrapCiviCRM();

    if ($task == 'score') {
      $scoreGenerator = new SourceContactScoreGenerator($BATCH_LIMIT);
      $scoreGenerator->start();
    }
    elseif ($task == 'convert') {
      $convertor = new Convertor($BATCH_LIMIT);
      $convertor->start();
    }
  }
  catch (Exception $e) {
    echo "==============================================\n\n";
    echo 'ERROR: ' . $e->getMessage();
    echo "\n\n";
  }

  echo "\nDone.\n";
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

function getTask() {
  global $argv;

  if (count($argv) > 1) {
    if ($argv[1] == 'score') {
      return $argv[1];
    }
    elseif ($argv[1] == 'convert') {
      return $argv[1];
    }
    else {
      echo "Please specify a task: {score|convert}\n";
      exit(1);
    }
  }
  else {
    echo "Please specify a task: {score|convert}\n";
    exit(1);
  }
}

$task = getTask();
main($task);
