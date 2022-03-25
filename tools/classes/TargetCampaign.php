<?php

class TargetCampaign {
  private $targetMigrationHelper;

  public function __construct() {
    $this->targetMigrationHelper = new TargetMigrationHelper();
  }

  public function create($campaign) {
    $result = civicrm_api3('Campaign', 'create', [

    ]);

    $oldCampaignId = $campaign['id'];
    $newCampaignId = $result['id'];

    $this->targetMigrationHelper->storeIds('civicrm_uf_group', $oldCampaignId, $newCampaignId);

    return $newCampaignId;
  }

}
