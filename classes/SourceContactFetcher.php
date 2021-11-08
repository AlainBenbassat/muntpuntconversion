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

  public function getValidMainContacts($startingContactId = 0, $numberOfContacts = 300) {
    $pdo = SourceDB::getPDO();

    $tableName = SourceContactLogger::LOG_TABLE_CONTACTS;
    $score = SourceContactValidator::FINAL_SCORE_MIGRATE;
    $sql = "
      SELECT
        *
      FROM
        $tableName
      where
        id > $startingContactId
      and
        score = $score and heeft_postadres = 1 and heeft_telefoonnummer = 1 and heeft_emailadres  = 1
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

  public function getPrimaryAddress($contactId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        *
      FROM
        civicrm_address
      where
        contact_id = $contactId
      and
        is_primary = 1
    ";
    $dao = $pdo->query($sql);
    if ($address = $dao->fetch()) {
      return $address;
    }
    else {
      throw new \Exception("Cannot retrieve address of contact with id = $contactId");
    }
  }

  public function getPhones($contactId) {
    $phones = [];

    $pdo = SourceDB::getPDO();

    $sql = "
      select
        is_primary,
        location_type_id,
        phone_type_id,
        phone,
        phone_numeric
      from
        civicrm_phone
      where
        LENGTH(phone_numeric) >= 8
      and
        contact_id = $contactId
    ";

    $dao = $pdo->query($sql);
    while ($phone = $dao->fetch()) {
      if ($this->isValidPhone($phone)) {
        $phones[] = $this->getCleanedPhone($phone);
      }
    }

    return $phones;
  }

  private function isValidPhone($phone) {
    if (strlen($phone->phone_numeric) == 8 && substr($phone->phone_numeric, 0, 1) == '0') {
      return FALSE; // too short and leading zero
    }

    if ($phone->phone_numeric == '11111111') {
      return FALSE; // obvious test number
    }

    if (strpos($phone->phone_numeric, '123456') !== FALSE) {
      return FALSE;  // obvious test number
    }

    return TRUE;
  }

  private function getCleanedPhone($phone) {
    $phone = $phone['phone'];
    $numericPhone = $phone['phone_numeric'];

    if (strlen($numericPhone) == 8) {
      $phone .= '0';
      $numericPhone .= '0';
    }

    $phoneParam = [
      'is_primary' => $phone['is_primary'],
      'location_type_id' => $phone['location_type_id'],
      'phone_type_id' => $phone['phone_type_id'],
      'phone' => $phone,
      'phone_numeric' => $numericPhone,
    ];

    return $phoneParam;
  }
}
