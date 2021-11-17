<?php

class TargetContactFinder {
  public static function getContactIdByOldContactId($contactId) {
    $result = civicrm_api3('Contact', 'findbyidentity', [
      'sequential' => 1,
      'identifier_type' => CRM_Muntpuntconfig_Config::ICONTACT_ID_TYPE,
      'identifier' => $contactId,
    ]);

    if ($result['is_error'] == 0 && $result['count'] > 0) {
      return $result['values'][0]['id'];
    }
    else {
      throw new Exception("Could not find contact with old civi id = $contactId");
    }
  }
}
