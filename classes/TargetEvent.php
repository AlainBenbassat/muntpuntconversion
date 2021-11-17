<?php

class TargetEvent {
  private $optionGroupId_EventType;

  public function __construct() {
    $this->optionGroupId_EventType = CRM_Core_DAO::singleValueQuery("select id from civicrm_option_group where name = 'event_type'");
  }

  public function create($sourceEvent) {
    unset($sourceEvent['id']);
    unset($sourceEvent['created_id']);
    unset($sourceEvent['loc_block_id']); // TIJDELIJK!!!!!!!!!!!!!!!!!!!!!
    unset($sourceEvent['participant_listing_id']);
    unset($sourceEvent['campaign_id']); // TIJDELIJK

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
      return $result['values'][0]['id'];
    }
  }

  public function createParticipant($newEventId, $newContactId, $sourceParticipant) {
    unset($sourceParticipant['id']);
    unset($sourceParticipant['campaign_id']); // TIJDELIJK
    $sourceParticipant['event_id'] = $newEventId;
    $sourceParticipant['contact_id'] = $newContactId;
    $sourceParticipant['sequential'] = 1;

    if ($sourceParticipant['status_id'] == 19) {
      $sourceParticipant['status_id'] = 1;
    }

    try {
      civicrm_api3('Participant', 'create', $sourceParticipant);
    }
    catch (Exception $e) {
      unset($sourceParticipant['registered_by_id']);
      civicrm_api3('Participant', 'create', $sourceParticipant);
    }
  }

  public function createEventType($sourceEventType) {
    if ($this->existsEventType($sourceEventType['value'])) {
      $this->updateEventType($sourceEventType);
    }
    else {
      $this->insertEventType($sourceEventType);
    }
  }

  private function existsEventType($value) {
    $id = CRM_Core_DAO::singleValueQuery("select id from civicrm_option_value where option_group_id = " . $this->optionGroupId_EventType . " and value = $value");
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function updateEventType($sourceEventType) {
    $sql = "
      update
        civicrm_option_value
      set
        name = %3
        , label = %4
        , weight = %5
        , is_active = %6
      where
        option_group_id = %1
      and
        value = %2
    ";
    $sqlParams = [
      1 => [$this->optionGroupId_EventType, 'Integer'],
      2 => [$sourceEventType['value'], 'String'],
      3 => [$sourceEventType['name'], 'String'],
      4 => [$sourceEventType['label'], 'String'],
      5 => [$sourceEventType['weight'], 'Integer'],
      6 => [$sourceEventType['is_active'], 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function insertEventType($sourceEventType) {
    $toRemove = ['id', 'is_optgroup', 'component_id', 'domain_id', 'visibility_id'];
    foreach ($toRemove as $key) {
      unset($sourceEventType[$key]);
    }

    $sourceEventType['option_group_id'] = $this->optionGroupId_EventType;

    civicrm_api3('OptionValue', 'create', $sourceEventType);
  }
}
