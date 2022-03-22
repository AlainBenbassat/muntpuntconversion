<?php

class TargetProfile {
  private $targetMigrationHelper;

  public function __construct() {
    $this->targetMigrationHelper = new TargetMigrationHelper();
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
      'created_id' => $profile['created_id'],
      'created_date' => $profile['created_date'],
      'is_proximity_search' => $profile['is_proximity_search'],
    ]);

    $oldProfileId = $profile['id'];
    $newProfileId = $result['id'];

    $this->targetMigrationHelper->storeIds('civicrm_uf_group', $oldProfileId, $newProfileId);

    return $newProfileId;
  }

  public function createField($newProfileId, $field) {
    $result = civicrm_api3('UfField', 'create', [
      'uf_group_id' => $newProfileId,
      'field_name' => $field['field_name'],
      'is_active' => $field['is_active'],
      'is_view' => $field['is_view'],
      'is_required' => $field['is_required'],
      'weight' => $field['weight'],
      'help_post' => $field['help_post'],
      'help_pre' => $field['help_pre'],
      'visibility' => $field['visibility'],
      'in_selector' => $field['in_selector'],
      'is_searchable' => $field['is_searchable'],
      'location_type_id' => $field['location_type_id'],
      'phone_type_id' => $field['phone_type_id'],
      'website_type_id' => $field['website_type_id'],
      'label' => $field['label'],
      'field_type' => $field['field_type'],
      'is_reserved' => $field['is_reserved'],
      'is_multi_summary' => $field['is_multi_summary'],
    ]);
  }

  public function createEventProfile($newEventId, $profile) {
    if ($profile['id'] == 12) {
      $newProfileId = 12;
    }
    else {
      $newProfileId = $this->targetMigrationHelper->getNewId('civicrm_uf_group', $profile['id']);
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
