<?php

class ConvertorGroup {
  private $groupFetcher;
  private $targetGroup;

  public function __construct() {
    $this->groupFetcher = new SourceGroupFetcher();
    $this->targetGroup = new TargetGroup();
  }

  public function run() {
    $dao = $this->groupFetcher->getGroupsToMigrate();
    while ($group = $dao->fetch()) {
      echo 'Converting group ' . $group['id'] . "...\n";

      $this->targetGroup->create($group);

      $this->convertGroupContacts($group['id']);
    }
  }

  public function convertGroupContacts($groupId) {
    $dao = $this->groupFetcher->getGroupContacts($groupId);
    while ($groupContact = $dao->fetch()) {
      echo 'Converting group contact ' . $groupContact['contact_id'] . "...\n";

      $this->targetGroup->createGroupContact($groupId, $groupContact['contact_id']);
    }
  }
}
