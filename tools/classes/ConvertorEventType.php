<?php

class ConvertorEventType {
  private $eventFetcher;
  private $targetEvent;

  public function __construct() {
    $this->eventFetcher = new SourceEventFetcher();
    $this->targetEvent = new TargetEvent();
  }

  public function run() {
    $dao = $this->eventFetcher->getEventTypes();
    while ($sourceEventType = $dao->fetch()) {
      echo 'Converting event type ' . $sourceEventType['label'] . ")...\n";

      $this->targetEvent->createEventType($sourceEventType);
    }
  }
}
