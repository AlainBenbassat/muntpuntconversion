<?php

class TargetScheduledReminder {
  public function create($newEventId, $sourceSchedRem) {
    $params = $this->convertOldParamsToNewParams($newEventId, $sourceSchedRem);
    civicrm_api3('ActionSchedule', 'create', $params);
  }

  public function convertOldParamsToNewParams($newEventId, $sourceSchedRem) {
    $fields = [
      'name',
      'title',
      'recipient',
      'limit_to',
      'entity_status',
      'start_action_offset',
      'start_action_unit',
      'start_action_condition',
      'start_action_date',
      'is_repeat',
      'repetition_frequency_unit',
      'repetition_frequency_interval',
      'end_frequency_unit',
      'end_frequency_interval',
      'end_action',
      'end_date',
      'is_active',
      'recipient_manual',
      'recipient_listing',
      'body_text',
      'body_html',
      'subject',
      'record_activity',
      'mapping_id',
      'group_id',
      'msg_template_id',
      'absolute_date',
      'from_name',
      'from_email',
      'mode',
      'used_for',
      'filter_contact_language',
      'communication_language',
      'created_date',
      'modified_date',
      'effective_start_date',
      'effective_end_date'
    ];

    $params = [
      'sequential' => 1,
      'entity_value' => $newEventId,
    ];

    foreach ($fields as $field) {
      $params[$field] = $sourceSchedRem[$field];
    }

    return $params;
  }
}
