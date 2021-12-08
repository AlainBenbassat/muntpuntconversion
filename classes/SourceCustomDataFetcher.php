<?php

class SourceCustomDataFetcher {
  public function getCustomDataGroup($customGroupName) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_custom_group
      where
        name = '$customGroupName'
    ";

    $dao = $pdo->query($sql);

    return $dao->fetch();
  }

  public function getCustomGroupOptionGroups($customGroupName) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        distinct cf.option_group_id
      from
        civicrm_custom_group cg
      inner join
        civicrm_custom_field cf on cf.custom_group_id = cg.id
      where
        name = '$customGroupName'
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getOptionGroupDetails($optionGroupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        id,
        name,
        title
      from
        civicrm_option_group
      where
        id = $optionGroupId
    ";

    $dao = $pdo->query($sql);

    $dao->fetch();

    return [$dao['id'], $dao['name'], $dao['title']];

  }
}
