<?php

class TargetContact {

  public function create($contact) {
    $params = $this->convertOldContactParamsToNewContactParams($contact);
    $newContact = civicrm_api3('Contact', 'create', $params);

    return $newContact['id'];
  }

  private function convertOldContactParamsToNewContactParams($contact) {
    $params = [
      'sequential' => 1
    ];

    // common fields
    $this->copyParams($contact, $params, ['contact_type', 'source']);

    // specific fields for individual and org
    if ($contact['contact_type'] == 'Individual') {
      $this->copyParams($contact, $params, ['first_name', 'last_name', 'job_title', 'birth_date']);
    }
    else {
      $this->copyParams($contact, $params, ['organization_name']);
    }

    return $params;
  }

  private function copyParams($fromContact, &$toParams, $fields) {
    foreach ($fields as $field) {
      $toParams[$field] = $fromContact[$field];
    }
  }

  public function addOldCiviCRMId($oldId, $newId) {
    civicrm_api3('Contact', 'addidentity', [
      'contact_id' => $newId,
      'identifier_type' => CRM_Muntpuntconfig_Config::ICONTACT_ID_TYPE,
      'identifier' => $oldId,
    ]);
  }
}
