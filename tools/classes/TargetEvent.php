<?php

class TargetEvent {
  private $optionGroupId_EventType;
  private $targetMigrationHelper;
  private $locBlockMuntpunt = 0;
  private $customFieldNameCache = [];

  public function __construct() {
    $this->optionGroupId_EventType = CRM_Core_DAO::singleValueQuery("select id from civicrm_option_group where name = 'event_type'");
    $this->targetMigrationHelper = new TargetMigrationHelper();
  }

  public function create($sourceEvent) {
    $oldEventId = $sourceEvent['id'];

    unset($sourceEvent['id']);
    unset($sourceEvent['created_id']);
    unset($sourceEvent['loc_block_id']);
    unset($sourceEvent['participant_listing_id']);
    unset($sourceEvent['payment_processor']);

    if (!$this->isValidCampaignId($sourceEvent['campaign_id'])) {
      unset($sourceEvent['campaign_id']);
    }

    if ($sourceEvent['financial_type_id'] == 7) {
      $sourceEvent['financial_type_id'] = 4;
    }
    $sourceEvent['sequential'] = 1;

    try {
      $result = civicrm_api3('Event', 'create', $sourceEvent);
    }
    catch (Exception $e) {
      // retry
      unset($sourceEvent['dedupe_rule_group_id']);
      $result = civicrm_api3('Event', 'create', $sourceEvent);
    }


    if ($result['is_error']) {
      throw new Exception("Cannot create event " . $sourceEvent['title']);
    }
    else {
      $newEventId = $result['values'][0]['id'];
      $this->targetMigrationHelper->storeIds('civicrm_event', $oldEventId, $newEventId);
      return $newEventId;
    }
  }

