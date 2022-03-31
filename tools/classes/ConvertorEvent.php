<?php

class ConvertorEvent {
  private $eventFetcher;
  private $targetEvent;
  private $profileFetcher;
  private $targetProfile;

  public function __construct() {
    $this->eventFetcher = new SourceEventFetcher();
    $this->targetEvent = new TargetEvent();
    $this->profileFetcher = new SourceProfileFetcher();
    $this->targetProfile = new TargetProfile();
  }

  public function run() {
    $this->convertEvents();
    $this->convertRecurringEvents();
  }

  public function convertEvents() {
    $dao = $this->eventFetcher->getAllEventsToMigrate();
    while ($sourceEvent = $dao->fetch()) {
      echo 'Converting event ' . $sourceEvent['title'] . ' (' . $sourceEvent['start_date'] . ")...\n";

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

      echo "  converting profiles...\n";
      $this->convertEventProfiles($sourceEvent['id'], $newEventId);

      echo "  converting participants...\n";
      $this->convertEventParticipants($sourceEvent['id'], $newEventId);
    }
  }

  public function convertRecurringEvents() {
    $dao = $this->eventFetcher->getRecurringEvents();
    while ($recurringEvent = $dao->fetch()) {
      $this->targetEvent->createRecurringEvent($recurringEvent);
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

  public function convertEventProfiles($sourceEventId, $newEventId) {
    $dao = $this->profileFetcher->getEventProfiles($sourceEventId);
    while ($profile = $dao->fetch()) {
      $this->targetProfile->createEventProfile($newEventId, $profile);
    }
  }
}
