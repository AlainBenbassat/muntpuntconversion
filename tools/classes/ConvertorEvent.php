<?php

class ConvertorEvent {
  private $eventFetcher;
  private $targetEvent;
  private $profileFetcher;
  private $targetProfile;
  private $customDataFetcher;
  private $targetCustomData;

  public function __construct() {
    $this->eventFetcher = new SourceEventFetcher();
    $this->targetEvent = new TargetEvent();
    $this->profileFetcher = new SourceProfileFetcher();
    $this->targetProfile = new TargetProfile();
    $this->customDataFetcher = new SourceCustomDataFetcher();
    $this->targetCustomData = new TargetCustomData();
  }

  public function run() {
    TargetMigrationHelper::clearMappingOldIdNewId('civicrm_event');
    TargetMigrationHelper::clearMappingOldIdNewId('civicrm_participant');
    TargetMigrationHelper::clearMappingOldIdNewId('civicrm_loc_block');

    $this->convertEvents();
    $this->convertRecurringEvents();
  }

  public function convertEvents() {
    $dao = $this->eventFetcher->getAllEventsToMigrate();
    while ($sourceEvent = $dao->fetch()) {
      echo 'Converting event ' . $sourceEvent['title'] . ' (' . $sourceEvent['start_date'] . ")...\n";

      $oldEventId = $sourceEvent['id'];
      $newEventId = $this->targetEvent->create($sourceEvent);

      echo "  converting loc block...\n";
      if ($sourceEvent['loc_block_id']) {
        $locBlock = $this->eventFetcher->getLocBlock($sourceEvent['loc_block_id']);
        $this->targetEvent->addLocBlock($newEventId, $locBlock);
      }

      $customGroups = $this->customDataFetcher->getCustomGroupsForEvents();
      foreach ($customGroups as $customGroupId => $customGroupName) {
        $customDataSet = $this->customDataFetcher->getCustomDataSetOfEntity($oldEventId, $customGroupId);
        $this->targetCustomData->create($newEventId, $customDataSet);
      }

      echo "  converting profiles...\n";
      $this->convertEventProfiles($oldEventId, $newEventId);

      echo "  converting participants...\n";
      $this->convertEventParticipants($oldEventId, $newEventId);
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

      $this->targetEvent->createParticipant($newEventId, $sourceParticipant);
    }
  }

  public function convertEventProfiles($sourceEventId, $newEventId) {
    $dao = $this->profileFetcher->getEventProfiles($sourceEventId);
    while ($profile = $dao->fetch()) {
      $this->targetProfile->createEventProfile($newEventId, $profile);
    }
  }
}
