<?php

class TargetEmail {
  const LOCATION_TYPE_MAIN = 3;

  public function create($newContactId, $email) {
    civicrm_api3('Email', 'create', [
      'sequential' => 1,
      'contact_id' => $newContactId,
      'email' => $email,
      'is_primary' => 1,
      'location_type_id' => self::LOCATION_TYPE_MAIN,
    ]);
  }
}
