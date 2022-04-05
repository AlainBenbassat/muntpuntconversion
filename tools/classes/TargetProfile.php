<?php

class TargetProfile {
  private $targetMigrationHelper;
  private $targetCustomData;

  public function __construct() {
    $this->targetMigrationHelper = new TargetMigrationHelper();
    $this->targetCustomData = new TargetCustomData();
  }

  public function create($profile) {
    $result = civicrm_api3('UfGroup', 'create', [
      'is_active' => $profile['is_active'],
      'group_type' => $profile['group_type'],
      'title' => $profile['title'],
      'description' => $profile['description'],
      'help_pre' => $profile['help_pre'],
      'help_post' => $profile['help_post'],
      'limit_listings_group_id' => $profile['limit_listings_group_id'],
      'post_URL' => $profile['post_URL'],
      'add_to_group_id' => $profile['add_to_group_id'],
      'add_captcha' => $profile['add_captcha'],
      'is_map' => $profile['is_map'],
      'is_edit_link' => $profile['is_edit_link'],
      'is_uf_link' => $profile['is_uf_link'],
      'is_update_dupe' => $profile['is_update_dupe'],
      'cancel_URL' => $profile['cancel_URL'],
      'is_cms_user' => $profile['is_cms_user'],
      'notify' => $profile['notify'],
      'is_reserved' => $profile['is_reserved'],
      'name' => $profile['name'],
      'is_proximity_search' => $profile['is_proximity_search'],
    ]);

    $oldProfileId = $profile['id'];
    $newProfileId = $result['id'];

    $this->targetMigrationHelper->storeIds('civicrm_uf_group', $oldProfileId, $newProfileId);

    return $newProfileId;
  }

  public function createField($newProfileId, $field) {
    if ($this->targetCustomData->isCustomFieldName($field['field_name'])) {
      $oldCustomFieldId = $this->targetCustomData->extractCustomFieldIdFromName($field['field_name']);
      $newCustomFieldId = $this->targetCustomData->getCustomFieldIdFromOldId($oldCustomFieldId);
      $field['field_name'] = "custom_$newCustomFieldId";
    }

    // api3 and api4 give error!
    $columnSpecs = [
      'uf_group_id' => 'Integer',
      'field_name' => 'String',
      'is_active' => 'Integer',
      'is_view' => 'Integer',
      'is_required' => 'Integer',
      'weight' => 'Integer',
      'help_post' => 'String',
      'help_pre' => 'String',
      'visibility' => 'String',
      'in_selector' => 'Integer',
      'is_searchable' => 'Integer',
      'location_type_id' => 'Integer',
      'phone_type_id' => 'Integer',
      'website_type_id' => 'Integer',
      'label' => 'String',
      'field_type' => 'String',
      'is_reserved' => 'Integer',
      'is_multi_summary' => 'Integer',
    ];

    $field['uf_group_id'] = $newProfileId;
    $this->targetMigrationHelper->insertIntoTable('civicrm_uf_field', $columnSpecs, $field);
  }

  public function createEventProfile($newEventId, $profile) {
    $oldProfileId = $profile['uf_group_id'];
    if ($oldProfileId == 12) {
      $newProfileId = 12;
    }
    else {
      $newProfileId = $this->targetMigrationHelper->getNewId('civicrm_uf_group', $oldProfileId);
    }

    if ($newProfileId) {
      civicrm_api3('UfJoin', 'create', [
        'is_active' => $profile['is_active'],
        'module' => $profile['module'],
        'entity_table' => 'civicrm_event',
        'entity_id' => $newEventId,
        'weight' => $profile['weight'],
        'uf_group_id' => $newProfileId,
        'module_data' => $profile['module_data'],
      ]);
    }
  }
}
