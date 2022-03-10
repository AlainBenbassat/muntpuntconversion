<?php

class SourceGroupFetcher {

  public function getGroupsToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        *
      FROM
        civicrm_group
      where
        id in (" . SourceContactValidator::GROUPS_TO_MIGRATE . ")
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getGroupContacts($groupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        *
      FROM
        civicrm_group_contact
      where
        group_id = $groupId
      and
        status = 'Added'
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

}


