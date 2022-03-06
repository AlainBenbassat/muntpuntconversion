<?php

class SourceContactLogger {
  public const LOG_TABLE_CONTACTS = 'migration_contacts';
  public const LOG_TABLE_MC_GROUPS = 'migration_mailchimp_groups';
  public const LOG_TABLE_MC_GROUP_CONTACTS = 'migration_mailchimp_group_contacts';

  public function printStats() {
    $pdo = SourceDB::getPDO();

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE_CONTACTS . ' where  score = ' . SourceContactValidator::FINAL_SCORE_MIGRATE);
    $numMigrate = $score->fetch()['score_count'];

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE_CONTACTS . ' where  score = ' . SourceContactValidator::FINAL_SCORE_DO_NOT_MIGRATE);
    $numDoNotMigrate = $score->fetch()['score_count'];

    $score = $pdo->query('select count(id) score_count from ' . self::LOG_TABLE_CONTACTS . ' where is_main_contact = 1 and score = ' . SourceContactValidator::FINAL_SCORE_MIGRATE);
    $numMainContacts = $score->fetch()['score_count'];

    $total = $numMigrate + $numDoNotMigrate;
    $percentageMigrate = round($numMigrate / $total * 100, 2);
    $percentageDoNotMigrate = round($numDoNotMigrate / $total * 100, 2);
    $percentageMainContacts = round($numMainContacts / $total * 100, 2);

    echo "Totaal aantal contacten: $total\n";
    echo " - Te migreren: $numMigrate ($percentageMigrate%) - na ontdubbeling: $numMainContacts ($percentageMainContacts%)\n";
    echo " - Niet migreren: $numDoNotMigrate ($percentageDoNotMigrate%)\n";
  }

  public function clearLogTableContacts() {
    $this->dropTable(self::LOG_TABLE_CONTACTS);
  }

  public function clearLogTableMailchimp() {
    $this->dropTable(self::LOG_TABLE_MC_GROUP_CONTACTS);
    $this->dropTable(self::LOG_TABLE_MC_GROUPS);
  }

  private function dropTable($tableName) {
    $pdo = SourceDB::getPDO();
    $pdo->query('drop table if exists ' . $tableName);
  }

  private function createLogTable($rating) {
    $sql = "
      create table " . self::LOG_TABLE_CONTACTS . "
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

    // add index on email and main_contact_id
    $pdo->query('CREATE INDEX em_' . self::LOG_TABLE_CONTACTS . ' ON ' . self::LOG_TABLE_CONTACTS . ' (email, id); ');
    $pdo->query('CREATE INDEX mc_' . self::LOG_TABLE_CONTACTS . ' ON ' . self::LOG_TABLE_CONTACTS . ' (main_contact_id); ');
  }

  public function createMailChimpTables() {
    $pdo = SourceDB::getPDO();

    $sql = "
      CREATE TABLE " . self::LOG_TABLE_MC_GROUPS . "
      (
        id int(10) unsigned NOT NULL auto_increment,
        group_name varchar(255),
        PRIMARY KEY (id)
      ) ENGINE=InnoDB;
    ";
    $pdo->query($sql);

    $sql = "
      CREATE TABLE " . self::LOG_TABLE_MC_GROUP_CONTACTS . "
      (
        group_id int(10),
        email varchar(255),
        PRIMARY KEY (group_id, email),
        INDEX (email)
      ) ENGINE=InnoDB;
    ";
    $pdo->query($sql);
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
    $sql = 'insert into ' . self::LOG_TABLE_CONTACTS . '(' . implode(',', $colNames) . ') values (' . implode(',', $colPlaceHolders) . ');';
    $stmt= $pdo->prepare($sql);
    $stmt->execute($colValues);
  }

  public function deleteMailChimpGroups() {

  }

  public function logMailChimpGroup($groupName) {
    $pdo = SourceDB::getPDO();

    $sql = "
      insert into
        " . self::LOG_TABLE_MC_GROUPS . "
      (group_name)
      values (" . $pdo->quote($groupName) . ")
    ";
    $pdo->query($sql);

    return $pdo->lastInsertId();
  }

  public function logMailChimpGroupContact($groupId, $email) {
    $pdo = SourceDB::getPDO();

    $sql = "
      insert into
        " . self::LOG_TABLE_MC_GROUP_CONTACTS . "
      (group_id, email)
      values ($groupId, " . $pdo->quote($email) . ")
    ";
    $pdo->query($sql);
  }

  public function logEmployers() {
    $pdo = SourceDB::getPDO();
    $sql = "
      select
        r.contact_id_b employer_id
      from " . self::LOG_TABLE_CONTACTS . "
        ltc
      inner join
        civicrm_contact c on ltc.id = c.id
      inner join
        civicrm_relationship r on r.contact_id_a = c.id and r.relationship_type_id = 4 and r.is_active = 1
      where
        ltc.contact_type = 'Individual' and ltc.heeft_actieve_relaties = 1 and ltc.score = 1";
    $dao = $pdo->query($sql);
    while ($row = $dao->fetch()) {
      $sqlUpdateEmployerScore = "update " . self::LOG_TABLE_CONTACTS . " set score = 1, is_main_contact = if(main_contact_id = 0, 1, 0) where id = " . $row['employer_id'];
      $pdo->query($sqlUpdateEmployerScore);
    }
  }
}
