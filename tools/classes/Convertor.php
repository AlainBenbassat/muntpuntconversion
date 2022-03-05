<?php

class Convertor {
  private $batchLimit;
  private $contactFetcher;
  private $targetContact;
  private $targetAddress;
  private $targetEmail;
  private $targetPhone;
  private $eventFetcher;
  private $targetEvent;
  private $targetRelationship;

  public function __construct($batchLimit = 200) {
    $this->batchLimit = $batchLimit;

    $this->contactFetcher = new SourceContactFetcher();
    $this->targetContact = new TargetContact();
    $this->targetAddress = new TargetAddress();
    $this->targetEmail = new TargetEmail();
    $this->targetPhone = new TargetPhone();
    $this->targetRelationship = new TargetRelationship();
    $this->eventFetcher = new SourceEventFetcher();
    $this->relationshipFetcher = new SourceRelationshipFetcher();
    $this->targetEvent = new TargetEvent();
  }

  public function convertContacts($clearContactTable = FALSE) {
    if ($clearContactTable) {
      CRM_Core_DAO::executeQuery("delete from civicrm_contact where id > 2");
    }

    $dao = $this->contactFetcher->getValidMainContacts(0, $this->batchLimit);
    while ($mainContactInfo = $dao->fetch()) {
      $newMainContactId = $this->processMainContact($mainContactInfo);

      $daoDupes = $this->contactFetcher->getDuplicateContacts($mainContactInfo['id']);
      while ($duplicateContactInfo = $daoDupes->fetch()) {
        $this->processDuplicateContact($newMainContactId, $duplicateContactInfo);
      }
    }
  }

  public function convertEventTypesRolesEtc() {
    $this->convertEventTypes();
  }

  public function convertRelationships() {
    $dao = $this->relationshipFetcher->getAllEmployeeRelationshipsToMigrate();
    while ($employeeRelationship = $dao->fetch()) {
      echo 'Converting employee relationship between ' . $employeeRelationship['contact_id_a'] . ' and ' . $employeeRelationship['contact_id_b'] . "...\n";

      $this->targetRelationship->createRelationship($employeeRelationship);
    }
  }

  public function convertEventTypes() {
    $dao = $this->eventFetcher->getEventTypes();
    while ($sourceEventType = $dao->fetch()) {
      echo 'Converting event type ' . $sourceEventType['label'] . ")...\n";

      $this->targetEvent->createEventType($sourceEventType);
    }
  }

  public function convertEvents() {
    TargetMigrationHelper::initialize();

    $dao = $this->eventFetcher->getAllEventsToMigrate();
    while ($sourceEvent = $dao->fetch()) {
      echo 'Converting event ' . $sourceEvent['title'] . '(' . $sourceEvent['start_date'] . ")...\n";

      $newEventId = $this->targetEvent->create($sourceEvent);

      echo "  converting loc block...\n";
      if ($sourceEvent['loc_block_id']) {
        $locBlock = $this->eventFetcher->getLocBlock($sourceEvent['loc_block_id']);
        $this->targetEvent->addLocBlock($newEventId, $locBlock);
      }

      echo "  converting customfields extra info...\n";
      $customFields = $this->eventFetcher->getEventCustomFields($sourceEvent['id'], 'private_extraevent');
      $this->targetEvent->addCustomFieldsExtraInfo($newEventId, $customFields);

      echo "  converting custom fields info...\n";
      $customFields = $this->eventFetcher->getEventCustomFields($sourceEvent['id'], 'Private_event_info');
      $this->targetEvent->addCustomFieldsInfo($newEventId, $customFields);

      echo "  converting custom fields BIOS...\n";
      $customFields = $this->eventFetcher->getEventCustomFields($sourceEvent['id'], 'Private_Bios');
      $this->targetEvent->addCustomFieldsPrivateBIOS($newEventId, $customFields);

      echo "  converting participants...\n";
      $this->convertEventParticipants($sourceEvent['id'], $newEventId);
    }
  }

