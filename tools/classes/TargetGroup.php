<?php

class TargetGroup {
  public function create($group) {
    $this->createWithOriginalId($group);
    $this->addAdditionalInfo($group);
  }

  public function createGroupContact($groupId, $groupContactId) {
    $newContactId = TargetContactFinder::getContactIdByOldContactId($groupContactId);
    if ($newContactId) {
      civicrm_api3('GroupContact', 'create', [
        'group_id' => $groupId,
        'contact_id' => $newContactId,
        'status' => 'Added',
      ]);
    }
  }

  private function createWithOriginalId($group) {
    $sql = "insert into civicrm_group (id, name, title) values (%1, %2, %3)";
    $sqlParams = [
      1 => [$group['id'], 'Integer'],
      2 => [$group['name'], 'String'],
      3 => [$group['title'], 'String'],
    ];
    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function addAdditionalInfo($group) {
    $result = civicrm_api3('Group', 'create', [
      'id' => $group['id'],
      'description' => $group['description'],
      'is_active' => $group['is_active'],
      'group_type' => [2],
    ]);
  }

  private function createWithAPI($group) {
    // OBSOLETE
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
    // OBSOLETE
    if ($oldId != $newId) {
      $sql = "update civicrm_group set id = $oldId where id = $newId";
      CRM_Core_DAO::executeQuery($sql);
    }
  }
}
