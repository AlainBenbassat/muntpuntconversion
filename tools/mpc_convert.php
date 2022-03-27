<?php

require 'common.php';

$validTasks = [
  'test_db' => '',
  'score_source_contacts' => 'ContactScore',
  'mark_duplicates' => 'ContactDuplicate',
  'clear_migration_ids' => '',
  'convert_profiles' => 'ConvertorProfile',
  'convert_campaigns' => 'ConvertorCampaign',
  'convert_contacts' => 'ConvertorContact',
  'convert_groups' => 'ConvertorCampaign',
  'convert_relationships' => 'ConvertorRelationship',
  'convert_event_types' => 'ConvertorEventType',
  'convert_events' => 'ConvertorEvent',
  'all',
];

function main() {
  try {
    $task = getTask();

    loadClasses();
    bootstrapCiviCRM();

    if ($task == 'all') {
      executeAllTasks();
    }
    else {
      executeTask($task);
    }
  }
  catch (Exception $e) {
    echo "============================================================================================\n";
    echo 'ERROR in ' . $e->getFile() . ', line ' . $e->getLine() . ":\n";
    echo  $e->getMessage() . "\n";
    echo "============================================================================================\n";
    echo "\n\n";
  }

  echo "\nDone.\n";
}

function executeTask($task) {
  echo "\n";
  $title = "Bezig met: $task";
  echo str_repeat("-", strlen($title)) . "\n";
  echo "$title\n";
  echo str_repeat("-", strlen($title)) . "\n";

  if (isTaskInOwnClass($task)) {
    runClassTask($task);
  }
  else {
    runLocalTask($task);
  }
}

function isTaskInOwnClass($task) {
  $className = getClassOfTask($task);
  if ($className) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

function runLocalTask($task) {
  if (!function_exists($task)) {
    throw new Exception("Function $task doesn't exist.");
  }

  call_user_func($task);
}

function runClassTask($task) {
  $class = getClassOfTask($task);

  $o = new $class();
  $o->run();
}

function getClassOfTask($task) {
  global $validTasks;

  return $validTasks[$task];
}

function executeAllTasks() {
  global $validTasks;

  foreach ($validTasks as $task) {
    if ($task != 'all') {
      executeTask($task);
    }
  }
}

function getTask() {
  global $argv;

  if (count($argv) > 1) {
    if (isValidTask($argv[1])) {
      return $argv[1];
    }
    else {
      showUsageAndExit();
    }
  }
  else {
    showUsageAndExit();
  }
}

function isValidTask($task) {
  global $validTasks;

  if (array_key_exists($task, $validTasks)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

function test_db() {
  $pdo = SourceDB::getPDO();
  $sql = "select count(*) num_contacts from civicrm_contact where is_deleted = 0";
  $dao = $pdo->query($sql);
  if ($row = $dao->fetch()) {
    echo 'Number of contacts in source database: ' . $row['num_contacts'] . "\n";
  }
  else {
    throw new \Exception("Cannot retrieve number of contacts in source database");
  }

  $numInTargetDB = CRM_Core_DAO::singleValueQuery($sql);
  echo "Number of contacts in target database: $numInTargetDB\n";
}

function clear_migration_ids() {
  TargetMigrationHelper::initialize();
}

function showUsageAndExit() {
  global $validTasks;

  echo 'Please specify a valid task: {' . implode('|', array_keys($validTasks)) . "}\n";
  exit(1);
}

main();