  public function convertEventParticipants($sourceEventId, $newEventId) {
    $dao = $this->eventFetcher->getEventParticipants($sourceEventId);
    while ($sourceParticipant = $dao->fetch()) {
      echo '  Converting participant ' . $sourceParticipant['id'] . "...\n";

      $newContactId = TargetContactFinder::getContactIdByOldContactId($sourceParticipant['contact_id']);
      if ($newEventId) {
        $this->targetEvent->createParticipant($newEventId, $newContactId, $sourceParticipant);
      }
    }
  }

  private function processMainContact($contactInfo) {
    //echo 'Converting main contact ' . $contactInfo['id'] . "...\n";

    $contact = $this->contactFetcher->getContact($contactInfo['id']);
    $newContactId = $this->targetContact->create($contact);

    $this->targetContact->addOldCiviCRMId($contact['id'], $newContactId);

    if ($contactInfo['heeft_emailadres'] && $contactInfo['email_onhold'] == 0) {
      $this->targetEmail->create($newContactId, $contactInfo['email']);
    }

    if ($contactInfo['heeft_postadres']) {
      $sourceAddress = $this->contactFetcher->getPrimaryAddress($contact['id']);
      $this->targetAddress->create($newContactId, $sourceAddress);
    }

    if ($contactInfo['heeft_telefoonnummer']) {
      $sourcePhones = $this->contactFetcher->getPhones($contact['id']);
      if (count($sourcePhones)) {
        $this->targetPhone->create($newContactId, $sourcePhones);
      }
    }


    // contributions
    // events
    // relationships
    // groups
    // mailchimp

    return $newContactId;
  }

  private function processDuplicateContact($mainContactId, $contactInfo) {
    //echo '  merging duplicate contact ' . $contactInfo['id'] . "...\n";

    $contact = $this->contactFetcher->getContact($contactInfo['id']);
    $this->targetContact->merge($mainContactId, $contact);

    $this->targetContact->addOldCiviCRMId($contact['id'], $mainContactId);

    if ($contactInfo['heeft_postadres']) {
      $sourceAddress = $this->contactFetcher->getPrimaryAddress($contact['id']);
      $this->targetAddress->merge($mainContactId, $sourceAddress);
    }

    if ($contactInfo['heeft_telefoonnummer']) {
      $sourcePhones = $this->contactFetcher->getPhones($contact['id']);
      if (count($sourcePhones)) {
        $this->targetPhone->merge($mainContactId, $sourcePhones);
      }
    }
  }

  private function convertCustomGroup($customGroupName) {
    $sourceCustomDataFetcher = new SourceCustomDataFetcher();
    $targetCustomData = new TargetCustomData();

    $this->convertOptionGroupsFromCustomGroup($customGroupName);

    echo "Converting custom group $customGroupName...\n";
    $customGroup = $sourceCustomDataFetcher->getCustomDataGroup($customGroupName);
    $targetCustomData->createCustomGroup($customGroup);

    $customFieldDAO = $sourceCustomDataFetcher->getCustomFields($customGroup['id']);
    while ($customField = $customFieldDAO->fetch()) {
      echo "Converting custom field $customGroupName...\n";
      $targetCustomData->createCustomField($customField);
    }
  }

  private function convertOptionGroupsFromCustomGroup($customGroupName) {
    $sourceCustomDataFetcher = new SourceCustomDataFetcher();
    $targetCustomData = new TargetCustomData();

    $optionGroupListDao = $sourceCustomDataFetcher->getCustomGroupOptionGroups($customGroupName);
    while ($sourceOptionGroup = $optionGroupListDao->fetch()) {
      echo 'Converting option group ' . $sourceOptionGroup['option_group_id'] . "...\n";

      [$optionGroupId, $name, $title] = $sourceCustomDataFetcher->getOptionGroupDetails($sourceOptionGroup['option_group_id']);
      $targetCustomData->createOptionGroup($optionGroupId, $name, $title);

      $optionValuesDAO = $sourceCustomDataFetcher->getOptionValues($optionGroupId);
      while ($sourceOptionValue = $optionValuesDAO->fetch()) {
        echo 'Convertion option value ' . $sourceOptionValue['label'] . "...\n";
        $targetCustomData->createOptionValue($sourceOptionValue);
      }
    }
  }


}
