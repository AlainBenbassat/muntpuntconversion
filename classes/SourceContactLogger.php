<?php

namespace Muntpuntconversion;

class SourceContactLogger {
  private const LOG_FILE_MIGRATE = __DIR__ . '/../valid_contacts.csv';
  private const LOG_FILE_DO_NOT_MIGRATE = __DIR__ . '/../invalid_contacts.csv';
  private const LOG_FILE_NEEDS_CLEANUP = __DIR__ . '/../verify_contacts.csv';

  public function __construct($reset = FALSE) {
    if ($reset) {
      $this->deleteLogFile(self::LOG_FILE_MIGRATE);
      $this->deleteLogFile(self::LOG_FILE_DO_NOT_MIGRATE);
      $this->deleteLogFile(self::LOG_FILE_NEEDS_CLEANUP);
    }
  }

  public function printStats() {
    $output1 = explode(' ', shell_exec('wc -l ' . self::LOG_FILE_MIGRATE));
    $output2 = explode(' ', shell_exec('wc -l ' . self::LOG_FILE_DO_NOT_MIGRATE));
    $output3 = explode(' ', shell_exec('wc -l ' . self::LOG_FILE_NEEDS_CLEANUP));

    $numMigrate = reset($output1);
    $numDoNotMigrate = reset($output2);
    $numCleanup = reset($output3);
    $total = $numMigrate + $numDoNotMigrate + $numCleanup - 3; // minus the three headers
    $percentageMigrate = round($numMigrate / $total * 100, 2);
    $percentageDoNotMigrate = round($numDoNotMigrate / $total * 100, 2);
    $percentageCleanup = round($numCleanup / $total * 100, 2);

    echo "Totaal aantal contacten: $total\n";
    echo " - Te migreren: $numMigrate ($percentageMigrate%)\n";
    echo " - Niet migreren: $numDoNotMigrate ($percentageDoNotMigrate%)\n";
    echo " - Na te kijken: $numCleanup ($percentageCleanup%)\n";
  }

  public function logMigrate($contact, $rating) {
    static $logFileCreated = FALSE;

    if ($logFileCreated == FALSE) {
      $this->createLogFile(self::LOG_FILE_MIGRATE, $rating);
      $logFileCreated = TRUE;
    }

    $this->logContact(self::LOG_FILE_MIGRATE, $contact, $rating);
  }

  public function logDoNotMigrate($contact, $rating) {
    static $logFileCreated = FALSE;

    if ($logFileCreated == FALSE) {
      $this->createLogFile(self::LOG_FILE_DO_NOT_MIGRATE, $rating);
      $logFileCreated = TRUE;
    }

    $this->logContact(self::LOG_FILE_DO_NOT_MIGRATE, $contact, $rating);
  }

  public function logNeedsCleanup($contact, $rating) {
    static $logFileCreated = FALSE;

    if ($logFileCreated == FALSE) {
      $this->createLogFile(self::LOG_FILE_NEEDS_CLEANUP, $rating);
      $logFileCreated = TRUE;
    }

    $this->logContact(self::LOG_FILE_NEEDS_CLEANUP, $contact, $rating);
  }

  private function deleteLogFile($fileName) {
    if (file_exists($fileName)) {
      unlink($fileName);
    }
  }

  private function createLogFile($fileName, $rating) {
    $header = [
      'Contact Id',
      'Display Name',
      'Contact Type'
    ];

    foreach ($rating as $k => $v) {
      $header[] = $k;
    }

    $tabSeparatedColumnNames = implode("\t", $header) . "\n";
    file_put_contents($fileName, $tabSeparatedColumnNames);
  }

  private function logContact($fileName, $contact, $rating) {
    $row = [
      $contact['id'],
      $contact['display_name'],
      $contact['contact_type'],
    ];

    foreach ($rating as $k => $v) {
      $row[] = $v;
    }

    $tabSeparatedRow = implode("\t", $row) . "\n";
    file_put_contents($fileName, $tabSeparatedRow, FILE_APPEND);
  }

}
