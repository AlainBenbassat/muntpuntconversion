<?php

class SourceContactFetcher {

  public function getBatchAllContacts($startingContactId = 0, $numberOfContacts = 300) {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        id
      FROM
        civicrm_contact
      where
        id > $startingContactId and id in (select contact_id from civicrm_email where email = 'aberkanm@hotmail.com')
      and
        is_deleted = 0
      order by
        id
      limit
        0,$numberOfContacts
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getBatchOnlyValidContacts($startingContactId = 0, $numberOfContacts = 300) {
    $pdo = SourceDB::getPDO();

    $tableName = SourceContactLogger::LOG_TABLE;
    $score = SourceContactValidator::FINAL_SCORE_MIGRATE;
    $sql = "
      SELECT
        id
      FROM
        $tableName
      where
        id > $startingContactId
      and
        score = $score
      order by
        id
      limit
        0,$numberOfContacts
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getContact($contactId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        *
      FROM
        civicrm_contact
      where
        id = $contactId
      and
        is_deleted = 0
    ";
    $dao = $pdo->query($sql);
    if ($contact = $dao->fetch()) {
      return $contact;
    }
    else {
      throw new \Exception("Cannot retrieve contact $contact");
    }
  }
}
