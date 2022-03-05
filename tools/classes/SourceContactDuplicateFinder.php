<?php

class SourceContactDuplicateFinder {
  private $tableName;

  public function __construct() {
    $this->tableName = SourceContactLogger::LOG_TABLE_CONTACTS;
  }

  public function markMainContacts() {
    $this->markContactsWithoutEmailAsMain();
    $this->markContactsWithUniqueEmailAsMain();
    $this->markContactsWithNonUniqueEmail();
  }

  private function markContactsWithoutEmailAsMain() {
    $pdo = SourceDB::getPDO();

    $sql = "update {$this->tableName} set is_main_contact = 1 where score = 1 and heeft_emailadres = 0"; // in theory only orgs
    $pdo->query($sql);
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
        $contactId = $this->getFirstContactWithRealName($row['email'], $row['contact_type']);
        if ($contactId) {
          $this->markContactAsMainForEmail($contactId, $row['email'], $row['contact_type']);
        }
        else {
          $this->markContactAsMainForEmail($row['min_id'], $row['email'], $row['contact_type']);
        }
      }
      else {
        $this->markContactAsMainForEmail($row['min_id'], $row['email'], $row['contact_type']);
      }
    }
  }

  private function getQueryDuplicateEmails() {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        email
        , contact_type
        , sum(display_name_is_email) has_display_name_is_email
        , min(id) min_id
      from
        {$this->tableName}
      where
        email_is_uniek = 0
      and
        ifnull(email, '') <> ''
      group by
        email, contact_type
      having
        sum(score) > 0
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  private function getFirstContactWithRealName($email, $contactType) {
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
      and
        contact_type = '$contactType'
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

  private function markContactAsMainForEmail($contactId, $email, $contactType) {
    $this->markContactAsMain($contactId);
    $this->markOtherContactsForEmail($contactId, $email, $contactType);
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

  private function markOtherContactsForEmail($contactId, $email, $contactType) {
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
      and
        contact_type = '$contactType'
    ";
    $pdo = SourceDB::getPDO();
    $pdo->query($sql);
  }


}
