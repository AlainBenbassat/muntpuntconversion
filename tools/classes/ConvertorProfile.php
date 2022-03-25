<?php

class ConvertorProfile {
  private $profileFetcher;
  private $targetProfile;

  public function __construct() {
    $this->profileFetcher = new SourceProfileFetcher();
    $this->targetProfile = new TargetProfile();
  }

  public function run() {
    TargetMigrationHelper::initialize();

    $dao = $this->profileFetcher->getProfilesToMigrate();
    while ($profile = $dao->fetch()) {
      $newProfileId = $this->targetProfile->create($profile);

      $this->convertProfileFields($profile['id'], $newProfileId);
    }
  }

  private function convertProfileFields($oldProfileId, $newProfileId) {
    $dao = $this->profileFetcher->getProfileFields($oldProfileId);
    while ($profileField = $dao->fetch()) {
      $this->targetProfile->createField($newProfileId, $profileField);
    }
  }
}
