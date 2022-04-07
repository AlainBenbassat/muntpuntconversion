<?php

class SourceCustomDataFetcher {
  private $allCustomGroupsToMigrate;
  private $tableNames;
  private $customDataDefinition = [];

  public function __construct() {
    $this->allCustomGroupsToMigrate =
      $this->getCustomGroupsForContacts() +
      $this->getCustomGroupsForEvents() +
      $this->getCustomGroupsForParticipants();
  }

  public function getCustomGroupsForContacts() {
    return [
      4 => 'Diensten en Producten',
      12 => 'Media info',
      21 => 'BTW Info',
    ];
  }

  public function getCustomGroupsForEvents() {
    return [
      109 => 'Extra Evenement info',
      115 => 'Evenement planning, memo, overleg en statistiek',
      117 => 'Bios',
    ];
  }

  public function getCustomGroupsForParticipants() {
    return [
      175 => 'Bijkomende informatie',
      177 => 'Netflix',
    ];
  }

  public function getCustomGroupsToMigrate() {
    return $this->allCustomGroupsToMigrate;
  }

  public function getCustomGroupDetails($customGroupId) {
    $pdo = SourceDB::getPDO();
    $sql = "
    select
      *
    from
      civicrm_custom_group
    where
      id = $customGroupId
    ";
    $dao = $pdo->query($sql);

    return $dao->fetch();
  }

  public function getOptionGroupsFromCustomGroups() {
    $customGroupIds = implode(', ', array_keys($this->allCustomGroupsToMigrate));
    $pdo = SourceDB::getPDO();
    $sql = "
    select
      og.id,
      og.title
    from
      civicrm_option_group og
    where
      og.id in (
        select
          cf.option_group_id
        from
          civicrm_custom_field cf
        where
          cf.custom_group_id in ($customGroupIds)
        and
          cf.is_active = 1
        and
          cf.option_group_id is not null
      )
    ";
    $dao = $pdo->query($sql);

    $optionGroups = [];
    while ($optionGroup = $dao->fetch()) {
      $optionGroups[$optionGroup['id']] = $optionGroup['title'];
    }

    return $optionGroups;
  }

  /**
   * @param $entityId
   * @param $customGroupId
   *
   * @return array with customfieldID => customfieldValue
   */
  public function getCustomDataSetOfEntity($entityId, $customGroupId) {
    $customDataSet = [];

    $this->loadCustomGroupDefinition($customGroupId);

    $data = $this->getCustomData($entityId, $this->customDataDefinition[$customGroupId]['table_name']);

    foreach ($this->customDataDefinition[$customGroupId]['fields'] as $fieldId => $fieldName) {
      $customDataSet[$fieldId] = $data ? $data[$fieldName] : '';
    }

    return $customDataSet;
  }

  private function loadCustomGroupDefinition($customGroupId) {
    if (empty($this->customDataDefinition[$customGroupId])) {
      $this->customDataDefinition[$customGroupId]['table_name'] = $this->getTableNameFromCustomGroupId($customGroupId);

      $dao = $this->getColumnNamesFromCustomGroup($customGroupId);
      while ($field = $dao->fetch()) {
        $this->customDataDefinition[$customGroupId]['fields'][$field['id']] = $field['column_name'];
      }
    }
  }

  private function getCustomData($contactId, $tableName) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        $tableName
      where
        entity_id = $contactId
    ";

    $dao = $pdo->query($sql);
    $customData = $dao->fetch();

    return $customData;
  }

  private function getTableNameFromCustomGroupId($customGroupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        table_name
      from
        civicrm_custom_group
      where
        id = $customGroupId
    ";

    $dao = $pdo->query($sql);
    $customGroup = $dao->fetch();

    return $customGroup['table_name'];
  }

  private function getColumnNamesFromCustomGroup($customGroupId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        id,
        column_name
      from
        civicrm_custom_field
      where
        custom_group_id = $customGroupId
      and
        is_active = 1
      order by
        id
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }
}
