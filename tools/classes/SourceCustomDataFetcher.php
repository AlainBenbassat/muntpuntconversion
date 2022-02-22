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
        cg.name = '$customGroupName'
      and
        cf.option_group_id is not null
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

    $row = $dao->fetch();

    return [$row['id'], $row['name'], $row['title']];
  }

  public function getOptionValues($optionGroupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_option_value
      where
        option_group_id = $optionGroupId
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getCustomFields($customGroupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_custom_field
      where
        custom_group_id = $customGroupId
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }
}
