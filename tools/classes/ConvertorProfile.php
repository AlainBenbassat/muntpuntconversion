<?php

class ConvertorProfile {
  private $profileFetcher;
  private $targetProfile;

  public function __construct() {
    $this->profileFetcher = new SourceProfileFetcher();
    $this->targetProfile = new TargetProfile();
  }

  public function run() {
    TargetMigrationHelper::clearMappingOldIdNewId('civicrm_uf_group');

    $dao = $this->profileFetcher->getProfilesToMigrate();
    while ($profile = $dao->fetch()) {
      $newProfileId = $this->targetProfile->create($profile);

      $this->convertProfileFields($profile['id'], $newProfileId);
    }
  }

  private function convertProfileFields($oldProfileId, $newProfileId) {
    $dao = $this->profileFetcher->getProfileFields($oldProfileId);
    while ($profileField = $dao->fetch()) {
      try {
        $this->targetProfile->createField($newProfileId, $profileField);
      }
      catch (Exception $e) {
        echo "FOUT tijdens converteren van profiel $oldProfileId:\n";
        var_dump($profileField);
      }
    }
  }
}
