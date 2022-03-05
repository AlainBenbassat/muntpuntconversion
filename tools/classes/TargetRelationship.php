<?php

class TargetRelationship {
  public function createRelationship($employeeRelationship) {
    $newContactIdA = TargetContactFinder::getContactIdByOldContactId($employeeRelationship['contact_id_a']);
    $newContactIdB = TargetContactFinder::getContactIdByOldContactId($employeeRelationship['contact_id_a']);
    if ($newContactIdA && $newContactIdB) {
      if (!$this->hasRelationship($newContactIdA, $newContactIdB, 4)) {
        $params = $this->convertOldParamsToNewParams($employeeRelationship, $newContactIdA, $newContactIdB);
        civicrm_api3('Relationship', 'create', $params);
      }
    }
  }

  private function convertOldParamsToNewParams($employeeRelationship, $newContactIdA, $newContactIdB) {
    $params = [
      'contact_id_a' => $newContactIdA,
      'contact_id_b' => $newContactIdB,
      'relationship_type_id' => $employeeRelationship['relationship_type_id'],
      'is_active' => $employeeRelationship['is_active'],
    ];

    $this->addParamIfExists($employeeRelationship, 'start_date', $params);
    $this->addParamIfExists($employeeRelationship, 'end_date', $params);

    return $params;
  }

  private function addParamIfExists($employeeRelationship, $element, &$params) {
    if (!empty($employeeRelationship[$element])) {
      $params[$element] = $employeeRelationship[$element];
    }
  }

  private function hasRelationship($newContactIdA, $newContactIdB, $relTypeId) {
    $id = CRM_Core_DAO::singleValueQuery("select max(id) from civicrm_relationship where relationship_type_id = $relTypeId and contact_id_a = $newContactIdA and contact_id_b = $newContactIdB");
    if ($id) {
      return True;
    }
    else {
      return FALSE;
    }
  }

}
