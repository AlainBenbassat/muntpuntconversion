<?php

class SourceContactDuplicateFinder {
  private $tableName;

  public function __construct() {
    $this->tableName = SourceContactLogger::LOG_TABLE_CONTACTS;
  }

  public function markMainContacts() {
    $this->markContactsWithUniqueEmailAsMain();
    $this->markContactsWithNonUniqueEmail();
  }

  private function markContactsWithUniqueEmailAsMain() {
    $pdo = SourceDB::getPDO();

    $sql = "update {$this->tableName} set is_main_contact = 1 where score = 1 and email_is_uniek = 1";
    $pdo->query($sql);
  }

  private function markContactsWithNonUniqueEmail() {
    $dao = $this->getQueryDuplicateEmails();
    while ($row = $dao->fetch()) {
      if ($row['has_display_name_is_email']) {
        $contactId = $this->getFirstContactWithRealName($row['email']);
        if ($contactId) {
          $this->markContactAsMainForEmail($contactId, $row['email']);
        }
        else {
          $this->markContactAsMainForEmail($row['min_id'], $row['email']);
        }
      }
      else {
        $this->markContactAsMainForEmail($row['min_id'], $row['email']);
      }
    }
  }

  private function getQueryDuplicateEmails() {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        email
        , sum(display_name_is_email) has_display_name_is_email
        , min(id) min_id
      from
        {$this->tableName}
      where
        email_is_uniek = 0
      group by
        email
      having
        sum(score) > 0
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  private function getFirstContactWithRealName($email) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        id
      from
        {$this->tableName}
      where
        email = " . $pdo->quote($email) . "
      and
        display_name_is_email = 0
      order by
        id
    ";

    $dao = $pdo->query($sql);
    if ($row = $dao->fetch()) {
      return $row['id'];
    }
    else {
      return 0;
    }
  }

  private function markContactAsMainForEmail($contactId, $email) {
    $this->markContactAsMain($contactId);
    $this->markOtherContactsForEmail($contactId, $email);
  }

  private function markContactAsMain($contactId) {
    $sql = "
      update
        {$this->tableName}
      set
        is_main_contact = 1
        , score = 1
      where
        id = $contactId
    ";
    $pdo = SourceDB::getPDO();
    $pdo->query($sql);
  }

  private function markOtherContactsForEmail($contactId, $email) {
    $pdo = SourceDB::getPDO();

    $sql = "
      update
        {$this->tableName}
      set
        is_main_contact = 0
        , main_contact_id = $contactId
        , score = 1
      where
        email = " . $pdo->quote($email) . "
      and
        id <> $contactId
    ";
    $pdo = SourceDB::getPDO();
    $pdo->query($sql);
  }


}
