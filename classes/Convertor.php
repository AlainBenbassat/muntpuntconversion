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

  public function __construct($batchLimit = 200) {
    $this->batchLimit = $batchLimit;

    $this->contactFetcher = new SourceContactFetcher();
    $this->targetContact = new TargetContact();
    $this->targetAddress = new TargetAddress();
    $this->targetEmail = new TargetEmail();
    $this->targetPhone = new TargetPhone();
    $this->eventFetcher = new SourceEventFetcher();
    $this->targetEvent = new TargetEvent();
  }

  public function convertContacts($clearContactTable = FALSE) {
    if ($clearContactTable) {
      CRM_Core_DAO::executeQuery("delete from civicrm_contact where id > 2");
    }

    $dao = $this->contactFetcher->getValidMainContacts(223745, $this->batchLimit);
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

      if ($sourceEvent['loc_block_id']) {
        $locBlock = $this->eventFetcher->getLocBlock($sourceEvent['loc_block_id']);
        $this->targetEvent->addLocBlock($newEventId, $locBlock);
      }

      $this->convertEventParticipants($sourceEvent['id'], $newEventId);
    }
  }

  public function convertEventParticipants($sourceEventId, $newEventId) {
    $dao = $this->eventFetcher->getEventParticipants($sourceEventId);
    while ($sourceParticipant = $dao->fetch()) {
      echo '  Converting participant ' . $sourceParticipant['id'] . "...\n";

      $newContactId = TargetContactFinder::getContactIdByOldContactId($sourceParticipant['contact_id']);
      $this->targetEvent->createParticipant($newEventId, $newContactId, $sourceParticipant);
    }
  }

  private function processMainContact($contactInfo) {
    echo 'Converting main contact ' . $contactInfo['id'] . "...\n";

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
    echo '  merging duplicate contact ' . $contactInfo['id'] . "...\n";

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
}
