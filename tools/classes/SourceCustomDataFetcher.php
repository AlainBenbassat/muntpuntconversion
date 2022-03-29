<?php

class SourceCustomDataFetcher {
  private $allCustomGroupsToMigrate;
  private $tableNames;

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
      17 => 'Muntpunt Medewerker type',
      19 => 'Ouder/Voogd Contact',
      21 => 'BTW Info',
      25 => 'Overheid Organisatie Type',
      27 => 'Leverancier Organisatie Type',
      29 => 'Partner Organisatie Type',
      31 => 'Overheid Medewerker',
      37 => 'Leverancier Medewerker Profiel',
      45 => 'Departement',
      47 => 'Abonnementen',
      71 => 'Muntpunt Vrijwilliger Type',
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

  public function getCustomDataOfContact($contactId, $customGroupId) {
    /*
     moet een array teruggeven met
      customfieldID => customfieldwaarde

     in het targetcustomdata object, zet ik die field id om naar de nieuwe (oude zit in help_post)
     */
    $tableName = $this->getTableNameFromCustomGroupId($customGroupId);
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

    return $dao->fetch();
  }

  private function getTableNameFromCustomGroupId($customGroupId) {
    if (empty($this->tableNames)) {
      $this->fillTableNames();
    }

    return $this->tableNames[$customGroupId];
  }

  private function fillTableNames() {
    $customGroupIds = implode(', ', array_keys($this->allCustomGroupsToMigrate));

    $pdo = SourceDB::getPDO();

    $sql = "
      select
        id, table_name
      from
        civicrm_custom_group
      where
        id in ($customGroupIds)
    ";

    $dao = $pdo->query($sql);

    while ($customGroup = $dao->fetch()) {
      $this->tableNames[$dao['id']] = $dao['table_name'];
    }
  }
}
