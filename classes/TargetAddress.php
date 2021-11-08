<?php

class TargetAddress {
  const LOCATION_TYPE_MAIN = 3;

  public function create($newContactId, $sourceAddress) {
    civicrm_api3('Address', 'create', [
      'sequential' => 1,
      'contact_id' => $newContactId,
      'street_address' => $sourceAddress['street_address'],
      'postal_code' => $sourceAddress['postal_code'],
      'city' => $sourceAddress['city'],
      'country_id' => $sourceAddress['country_id'],
      'supplemental_address_1' => $sourceAddress['supplemental_address_1'],
      'supplemental_address_2' => $sourceAddress['supplemental_address_2'],
      'supplemental_address_3' => $sourceAddress['supplemental_address_3'],
      'is_primary' => 1,
      'location_type_id' => self::LOCATION_TYPE_MAIN,
    ]);
  }

  private function get($contactId) {
    $result = civicrm_api3('Address', 'get', [
      'sequential' => 1,
      'contact_id' => $contactId,
      'is_primary' => 1,
    ]);

    if ($result['count'] > 0) {
      return $result['values'][0];
    }

    return FALSE;
  }
}
