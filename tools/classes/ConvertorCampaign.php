<?php

class ConvertorCampaign {
  private $campaignFetcher;
  private $targetCampaign;

  public function __construct() {
    $this->campaignFetcher = new SourceCampaignFetcher();
    $this->targetCampaign = new TargetCampaign();
  }

  public function run() {
    $this->disableAllCampaignTypes();
    $this->migrateCampaignTypes();
    $this->migrateCampaigns();
  }

  private function disableAllCampaignTypes() {
    $this->targetCampaign->disableCampaignTypes();
  }

  private function migrateCampaignTypes() {
    $dao = $this->campaignFetcher->getCampaignTypesToMigrate();
    while ($campaignType = $dao->fetch()) {
      echo 'Converting campaign type ' . $campaignType['id'] . "...\n";

      $this->targetCampaign->createCampaignType($campaignType);
    }
  }

  private function migrateCampaigns() {
    $dao = $this->campaignFetcher->getCampaignsToMigrate();
    while ($campaign = $dao->fetch()) {
      echo 'Converting campaign ' . $campaign['id'] . "...\n";

      $this->targetCampaign->create($campaign);
    }
  }

}
