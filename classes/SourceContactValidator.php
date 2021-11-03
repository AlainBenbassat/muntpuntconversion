<?php

class SourceContactValidator {
  public const FINAL_SCORE_MIGRATE = 1;
  public const FINAL_SCORE_DO_NOT_MIGRATE = 0;
  public const GROUPS_TO_MIGRATE = '1949, 1915, 729, 239, 311, 1757, 1891, 2003, 145, 279, 1177, 119, 731, 733, 425, 905, 2041, 1753, 1803, 1799, 1755, 1825, 1815, 1797, 1805, 1859, 1865, 2049, 1121, 1367, 1947, 687, 685, 691, 689, 115, 1399, 2051, 743, 893, 895, 869, 1051, 1049, 1501, 1827, 307, 1143, 2059, 1013, 97, 435, 437, 429, 433, 513, 431, 1499, 105, 333, 1511, 843, 829, 331, 511, 121, 441, 867, 589, 671, 673, 1061, 771, 1053, 1055, 1801, 1059, 767, 1155, 841, 107, 127, 269, 405, 133, 2015, 2011, 2013, 2017, 2019, 1153, 735, 1421, 847';

  public function getRating($contact) {
    $rating = [];

    $this->hasDisplayName($contact, $rating);
    $this->isIndividualOrOrganization($contact, $rating);
    $this->isContactSubTypePers($contact, $rating);
    $this->hasActiveRelationships($contact, $rating);
    $this->isSpam($contact, $rating);
    $this->hasActiveLogin($contact, $rating);
    $this->hasPostalAddress($contact, $rating);
    $this->hasPhoneNumber($contact, $rating);
    $this->hasEmailAddress($contact, $rating);
    $this->hasRecentActivities($contact, $rating);
    $this->hasRecentEventRegistrations($contact,$rating);
    $this->hasOptedOut($contact, $rating);
    $this->isGroupMember($contact, $rating);
    $this->isMailchimpContact($contact, $rating);
    $this->isEventPartner($contact, $rating);

    $this->calculateScore($rating);

    return $rating;
  }

  private function hasDisplayName($contact, &$rating) {
    if (trim($contact['display_name'])) {
      $rating['heeft_naam'] = 1;
    }
    else {
      $rating['heeft_naam'] = 0;
    }
  }

  private function isIndividualOrOrganization($contact, &$rating) {
    $rating['contact_type'] = $contact['contact_type'];

    if ($contact['contact_type'] == 'Individual' || $contact['contact_type'] == 'Organization') {
      $rating['is_persoon_of_organisatie'] = 1;
    }
    else {
      $rating['is_persoon_of_organisatie'] = 0;
    }
  }

  private function isContactSubTypePers($contact, &$rating) {
    if (strpos($contact['contact_sub_type'], 'Pers_Medewerker') === FALSE) {
      $rating['is_pers_medewerker'] = 0;
    }
    else {
      $rating['is_pers_medewerker'] = 1;
    }
  }

