<?php

class TargetCustomData {
  private $oldCustomFieldIdIsNewCustomFieldId = [];
  private $cacheIsContactReferenceField = [];

  public function create($entityId, $customDataSet) {
    foreach ($customDataSet as $oldCustomFieldId => $customValue) {
      if ($customValue) {
        $newCustomFieldId = $this->getCustomFieldIdFromOldId($oldCustomFieldId);
        $customField = "custom_$newCustomFieldId";

        if ($this->isContactReferenceField($newCustomFieldId)) {
          $customValue = TargetContactFinder::getContactIdByOldContactId($customValue);
        }

        civicrm_api3('CustomValue', 'create', [
          'entity_id' => $entityId,
          $customField => $customValue,
        ]);
      }
    }
  }

  private function isContactReferenceField($newCustomFieldId) {
    if (!array_key_exists($newCustomFieldId, $this->cacheIsContactReferenceField)) {
      $sql = "select data_type from civicrm_custom_field where id = $newCustomFieldId";
      $dataType = CRM_Core_DAO::singleValueQuery($sql);
      if ($dataType == 'ContactReference') {
        $this->cacheIsContactReferenceField[$newCustomFieldId] = TRUE;
      }
      else {
        $this->cacheIsContactReferenceField[$newCustomFieldId] = FALSE;
      }
    }

    return $this->cacheIsContactReferenceField[$newCustomFieldId];
  }

  public function getCustomFieldIdFromOldId($oldCustomFieldId) {
    if (empty($this->oldCustomFieldIdIsNewCustomFieldId[$oldCustomFieldId])) {
      $sql = "select id from civicrm_custom_field where help_post = '$oldCustomFieldId'";
      $field = CRM_Core_DAO::singleValueQuery($sql);
      if (!$field) {
        throw new Exception("Cannot map old custom field id = $oldCustomFieldId to new value");
      }
      $this->oldCustomFieldIdIsNewCustomFieldId[$oldCustomFieldId] = $field;
    }

    return $this->oldCustomFieldIdIsNewCustomFieldId[$oldCustomFieldId];
  }

  public function isCustomFieldName($fieldName) {
    if (strpos($fieldName, 'custom_') === FALSE) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  public function extractCustomFieldIdFromName($fieldName) {
    return str_replace('custom_', '', $fieldName);
  }


}
