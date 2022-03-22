<?php

require 'common.php';

$validTasks = [
  'test_db',
  'profiles',
  'score_source_contacts',
  'mark_duplicates',
  'convert_contacts',
  'convert_groups',
  'convert_relationships',
  'convert_event_types_roles_status',
  'convert_events',
  'all',
];

$BATCH_LIMIT = 500000;

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
  if (!function_exists($task)) {
    throw new Exception("Function $task doesn't exist.");
  }

  echo "\n";
  $title = "Bezig met: $task";
  echo str_repeat("-", strlen($title)) . "\n";
  echo "$title\n";
  echo str_repeat("-", strlen($title)) . "\n";

  call_user_func($task);
}

function executeAllTasks() {
  global $validTasks;

  foreach ($validTasks as $task) {
    if ($task != 'all') {
      executeTask($task);
    }
  }
}

function profiles() {
  global $BATCH_LIMIT;

  $convertor = new Convertor($BATCH_LIMIT);
  $convertor->convertProfiles();
}

function score_source_contacts() {
  global $BATCH_LIMIT;

  $scoreGenerator = new SourceContactScoreGenerator($BATCH_LIMIT);
  $scoreGenerator->validateAllContacts();


  $logger = new SourceContactLogger();
  $logger->printStats();
}

function mark_duplicates() {
  global $BATCH_LIMIT;

  $duplicateFinder = new SourceContactDuplicateFinder();
  $duplicateFinder->markMainContacts();

  $scoreGenerator = new SourceContactScoreGenerator($BATCH_LIMIT);
  $scoreGenerator->validateEmployers();

  $logger = new SourceContactLogger();
  $logger->printStats();
}

function convert_contacts() {
  global $BATCH_LIMIT;

  $convertor = new Convertor($BATCH_LIMIT);
  $convertor->convertContacts(FALSE);
}

function convert_profiles() {
  $convertor = new Convertor();
  $convertor->convertProfiles();
}

function convert_relationships() {
  global $BATCH_LIMIT;

  $convertor = new Convertor($BATCH_LIMIT);
  $convertor->convertRelationships();
}

function convert_groups() {
  global $BATCH_LIMIT;

  $convertor = new Convertor($BATCH_LIMIT);
  $convertor->convertGroups();
}

function convert_events() {
  $convertor = new Convertor();
  $convertor->convertEvents();
  $convertor->convertRecurringEvents();
}

function convert_event_types_roles_status() {
  $convertor = new Convertor();
  $convertor->convertEventTypesRolesEtc();
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

  if (in_array($task, $validTasks)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

function showUsageAndExit() {
  global $validTasks;

  echo 'Please specify a valid task: {' . implode('|', $validTasks) . "}\n";
  exit(1);
}

main();
