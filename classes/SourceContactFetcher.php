<?php

namespace Muntpuntconversion;

class SourceContactFetcher {
  public function getBatch($startingContactId = 0, $numberOfContacts = 300) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $sql = "
      SELECT
        id
      FROM
        civicrm_contact
      where
        id > $startingContactId
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

  public function getContact($contactId) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

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
