<?php

class TargetPhone {
  public function create($newContactId, $sourcePhones) {
    foreach ($sourcePhones as $sourcePhone) {
      $sourcePhone['contact_id'] = $newContactId;
      civicrm_api3('Phone', 'create', $sourcePhone);
    }
  }
}
