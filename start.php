<?php

require 'common.php';

function main($task) {
  $BATCH_LIMIT = 2000000;

  try {
    loadClasses();
    bootstrapCiviCRM();

    if ($task == 'score') {
      $scoreGenerator = new SourceContactScoreGenerator($BATCH_LIMIT);
      $scoreGenerator->validateAllContacts();

      $duplicateFinder = new SourceContactDuplicateFinder();
      $duplicateFinder->markMainContacts();

      $logger = new SourceContactLogger();
      $logger->printStats();
    }
    elseif ($task == 'convert') {
      $convertor = new Convertor($BATCH_LIMIT);
      $convertor->start();
    }
  }
  catch (Exception $e) {
    echo "==============================================\n";
    echo 'ERROR in ' . $e->getFile() . ', line ' . $e->getLine() . ":\n";
    echo  $e->getMessage() . "\n";
    echo "==============================================\n\n";
    echo "\n\n";
  }

  echo "\nDone.\n";
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
