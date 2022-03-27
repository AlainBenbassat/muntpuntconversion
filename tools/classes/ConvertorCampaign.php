<?php

class ConvertorCampaign {
  private $campaignFetcher;
  private $targetCampaign;

  public function __construct() {
    $this->campaignFetcher = new SourceCampaignFetcher();
    $this->targetCampaign = new TargetCampaign();
  }

  public function run() {
    $this->migrateCampaignTypes();
    $this->migrateCampaigns();
  }

  private function migrateCampaignTypes() {
    $dao = $this->campaignFetcher->getCampaignTypesToMigrate();
  }

  private function migrateCampaigns() {

  }

}