  private function hasActiveRelationships($contact, &$rating) {
    $pdo = SourceDB::getPDO();

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
      $rating['heeft_actieve_relaties'] = 1;
    }
    else {
      $rating['heeft_actieve_relaties'] = 0;
    }
  }

  private function isSpam($contact, &$rating) {
    if (strpos($contact['display_name'], '@ssemarketing.net') > 0) {
      $rating['is_spam'] = 1;
    }
    else {
      $rating['is_spam'] = 0;
    }
  }

  private function hasActiveLogin($contact, &$rating) {
    $drupalId = $this->getDrupalIdFromUfMatch($contact['id']);
    if ($drupalId) {
      $rating['heeft_Drupal_login'] = 1;
    }
    else {
      $rating['heeft_Drupal_login'] = 0;
    }

    $this->isActiveDrupalUser($drupalId, $rating);
  }

  private function getDrupalIdFromUfMatch($contactId) {
    $pdo = SourceDB::getPDO();

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
    $pdo = SourceDB::getPDO();

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
      $rating['heeft_postadres'] = 1;
    }
    else {
      $rating['heeft_postadres'] = 0;
    }
  }

  private function hasPhoneNumber($contact, &$rating) {
    $pdo = SourceDB::getPDO();

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
      $rating['heeft_telefoonnummer'] = 1;
    }
    else {
      $rating['heeft_telefoonnummer'] = 0;
    }
  }

  private function hasEmailAddress($contact, &$rating) {
    $pdo = SourceDB::getPDO();

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
      $rating['email'] = $row['email'];
      $rating['heeft_emailadres'] = 1;
      $rating['email_onhold'] = $row['on_hold'] == 1 ? 1 : 0;
      $rating['email_is_uniek'] = $this->isUniqueEmailAddress($row['email']) ? 1 : 0;

      if (trim($contact['display_name']) == '' || trim($contact['display_name']) == trim($row['email'])) {
        $rating['display_name_is_email'] =  1;
      }
      else {
        $rating['display_name_is_email'] =  0;
      }
    }
    else {
      $rating['email'] = '';
      $rating['heeft_emailadres'] = 0;
      $rating['email_onhold'] = 0;
      $rating['email_is_uniek'] = 0;
      $rating['display_name_is_email'] = 0;
    }
  }

  private function isUniqueEmailAddress($email) {
    $pdo = SourceDB::getPDO();

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
    $pdo = SourceDB::getPDO();

    $cutOffDate = $contact['contact_type'] == 'Individual' ? '2019-11-01' : '2016-11-01';

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
        a.activity_date_time >= '$cutOffDate'
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['heeft_recente_activiteiten'] = 1;
    }
    else {
      $rating['heeft_recente_activiteiten'] = 0;
    }
  }

  private function hasRecentEventRegistrations($contact, &$rating) {
    $pdo = SourceDB::getPDO();

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
      $rating['heeft_recent_deelgenomen_aan_evenementen'] = 1;
    }
    else {
      $rating['heeft_recent_deelgenomen_aan_evenementen'] = 0;
    }
  }

  private function isActiveDrupalUser($drupalId, &$rating) {
    $pdo = SourceDB::getPDO();

    if ($drupalId) {
      $sql = "
        select
          uid
        from
          users
        where
          uid = $drupalId
        and
          status = 1
      ";

      $dao = $pdo->query($sql);
      $uid = $dao->fetchColumn();
    }
    else {
      $uid = 0;
    }

    if ($uid) {
      $rating['heeft_actief_Drupal_account'] = 1;
    }
    else {
      $rating['heeft_actief_Drupal_account'] = 0;
    }

  }

  private function hasOptedOut($contact, &$rating) {
    if ($contact['is_opt_out'] == 1) {
      $rating['is_optout'] = 1;
    }
    else {
      $rating['is_optout'] = 0;
    }
  }

  private function isGroupMember($contact, &$rating) {
    $pdo = SourceDB::getPDO();

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
        g.id in (" . self::GROUPS_TO_MIGRATE . ")
      and
        gc.contact_id = $contactId
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['lid_van_groep'] = 1;
    }
    else {
      $rating['lid_van_groep'] = 0;
    }
  }

  private function isMailchimpContact($contact, &$rating) {
    $pdo = SourceDB::getPDO();
    $sql = "
      select
        group_id
      from
        " . SourceContactLogger::LOG_TABLE_MC_GROUP_CONTACTS . "
      where
        email = " . $pdo->quote($rating['email']) . "
    ";
    $dao = $pdo->query($sql);
    if ($dao->fetch()) {
      $rating['is_mailchimp_contact'] = 1;
    }
    else {
      $rating['is_mailchimp_contact'] = 0;
    }
  }

  private function isEventPartner($contact, &$rating) {
    if ($contact['contact_type'] == 'Individual') {
      $rating['is_evenement_partner'] = 0;
    }

    $pdo = SourceDB::getPDO();

    $contactId = $contact['id'];
    $sql = "
      select
        count(id)
      from
        civicrm_value_private_event_info_115
      where
        partner_1_331 = $contactId
      or
        partner_2__333 = $contactId
      or
        partner_3__339 = $contactId
      or
        partner_4__337 = $contactId
    ";

    $dao = $pdo->query($sql);
    $activityCount = $dao->fetchColumn();
    if ($activityCount) {
      $rating['is_evenement_partner'] = 1;
    }
    else {
      $rating['is_evenement_partner'] = 0;
    }
  }

  private function calculateScore(&$rating) {
    if ($rating['contact_type'] == 'Individual') {
      $this->calculateScoreIndividual($rating);
    }
    elseif ($rating['contact_type'] == 'Organization') {
      $this->calculateScoreOrganization($rating);
    }
    else {
      $rating['score'] = self::FINAL_SCORE_DO_NOT_MIGRATE;
    }
  }

  private function calculateScoreIndividual(&$rating) {
    $rating['score'] = self::FINAL_SCORE_DO_NOT_MIGRATE;

    if ($rating['heeft_emailadres'] == 0) {
      return;
    }

    if ($rating['email_onhold'] == 1) {
      return;
    }

    if ($rating['is_spam'] == 1) {
      return;
    }

    if ($rating['is_optout'] == 1) {
      return;
    }

    // we keep this contact
    if ($rating['heeft_recente_activiteiten'] == 1
      || $rating['lid_van_groep'] == 1
      || $rating['is_pers_medewerker'] == 1
      || $rating['is_mailchimp_contact'] == 1
    ) {
      $rating['score'] = self::FINAL_SCORE_MIGRATE;
    }
  }

  private function calculateScoreOrganization(&$rating) {
    $rating['score'] = self::FINAL_SCORE_DO_NOT_MIGRATE;

    if ($rating['heeft_naam'] == 0) {
      return;
    }

    // we keep this contact
    if ($rating['heeft_recente_activiteiten'] == 1
      || $rating['lid_van_groep'] == 1
      || $rating['is_mailchimp_contact'] == 1
      || $rating['is_evenement_partner'] == 1
    ) {
      $rating['score'] = self::FINAL_SCORE_MIGRATE;
    }
  }
}
