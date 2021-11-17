<?php

require 'common.php';

$validTasks = [
  'score_source_contacts',
  'mark_duplicates',
  'convert_contacts',
  'convert_events',
  'convert_participants',
  'all',
];

$BATCH_LIMIT = 2500000;

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
    echo "============================================================================================\n\n";
    echo "\n\n";
  }

  echo "\nDone.\n";
}

function executeTask($task) {
  if (!function_exists($task)) {
    throw new Exception("Function $task doesn't exist.");
  }

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

function score_source_contacts() {
  global $BATCH_LIMIT;

  $scoreGenerator = new SourceContactScoreGenerator($BATCH_LIMIT);
  $scoreGenerator->validateAllContacts();

  $logger = new SourceContactLogger();
  $logger->printStats();
}


function mark_duplicates() {

  $duplicateFinder = new SourceContactDuplicateFinder();
  $duplicateFinder->markMainContacts();
}


function convert_contacts() {
  global $BATCH_LIMIT;

  $convertor = new Convertor($BATCH_LIMIT);
  $convertor->start();
}


function convert_events() {

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
