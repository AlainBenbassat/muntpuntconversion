<?php

namespace Muntpuntconversion;

class SourceContactValidator {
  public const FINAL_SCORE_MIGRATE = 1;
  public const FINAL_SCORE_DO_NOT_MIGRATE = -1;
  public const FINAL_SCORE_NEEDS_CLEANUP = 0;

  private const SCORE_NEUTRAL = 0;
  private const SCORE_GOOD = 10;
  private const SCORE_VERY_GOOD = 50;
  private const SCORE_BAD = -10;
  private const SCORE_VERY_BAD = -500;

  public function getValidationRating($contact) {
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

    return $rating;
  }

  private function hasDisplayName($contact, &$rating) {
    if (trim($contact['display_name'])) {
      $rating['heeft naam'] = self::SCORE_NEUTRAL;
    }
    else {
      $rating['heeft naam'] = self::SCORE_VERY_BAD;
    }
  }

  private function isIndividualOrOrganization($contact, &$rating) {
    if ($contact['contact_type'] == 'Individual' || $contact['contact_type'] == 'Organization') {
      $rating['is persoon of organisatie'] = self::SCORE_NEUTRAL;
    }
    else {
      $rating['is persoon of organisatie'] = self::SCORE_VERY_BAD;
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
      $rating['heeft actieve relaties'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft actieve relaties'] = self::SCORE_NEUTRAL;
    }
  }

  private function isNotSpam($contact, &$rating) {
    if (strpos($contact['display_name'], '@ssemarketing.net') > 0) {
      $rating['is spam'] = self::SCORE_VERY_BAD;
    }
    else {
      $rating['is spam'] = self::SCORE_NEUTRAL;
    }
  }

  private function hasActiveLogin($contact, &$rating) {
    $drupalId = $this->getDrupalIdFromUfMatch($contact['id']);
    if ($drupalId) {
      $rating['heeft Drupal login'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft Drupal login'] = self::SCORE_NEUTRAL;
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
      $rating['heeft postadres'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft postadres'] = self::SCORE_NEUTRAL;
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
      $rating['heeft telefoonnummer'] = self::SCORE_GOOD;
    }
    else {
      $rating['heeft telefoonnummer'] = self::SCORE_NEUTRAL;
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
      $rating['heeft e-mailadres'] = self::SCORE_VERY_GOOD;
      $rating['e-mail on-hold'] = $row['on_hold'] == 1 ? self::SCORE_BAD : self::SCORE_NEUTRAL;
      $rating['e-mail is uniek'] = $this->isUniqueEmailAddress($row['email']) ? self::SCORE_GOOD : self::SCORE_BAD;
    }
    else {
      $rating['heeft e-mailadres'] = self::SCORE_BAD;
      $rating['e-mail on-hold'] = self::SCORE_NEUTRAL;
      $rating['e-mail is uniek'] = self::SCORE_NEUTRAL;
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
      return FALSE;
    }
    else {
      return TRUE;
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
      $rating['heeft recente activiteiten'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft recente activiteiten'] = self::SCORE_NEUTRAL;
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
      $rating['heeft recent deelgenomen aan evenementen'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft recent deelgenomen aan evenementen'] = self::SCORE_NEUTRAL;
    }
  }

  private function isActiveDrupalUser($drupalId, $rating) {
    if ($drupalId) {
      // TODO: query users table
      $rating['heeft actief Drupal account'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['heeft actief Drupal account'] = self::SCORE_NEUTRAL;
    }

  }

  private function hasOptedOut($contact, &$rating) {
    if ($contact['is_opt_out'] == 1) {
      $rating['is opt-out'] = self::SCORE_VERY_BAD;
    }
    else {
      $rating['is opt-out'] = self::SCORE_GOOD;
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
      $rating['lid van groep Hou mij op de hoogte'] = self::SCORE_VERY_GOOD;
    }
    else {
      $rating['lid van groep Hou mij op de hoogte'] = self::SCORE_NEUTRAL;
    }
  }

  private function calculateScore(&$rating) {
    $absoluteScore = 0;

    foreach ($rating as $k => $v) {
      $absoluteScore += $v;
    }

    $rating['absolute_score'] = $absoluteScore;

    if ($absoluteScore < self::SCORE_NEUTRAL) {
      $rating['score'] = self::FINAL_SCORE_DO_NOT_MIGRATE;
    }
    elseif ($absoluteScore >= self::SCORE_NEUTRAL and $absoluteScore <= self::SCORE_VERY_GOOD) {
      $rating['score'] = self::FINAL_SCORE_NEEDS_CLEANUP;
    }
    else {
      $rating['score'] = self::FINAL_SCORE_MIGRATE;
    }
  }
}
