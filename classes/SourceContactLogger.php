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

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE . ' where  score = ' . SourceContactValidator::FINAL_SCORE_NEEDS_CLEANUP);
    $numCleanup = $score->fetch()['score_count'];

    $total = $numMigrate + $numDoNotMigrate + $numCleanup;
    $percentageMigrate = round($numMigrate / $total * 100, 2);
    $percentageDoNotMigrate = round($numDoNotMigrate / $total * 100, 2);
    $percentageCleanup = round($numCleanup / $total * 100, 2);

    echo "Totaal aantal contacten: $total\n";
    echo " - Te migreren: $numMigrate ($percentageMigrate%)\n";
    echo " - Niet migreren: $numDoNotMigrate ($percentageDoNotMigrate%)\n";
    echo " - Na te kijken: $numCleanup ($percentageCleanup%)\n";
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
        email varchar(255)
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
    ];
    $colPlaceHolders = [
      '?',
      '?',
      '?',
    ];

    $colValues = [
      $contact['id'],
      $contact['display_name'],
    ];

    foreach ($rating as $k => $v) {
      $colNames[] = $k;
      $colValues[] = $v;
      $colPlaceHolders[] = '?';
    }

    $pdo = SourceDB::getPDO();
    $sql = 'insert into ' . self::LOG_TABLE . '(' . implode(',', $colNames) . ') values (' . implode(',', $colPlaceHolders) . ');';
    $stmt= $pdo->prepare($sql);
    $stmt->execute($colValues);
  }

}
