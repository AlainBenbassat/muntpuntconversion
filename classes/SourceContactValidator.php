<?php

namespace Muntpuntconversion;

class SourceContactValidator {
  const LOG_FILE = __DIR__ . '/../invalid_contacts.csv';
  private $logFileCreated = FALSE;

  public function __construct() {
    $this->deleteLogfile();
  }

  public function isValidContact($contact) {
    $rating = [];

    $this->hasDisplayName($contact, $rating);
    $this->hasActiveRelationships($contact, $rating);
    $this->isNotSpam($contact, $rating);
    $this->hasActiveLogin($contact, $rating);
    $this->hasPostalAddress($contact, $rating);
    $this->hasEmailAddress($contact, $rating);

    $this->calculateScore($rating);
    if ($rating['score'] > 0) {
      $this->logContact($contact, $rating); // tijdelijk tot we drempel bepaald hebben
      return TRUE;
    }
    else {
      $this->logContact($contact, $rating);
    }
  }

  private function hasDisplayName($contact, &$rating) {
    if (trim($contact['display_name'])) {
      $rating['has display name'] = 1;
    }
    else {
      $rating['has display name'] = 0;
    }
  }

  private function hasActiveRelationships($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $sql = "
      select
        count(*)
      from
        civicrm_relationship r
      where
        r.contact_id_a = :contactIdA or r.contact_id_b = :contactIdB
      and
        r.is_active = 1
    ";

    $dao = $pdo->prepare($sql);
    $dao->execute([
      'contactIdA' => $contact['id'],
      'contactIdB' => $contact['id'],
    ]);
    $numOfRelationships = $dao->fetchColumn();
    if ($numOfRelationships) {
      $rating['has active relationships'] = 1;
    }
    else {
      $rating['has active relationships'] = 0;
    }
  }

  private function isNotSpam($contact, &$rating) {
    if (strpos($contact['display_name'], '@ssemarketing.net') > 0) {
      $rating['is spam'] = 1;
    }
    else {
      $rating['is spam'] = 0;
    }
  }

  private function hasActiveLogin($contact, &$rating) {
    $drupalId = $this->getDrupalIdFromUfMatch($contact['id']);
    if ($drupalId) {
      $rating['has login'] = 1;
    }
    else {
      $rating['has login'] = 0;
    }

    $this->isActiveDrupalUser($drupalId, $rating);
  }

  private function getDrupalIdFromUfMatch($contactId) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $sql = "
      select
        uf_id
      from
        civicrm_uf_match
      where
        contact_id = $contactId
    ";

    $dao = $pdo->query($sql);
    $drupalId = $dao->fetchColumn();
    return $drupalId;
  }

  private function hasPostalAddress($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        id
      from
        civicrm_address
      where
        contact_id = $contactId and is_primary = 1
    ";

    $dao = $pdo->query($sql);
    $id = $dao->fetchColumn();
    if ($id) {
      $rating['has postal address'] = 1;
    }
    else {
      $rating['has postal address'] = 0;
    }
  }

  private function hasEmailAddress($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        id
      from
        civicrm_email
      where
        contact_id = $contactId and is_primary = 1
    ";

    $dao = $pdo->query($sql);
    $id = $dao->fetchColumn();
    if ($id) {
      $rating['has email address'] = 1;
    }
    else {
      $rating['has email address'] = 0;
    }
  }

  private function isActiveDrupalUser($drupalId, $rating) {
    if ($drupalId) {
      // TODO: query users table
      $rating['is active Drupal account'] = 1;
    }
    else {
      $rating['is active Drupal account'] = 0;
    }

  }

  private function calculateScore(&$rating) {
    $score = 0;

    foreach ($rating as $k => $v) {
      $score += $v;
    }

    $rating['score'] = $score;
  }

  private function deleteLogFile() {
    if (file_exists(self::LOG_FILE)) {
      unlink(self::LOG_FILE);
    }
  }

  private function createLogFile($rating) {
    $header = [
      'Contact Id',
      'Display Name',
    ];

    foreach ($rating as $k => $v) {
      $header[] = $k;
    }

    $tabSeparatedColumnNames = implode("\t", $header) . "\n";
    file_put_contents(self::LOG_FILE, $tabSeparatedColumnNames);

    $this->logFileCreated = TRUE;
  }

  private function logContact($contact, $rating) {
    if ($this->logFileCreated == FALSE) {
      $this->createLogFile($rating);
    }

    $row = [
      $contact['id'],
      $contact['display_name'],
    ];

    foreach ($rating as $k => $v) {
      $row[] = $v;
    }

    $tabSeparatedRow = implode("\t", $row) . "\n";
    file_put_contents(self::LOG_FILE, $tabSeparatedRow, FILE_APPEND);
  }
}
