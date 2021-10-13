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
    $this->isIndividualOrOrganization($contact, $rating);
    $this->hasActiveRelationships($contact, $rating);
    $this->isNotSpam($contact, $rating);
    $this->hasActiveLogin($contact, $rating);
    $this->hasPostalAddress($contact, $rating);
    $this->hasPhoneNumber($contact, $rating);
    $this->hasEmailAddress($contact, $rating);
    $this->hasRecentActivities($contact, $rating);
    $this->hasRecentEventRegistrations($contact,$rating);
    $this->hasOptedOut($contact, $rating);
    $this->isGroupMemberKeepMeInformed($contact, $rating);

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
      $rating['heeft naam'] = 1;
    }
    else {
      $rating['heeft naam'] = 0;
    }
  }

  private function isIndividualOrOrganization($contact, &$rating) {
    if ($contact['contact_type'] == 'Individual' || $contact['contact_type'] == 'Organization') {
      $rating['is persoon of organisatie'] = 1;
    }
    else {
      $rating['is persoon of organisatie'] = 0;
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
      $rating['heeft actieve relaties'] = 1;
    }
    else {
      $rating['heeft actieve relaties'] = 0;
    }
  }

  private function isNotSpam($contact, &$rating) {
    if (strpos($contact['display_name'], '@ssemarketing.net') > 0) {
      $rating['is geen spam'] = 0;
    }
    else {
      $rating['is geen spam'] = 1;
    }
  }

  private function hasActiveLogin($contact, &$rating) {
    $drupalId = $this->getDrupalIdFromUfMatch($contact['id']);
    if ($drupalId) {
      $rating['heeft Drupal login'] = 1;
    }
    else {
      $rating['heeft Drupal login'] = 0;
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
      $rating['heeft postadres'] = 1;
    }
    else {
      $rating['heeft postadres'] = 0;
    }
  }

  private function hasPhoneNumber($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        id
      from
        civicrm_phone
      where
        contact_id = $contactId and is_primary = 1
    ";

    $dao = $pdo->query($sql);
    $id = $dao->fetchColumn();
    if ($id) {
      $rating['heeft telefoonnummer'] = 1;
    }
    else {
      $rating['heeft telefoonnummer'] = 0;
    }
  }

  private function hasEmailAddress($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        *
      from
        civicrm_email
      where
        contact_id = $contactId
      and
        is_primary = 1
    ";

    $dao = $pdo->query($sql);
    $row = $dao->fetch();
    if ($row) {
      $rating['heeft e-mailadres'] = 1;
      $rating['e-mail is niet on-hold'] = $row['on_hold'] == 1 ? 0 : 1;
      $rating['e-mail is uniek'] = $this->isUniqueEmailAddress($row['email']);
    }
    else {
      $rating['heeft e-mailadres'] = 0;
      $rating['e-mail is niet on-hold'] = 0;
      $rating['e-mail is uniek'] = 0;
    }
  }

  private function isUniqueEmailAddress($email) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $quotedEmail = $pdo->quote($email);
    $sql = "
      select
        count(e.id)
      from
        civicrm_email e
      WHERE
        e.email = $quotedEmail
      and
        exists (
          select * from civicrm_contact c where c.id = e.contact_id and c.is_deleted = 0
        )
      group by
        e.email
      having
        count(e.id) > 1
    ";

    $dao = $pdo->query($sql);
    $emailCount = $dao->fetchColumn();
    if ($emailCount) {
      return 0;
    }
    else {
      return 1;
    }
  }

  private function hasRecentActivities($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        count(a.id)
      from
        civicrm_activity a
      inner join
        civicrm_activity_contact ac on ac.activity_id = a.id
      where
        ac.contact_id = $contactId
      and
        a.is_deleted = 0
      and
        a.activity_date_time >= '2019-01-01'
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['heeft recente activiteiten'] = 1;
    }
    else {
      $rating['heeft recente activiteiten'] = 0;
    }
  }

  private function hasRecentEventRegistrations($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        count(p.id)
      from
        civicrm_participant p
      where
        p.contact_id = $contactId
      and
        p.register_date >= '2017-01-01'
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['heeft recent deelgenomen aan evenementen'] = 1;
    }
    else {
      $rating['heeft recent deelgenomen aan evenementen'] = 0;
    }
  }

  private function isActiveDrupalUser($drupalId, $rating) {
    if ($drupalId) {
      // TODO: query users table
      $rating['heeft actief Drupal account'] = 1;
    }
    else {
      $rating['heeft actief Drupal account'] = 0;
    }

  }

  private function hasOptedOut($contact, &$rating) {
    if ($contact['is_opt_out'] == 1) {
      $rating['is niet opt-out'] = 1;
    }
    else {
      $rating['is niet opt-out'] = 0;
    }
  }

  private function isGroupMemberKeepMeInformed($contact, &$rating) {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        count(gc.id)
      from
        civicrm_group_contact gc
      inner join
        civicrm_group g on g.id = gc.group_id
      WHERE
        gc.status = 'Added'
      and
        g.title like 'Hou mij op de hoogte%'
      and
        gc.contact_id = $contactId
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['lid van groep Hou mij op de hoogte'] = 1;
    }
    else {
      $rating['lid van groep Hou mij op de hoogte'] = 0;
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
      'Contact Type'
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
      $contact['contact_type'],
    ];

    foreach ($rating as $k => $v) {
      $row[] = $v;
    }

    $tabSeparatedRow = implode("\t", $row) . "\n";
    file_put_contents(self::LOG_FILE, $tabSeparatedRow, FILE_APPEND);
  }
}
