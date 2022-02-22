<?php

class TargetPhone {
  public function create($newContactId, $sourcePhones) {
    foreach ($sourcePhones as $sourcePhone) {
      $sourcePhone['contact_id'] = $newContactId;
      civicrm_api3('Phone', 'create', $sourcePhone);
    }
  }

  public function merge($mainContactId, $duplicatePhones) {
    foreach ($duplicatePhones as $duplicatePhone) {
      if (!$this->hasNumericPhoneNumber($mainContactId, $duplicatePhone['phone_numeric'])) {
        $duplicatePhone['contact_id'] = $mainContactId;
        civicrm_api3('Phone', 'create', $duplicatePhone);
      }
    }
  }

  private function hasNumericPhoneNumber($mainContactId, $phoneNumeric) {
    $sql = "select id from civicrm_phone where contact_id = $mainContactId and phone_numeric = '$phoneNumeric'";
    if (CRM_Core_DAO::singleValueQuery($sql)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
