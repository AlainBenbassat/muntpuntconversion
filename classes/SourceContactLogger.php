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

    $total = $numMigrate + $numDoNotMigrate;
    $percentageMigrate = round($numMigrate / $total * 100, 2);
    $percentageDoNotMigrate = round($numDoNotMigrate / $total * 100, 2);

    echo "Totaal aantal contacten: $total\n";
    echo " - Te migreren: $numMigrate ($percentageMigrate%)\n";
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

  public function resolveDuplicates() {
    $this->markContactsWithUniqueEmailAsMain();

    $this->resolveDuplicatesWithNameIsEmail();
    $this->resolveDuplicatesWithNameIsNotEmail();
  }

  private function markContactsWithUniqueEmailAsMain() {
    $pdo = SourceDB::getPDO();

    $sql = 'update ' . self::LOG_TABLE . ' set is_main_contact = 1 where score = 1 and email_is_uniek = 1';
    $pdo->query($sql);
  }

  private function resolveDuplicatesWithNameIsEmail() {
    $dao = $this->getQueryDuplicatesWithNameIsEmail();
    while ($row = $dao->fetch()) {
      if ($contactId = $this->getContactWithRealName($row['email'])) {
        $this->markContactAsMain($contactId);
        $this->scoreContactsWithEmail($row['email']);
      }
      else {
        $this->markFistOccurenceAsMain('', $row['email']);
        $this->scoreContactsWithEmail($row['email']);
      }
    }
  }

  private function resolveDuplicatesWithNameIsNotEmail() {
    $dao = $this->getQueryDuplicatesWithNameIsNotEmail();
    while ($row = $dao->fetch()) {
      if ($row['has_main_contact']) {
        // do nothing
      }
      else {
        $this->markFistOccurenceAsMain($row['display_name'], $row['email']);
      }
    }
  }

  private function getContactWithRealName($email) {
    $table = self::LOG_TABLE;

    $sql = "
      select
        id
      from
        $table
      where
        email = '$email'
      and
        display_name_is_email = 0
      order by
        id
    ";

    $pdo = SourceDB::getPDO();
    $dao = $pdo->query($sql);
    if ($row = $dao->fetch()) {
      return $row['id'];
    }
    else {
      return 0;
    }
  }

  private function markContactAsMain($contactId) {
    $table = self::LOG_TABLE;
    $sql = "
      update
        $table
      set
        is_main_contact = 1
        , score = 1
      where
        id = $contactId
    ";
    $pdo = SourceDB::getPDO();
    $pdo->query($sql);
  }

  private function scoreContactsWithEmail($email) {
    $table = self::LOG_TABLE;
    $sql = "
      update
        $table
      set
        score = 1
      where
        is_main_contact = 0
      and
        email = '$email'
    ";
    $pdo = SourceDB::getPDO();
    $pdo->query($sql);
  }

  private function markFistOccurenceAsMain($displayName, $email) {
    $table = self::LOG_TABLE;
    $pdo = SourceDB::getPDO();

    if ($displayName) {
      $displayNameClause = ' and display_name = ' . $pdo->quote($displayName);
    }
    else {
      $displayNameClause = '';
    }

    $sql = "
      select
        id
      from
        $table
      where
        email = '$email'
        $displayNameClause
      order by
        id
    ";

    $dao = $pdo->query($sql);
    $row = $dao->fetch();

    $this->markContactAsMain($row['id']);
  }

  private function getQueryDuplicatesWithNameIsEmail() {
    $pdo = SourceDB::getPDO();

    $sql = '
      select
        email
      from ' . self::LOG_TABLE . '
      where
        display_name_is_email = 1
      group by
        email
      having
        sum(score) > 0
    ';

    $dao = $pdo->query($sql);

    return $dao;
  }

  private function getQueryDuplicatesWithNameIsNotEmail() {
    $pdo = SourceDB::getPDO();

    $sql = '
      select
        display_name
        , email
        , sum(is_main_contact) has_main_contact
      from ' . self::LOG_TABLE . '
      where
        display_name_is_email = 0
      group by
        display_name
        , email
      having
        sum(score) > 0
    ';

    $dao = $pdo->query($sql);

    return $dao;
  }

}
