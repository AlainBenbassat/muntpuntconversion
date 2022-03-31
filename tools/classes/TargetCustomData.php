<?php

class TargetCustomData {
  private $idMapper = [];

  public function create($entityId, $customDataSet) {
    foreach ($customDataSet as $oldCustomFieldId => $customValue) {
      if ($customValue) {
        $customField = 'custom_' . $this->getCustomFieldIdFromOldId($oldCustomFieldId);
        civicrm_api3('CustomValue', 'create', [
          'entity_id' => $entityId,
          $customField => $customValue,
        ]);
      }
    }
  }

  private function getCustomFieldIdFromOldId($oldCustomFieldId) {
    if (empty($this->idMapper[$oldCustomFieldId])) {
      $sql = "select id from civicrm_custom_field where help_post = '$oldCustomFieldId'";
      $field = CRM_Core_DAO::singleValueQuery($sql);
      if (!$field) {
        throw new Exception("Cannot map old custom field id = $oldCustomFieldId to new value");
      }
      $this->idMapper[$oldCustomFieldId] = $field;
    }

    return $this->idMapper[$oldCustomFieldId];
  }


}
