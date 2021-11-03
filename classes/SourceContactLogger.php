<?php

class SourceContactLogger {
  public const LOG_TABLE = 'migration_contacts';

  public function __construct($reset = FALSE) {
    if ($reset) {
      $this->clearLogTable();
    }
  }

  public function printStats() {
    $pdo = SourceDB::getPDO();

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE . ' where  score = ' . SourceContactValidator::FINAL_SCORE_MIGRATE);
    $numMigrate = $score->fetch()['score_count'];

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE . ' where  score = ' . SourceContactValidator::FINAL_SCORE_DO_NOT_MIGRATE);
    $numDoNotMigrate = $score->fetch()['score_count'];

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE . ' where is_main_contact = 1 and score = ' . SourceContactValidator::FINAL_SCORE_MIGRATE);
    $numMainContacts = $score->fetch()['score_count'];

    $total = $numMigrate + $numDoNotMigrate;
    $percentageMigrate = round($numMigrate / $total * 100, 2);
    $percentageDoNotMigrate = round($numDoNotMigrate / $total * 100, 2);
    $percentageMainContacts = round($numMainContacts / $total * 100, 2);

    echo "Totaal aantal contacten: $total\n";
    echo " - Te migreren: $numMigrate ($percentageMigrate%) - na ontdubbeling: $numMainContacts ($percentageMainContacts%)\n";
    echo " - Niet migreren: $numDoNotMigrate ($percentageDoNotMigrate%)\n";
  }

  public function export() {
    // OP BASIS VAN TABEL DE CSV's genereren
  }

  private function clearLogTable() {
    $pdo = SourceDB::getPDO();
    $pdo->query('drop table if exists ' . self::LOG_TABLE);
  }

  private function createLogTable($rating) {
    $sql = "
      create table " . self::LOG_TABLE . "
      (
        id int(10) unsigned PRIMARY KEY,
        display_name varchar(255),
        contact_type varchar(255),
        email varchar(255),
        is_main_contact int(5) default 0,
        main_contact_id int(10) default 0
    ";

    foreach ($rating as $k => $v) {
      if ($k != 'email' && $k != 'contact_type') {
        $sql .= ", $k int(5)";
      }
    }

    $sql .= ') ENGINE=InnoDB';

    $pdo = SourceDB::getPDO();
    $pdo->query($sql);

    // add index on email
    $pdo->query('CREATE INDEX em_' . self::LOG_TABLE . ' ON ' . self::LOG_TABLE . ' (email, id); ');
  }

  public function logContact($contact, $rating) {
    static $log_table_created = FALSE;

    if ($log_table_created == FALSE) {
      $this->createLogTable($rating);
      $log_table_created = TRUE;
    }

    $colNames = [
      'id',
      'display_name',
      'is_main_contact',
    ];
    $colPlaceHolders = [
      '?',
      '?',
      '?',
    ];

    $colValues = [
      $contact['id'],
      trim($contact['display_name']),
      0,
    ];

    foreach ($rating as $k => $v) {
      $colNames[] = $k;
      $colValues[] = trim($v);
      $colPlaceHolders[] = '?';
    }

    $pdo = SourceDB::getPDO();
    $sql = 'insert into ' . self::LOG_TABLE . '(' . implode(',', $colNames) . ') values (' . implode(',', $colPlaceHolders) . ');';
    $stmt= $pdo->prepare($sql);
    $stmt->execute($colValues);
  }

}