  public function createRecurringEvent($recurringEvent) {
    $parentId = $this->targetMigrationHelper->getNewId('civicrm_event', $recurringEvent['parent_id']);
    $entityId = $this->targetMigrationHelper->getNewId('civicrm_event', $recurringEvent['entity_id']);

    if ($parentId && $entityId) {
      $sql = "
        insert into
          civicrm_recurring_entity (
           parent_id,
           entity_id,
           entity_table,
           mode
        )
        values (
           %1,
           %2,
           'civicrm_event',
           %3
        )
      ";
      $sqlParams = [
        1 => [$parentId, 'Integer'],
        2 => [$entityId, 'Integer'],
        3 => [$recurringEvent['mode'], 'Integer'],
      ];

      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  public function createParticipant($newEventId, $sourceParticipant) {
    $newContactId = TargetContactFinder::getContactIdByOldContactId($sourceParticipant['contact_id']);

    if ($this->isParticipantRegistered($newEventId, $newContactId)) {
      return;
    }

    $oldParticipantId = $sourceParticipant['id'];
    unset($sourceParticipant['id']);
    $sourceParticipant['event_id'] = $newEventId;
    $sourceParticipant['contact_id'] = $newContactId;
    $sourceParticipant['sequential'] = 1;

    if (!$this->isValidCampaignId($sourceParticipant['campaign_id'])) {
      unset($sourceParticipant['campaign_id']);
    }

    if ($sourceParticipant['status_id'] == 19) {
      $sourceParticipant['status_id'] = 1;
    }

    if ($sourceParticipant['registered_by_id']) {
      $newRegisteredById = $this->targetMigrationHelper->getNewId('civicrm_participant', $sourceParticipant['registered_by_id']);
      if ($newRegisteredById) {
        $sourceParticipant['registered_by_id'] = $newRegisteredById;
      }
      else {
        unset($sourceParticipant['registered_by_id']);
      }
    }

    $result = civicrm_api3('Participant', 'create', $sourceParticipant);
    $newParticipantId = $result['values'][0]['id'];
    $this->targetMigrationHelper->storeIds('civicrm_participant', $oldParticipantId, $newParticipantId);

    return $newParticipantId;
  }

  private function isParticipantRegistered($newEventId, $newContactId) {
    $sql = "select id from civicrm_participant where event_id = $newEventId and contact_id = $newContactId";
    if (CRM_Core_DAO::singleValueQuery($sql)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function createEventType($sourceEventType) {
    $toRemove = ['id', 'is_optgroup', 'component_id', 'domain_id', 'visibility_id'];
    foreach ($toRemove as $key) {
      unset($sourceEventType[$key]);
    }

    $sourceEventType['option_group_id'] = $this->optionGroupId_EventType;

    civicrm_api3('OptionValue', 'create', $sourceEventType);
  }

  public function deleteAllEventTypes() {
    CRM_Core_DAO::executeQuery("delete from civicrm_option_value where option_group_id = " . $this->optionGroupId_EventType);
  }

  public function enableWaitingList() {
    $sql = "update civicrm_participant_status_type set is_active = 1 where id in (7,9)";
    CRM_Core_DAO::executeQuery($sql);
  }

  public function addLocBlock($newEventId, $locBlock) {
    if ($this->isLocBlockMuntpunt($locBlock)) {
      if ($this->locBlockMuntpunt == 0) {
        // not in the cache yet
        $this->locBlockMuntpunt = $this->createLocBlock($locBlock);
      }

      $newLocBlockId = $this->locBlockMuntpunt;
    }
    else {
      // do we have the id in the cache?
      $newLocBlockId = $this->targetMigrationHelper->getNewId('civicrm_loc_block', $locBlock['id']);
      if (empty($newLocBlockId)) {
        $newLocBlockId = $this->createLocBlock($locBlock);
        $this->targetMigrationHelper->storeIds('civicrm_loc_block', $locBlock['id'], $newLocBlockId);
      }
    }

    CRM_Core_DAO::executeQuery("update civicrm_event set loc_block_id = $newLocBlockId where id = $newEventId");
  }

  private function isLocBlockMuntpunt($locBlock) {
    if ($locBlock['name'] == 'Muntpunt' && $locBlock['street_address'] == 'Munt 6') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function createLocBlock($locBlock) {
    $params = [
      'sequential' => 1,
    ];

    if ($locBlock['address_id']) {
      $params['address_id'] = $this->createLocBlockAddress($locBlock);
    }

    if ($locBlock['email_id']) {
      $params['email_id'] = $this->createLocBlockEmail($locBlock);
    }

    if ($locBlock['phone_id']) {
      $params['phone_id'] = $this->createLocBlockPhone($locBlock);
    }

    $result = civicrm_api3('LocBlock', 'create', $params);

    return $result['values'][0]['id'];
  }

  public function createLocBlockAddress($locBlock) {
    if ($locBlock['name'] == 'Stripcentrum Brussel') {
      $locBlock['name'] = 'Stripmuseum Brussel';
    }

    $params = [
      'sequential' => 1,
      'street_address' => $locBlock['street_address'],
      'supplemental_address_1' => $locBlock['supplemental_address_1'],
      'supplemental_address_2' => $locBlock['supplemental_address_2'],
      'supplemental_address_3' => $locBlock['supplemental_address_3'],
      'city' => $locBlock['city'],
      'postal_code' => $locBlock['postal_code'],
      'country_id' => $locBlock['country_id'],
      'name' => $locBlock['name'],
      'location_type_id' => 1,
      'is_primary' => 1,
      'contact_id' => 1, // DUMMY, otherwise the API does not work - will be set to NULL after API call
    ];
    $result = civicrm_api3('Address', 'create', $params);
    CRM_Core_DAO::executeQuery("update civicrm_address set contact_id = NULL where id = " . $result['values'][0]['id']);
    return $result['values'][0]['id'];
  }

  public function createLocBlockEmail($locBlock) {
    $params = [
      'sequential' => 1,
      'email' => $locBlock['email'],
      'location_type_id' => 1,
      'is_primary' => 1,
      'contact_id' => 1, // DUMMY, otherwise the API does not work - will be set to NULL after API call
    ];
    $result = civicrm_api3('Email', 'create', $params);
    CRM_Core_DAO::executeQuery("update civicrm_email set contact_id = NULL where id = " . $result['values'][0]['id']);
    return $result['values'][0]['id'];
  }

  public function createLocBlockPhone($locBlock) {
    $params = [
      'sequential' => 1,
      'phone' => $locBlock['phone'],
      'location_type_id' => 1,
      'phone_type_id' => 1,
      'is_primary' => 1,
      'contact_id' => 1, // DUMMY, otherwise the API does not work - will be set to NULL after API call
    ];
    $result = civicrm_api3('Phone', 'create', $params);
    CRM_Core_DAO::executeQuery("update civicrm_phone set contact_id = NULL where id = " . $result['values'][0]['id']);
    return $result['values'][0]['id'];
  }

  private function isValidCampaignId($id) {
    if (empty($id)) {
      return TRUE;
    }

    $id = CRM_Core_DAO::singleValueQuery("select id from civicrm_campaign where id = $id");
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
