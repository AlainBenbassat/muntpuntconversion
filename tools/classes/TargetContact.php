<?php

class TargetContact {
  const DEFAULT_FIELDS_INDIVIDUAL = ['contact_type', 'source', 'first_name', 'last_name', 'job_title', 'birth_date'];
  const DEFAULT_FIELDS_ORGANIZATION = ['contact_type', 'source', 'organization_name'];

  public function create($contact) {
    $params = $this->convertOldParamsToNewParams($contact);
    $newContact = civicrm_api3('Contact', 'create', $params);

    return $newContact['id'];
  }

  public function merge($mainContactId, $duplicateContact) {
    $mainContact = $this->get($mainContactId);

    $params = $this->fillInTheBlanks($mainContact, $duplicateContact);
    if ($params !== FALSE) {
      $this->update($params);
    }
  }

  public function get($id) {
    $result = civicrm_api3('Contact', 'getsingle', [
      'sequential' => 1,
      'id' => $id,
    ]);

    return $result;
  }

  public function update($params) {
    $result = civicrm_api3('Contact', 'create', $params);
    return $result;
  }

  private function fillInTheBlanks($mainContact, $duplicateContact) {
    if ($mainContact['contact_type'] == 'Individual') {
      $fieldsTocheck = ['first_name', 'last_name', 'job_title', 'birth_date'];
    }
    else {
      $fieldsTocheck = ['organization_name'];
    }

    $params = [];
    $this->copyMissingParams($mainContact, $duplicateContact, $params, $fieldsTocheck);

    if (empty($params)) {
      return FALSE;
    }
    else {
      $params['id'] = $mainContact['id'];
      $params['sequential'] = 1;
      return $params;
    }
  }

  private function convertOldParamsToNewParams($contact) {
    $params = [
      'sequential' => 1
    ];

    // specific fields for individual and org
    if ($contact['contact_type'] == 'Individual') {
      $this->copyParams($contact, $params, self::DEFAULT_FIELDS_INDIVIDUAL);
      $this->handleMissingName($contact, $params);
      $this->truncateLongValues($contact, $params);
    }
    else {
      $this->copyParams($contact, $params, self::DEFAULT_FIELDS_ORGANIZATION);
    }

    $this->copySubType($contact, $params);

    return $params;
  }

  private function copySubType($contact, &$params) {
    if (!empty($contact['contact_sub_type'])) {
      if (in_array('Partner', $contact['contact_sub_type'])) {
        $params['contact_sub_type'] = ['Perspartner'];
      }
      elseif (in_array('Pers_Medewerker', $contact['contact_sub_type'])) {
        $params['contact_sub_type'] = ['Persmedewerker'];
      }
    }
  }

  private function copyParams($fromParams, &$toParams, $fields) {
    foreach ($fields as $field) {
      $toParams[$field] = $fromParams[$field];
    }
  }

  private function copyMissingParams($mainParams, $additionalParams, &$params, $fields) {
    foreach ($fields as $field) {
      if (empty($mainParams[$field]) && !empty($additionalParams[$field])) {
        $params[$field] = $additionalParams[$field];
      }
    }
  }

  private function handleMissingName($contact, &$params) {
    if (empty($params['first_name']) && empty($params['last_name'])) {
      $params['first_name'] = $contact['display_name'];
    }
  }

  private function truncateLongValues($contact, &$params) {
    $params['first_name'] = mb_substr(trim($params['first_name']), 0, 60);
    $params['last_name'] = mb_substr(trim($params['last_name']), 0, 60);
  }

  public function addOldCiviCRMId($oldId, $newId) {
    civicrm_api3('Contact', 'addidentity', [
      'contact_id' => $newId,
      'identifier_type' => CRM_Muntpuntconfig_Config::ICONTACT_ID_TYPE,
      'identifier' => $oldId,
    ]);
  }


}
