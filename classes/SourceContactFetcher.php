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
        is_main_contact = 1
      and
        score = $score
      and
        contact_type in ('Individual', 'Organization')
      order by
        id
      limit
        0,$numberOfContacts
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getDuplicateContacts($mainContactId) {
    $pdo = SourceDB::getPDO();

    $tableName = SourceContactLogger::LOG_TABLE_CONTACTS;
    $score = SourceContactValidator::FINAL_SCORE_MIGRATE;
    $sql = "
      SELECT
        *
      FROM
        $tableName
      where
        main_contact_id = $mainContactId
      and
        score = $score
      order by
        id
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
    $validPhones = [];

    $pdo = SourceDB::getPDO();

    $sql = "
      select
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
      order by
        is_primary
    ";

    $phones = $pdo->query($sql)->fetchAll();
    foreach ($phones as $phone) {
      if ($this->isValidPhone($phone)) {
        $validPhone = $this->getCleanedPhone($phone);

        if (!$this->isDuplicatePhone($validPhone, $validPhones)) {
          $validPhones[] = $validPhone;
        }
      }
    }

    return $validPhones;
  }

  private function isDuplicatePhone($newPhone, $validPhones) {
    foreach ($validPhones as $validPhone) {
      if ($newPhone['phone_numeric'] == $validPhone['phone_numeric']) {
        return TRUE; // duplicate
      }
    }

    return FALSE;
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
    $phoneNumber = $phone['phone'];
    $numericPhone = $phone['phone_numeric'];

    if (strlen($numericPhone) == 8) {
      $phoneNumber .= '0';
      $numericPhone .= '0';
    }

    $phoneParam = [
      'location_type_id' => 3, // FORCE TO USE MAIN? or $phone['location_type_id'],
      'phone_type_id' => $phone['phone_type_id'],
      'phone' => $phoneNumber,
      'phone_numeric' => $numericPhone,
    ];

    return $phoneParam;
  }
}
