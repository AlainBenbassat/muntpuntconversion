<?php

class TargetAddress {
  const DEFAULT_FIELDS_ADDRESS = ['is_primary', 'street_address', 'postal_code', 'city', 'country_id', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3'];
  const LOCATION_TYPE_MAIN = 3;
  const LOCATION_TYPE_PRIVE = 1;
  const LOCATION_TYPE_REDACTIE = 4;

  const ICONTACT_LT_STANDAARD = 1;
  const ICONTACT_LT_PRIVE = 4;
  const ICONTACT_LT_REDACTIE = 6;

  public function create($newContactId, $sourceAddress) {
    $params = $this->convertOldParamsToNewParams($newContactId, $sourceAddress);
    civicrm_api3('Address', 'create', $params);
  }

  public function createOther($newContactId, $otherAddress) {
    $params = $this->convertOldParamsToNewParams($newContactId, $otherAddress);
    civicrm_api3('Address', 'create', $params);
  }

  public function get($contactId) {
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

  public function update($params) {
    $result = civicrm_api3('Address', 'create', $params);
    return $result;
  }

  public function delete($mainContactId) {
    $sql = "delete from civicrm_address where contact_id = $mainContactId";
    CRM_Core_DAO::executeQuery($sql);
  }

  public function merge($mainContactId, $duplicateAddress) {
    $mainAddress = $this->get($mainContactId);

    if ($mainAddress === FALSE) {
      $this->create($mainContactId, $duplicateAddress);
    }
    elseif ($this->isBetterAddress($mainAddress, $duplicateAddress)) {
      $this->delete($mainContactId);
      $this->create($mainContactId, $duplicateAddress);
    }
  }

  private function convertOldParamsToNewParams($newContactId, $address) {
    $params = [
      'sequential' => 1,
      'contact_id' => $newContactId,
    ];

    $this->copyParams($address, $params, self::DEFAULT_FIELDS_ADDRESS);
    $this->addLocationTypeParam($address['location_type_id'], $params);

    return $params;
  }

  private function isBetterAddress($mainAddress, $duplicateAddress) {
    if (empty($mainAddress['street_address']) && !empty($duplicateAddress['street_address'])) {
      return TRUE;
    }

    if (empty($mainAddress['city']) && !empty($duplicateAddress['city'])) {
      return TRUE;
    }

    if (empty($mainAddress['postal_code']) && !empty($duplicateAddress['postal_code'])) {
      return TRUE;
    }

    return FALSE;
  }

  private function copyParams($fromParams, &$toParams, $fields) {
    foreach ($fields as $field) {
      $toParams[$field] = $fromParams[$field];
    }
  }

  private function addLocationTypeParam($origLocationTypeId, &$params) {
    if ($origLocationTypeId == self::ICONTACT_LT_STANDAARD) {
      $params['location_type_id'] = self::LOCATION_TYPE_MAIN;
    }
    elseif ($origLocationTypeId == self::ICONTACT_LT_PRIVE) {
      $params['location_type_id'] = self::LOCATION_TYPE_PRIVE;
    }
    elseif ($origLocationTypeId == self::ICONTACT_LT_REDACTIE) {
      $params['location_type_id'] = self::LOCATION_TYPE_REDACTIE;
    }
    else {
      $params['location_type_id'] = self::LOCATION_TYPE_MAIN;
    }
  }
}
