<?php

class TargetCampaign {
  private $targetMigrationHelper;
  private $campaignTypeOptionGroupId;

  public function __construct() {
    $this->targetMigrationHelper = new TargetMigrationHelper();
    $this->campaignTypeOptionGroupId = civicrm_api3('OptionGroup', 'getsingle', ['name' => 'campaign_type'])['id'];
  }

  public function create($campaign) {
    // we want to keep the original id
    $columnSpecs = [
      'id' => 'Integer',
      'name' => 'String',
      'title' => 'String',
      'description' => 'String',
      'start_date' => 'String',
      'end_date' => 'String',
      'campaign_type_id' => 'Integer',
      'status_id' => 'Integer',
      'is_active' => 'Integer',
    ];
    $this->targetMigrationHelper->insertIntoTable('civicrm_campaign', $columnSpecs, $campaign);
  }

  public function createCampaignType($campaignType) {
    unset($campaignType['id']);
    $campaignType['option_group_id'] = $this->campaignTypeOptionGroupId;
    civicrm_api3('OptionValue', 'create', $campaignType);
  }

  public function disableCampaignTypes() {
    $sql = "update civicrm_option_value set is_active = 0 where option_group_id = " . $this->campaignTypeOptionGroupId;
    CRM_Core_DAO::executeQuery($sql);
  }

}
