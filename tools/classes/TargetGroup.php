<?php

class TargetGroup {
  public function create($group) {
    $newId = $this->createWithAPI($group);
    $this->keepOriginalGroupId($group['id'], $newId);
  }

  public function createGroupContact($groupId, $groupContactId) {

  }

  private function createWithAPI($group) {
    $result = civicrm_api3('Group', 'create', [
      'name' => $group['name'],
      'title' => $group['title'],
      'description' => $group['description'],
      'is_active' => $group['is_active'],
      'group_type' => [2],
    ]);

    return $result['id'];
  }

  private function keepOriginalGroupId($oldId, $newId) {
    $sql = "update civicrm_group set id = $oldId where id = $newId";
    CRM_Core_DAO::executeQuery($sql);
  }
}
