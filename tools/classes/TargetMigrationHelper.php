<?php

class TargetMigrationHelper {
  private $alternateIds = [];

  public function __construct() {
    // array to depude loc blocks (i.e. event locations)
    $this->alternateIds['civicrm_loc_block'][465] = 113;
    $this->alternateIds['civicrm_loc_block'][405] = 287; // Use-it
    $this->alternateIds['civicrm_loc_block'][551] = 287;
    $this->alternateIds['civicrm_loc_block'][570] = 287;
    $this->alternateIds['civicrm_loc_block'][427] = 5;
    $this->alternateIds['civicrm_loc_block'][443] = 73; // Stripmuseum Brussel
  }

  public static function clearMappingOldIdNewId($entity) {
    if ($entity == 'all') {
      self::recreateMigrationIdsTable();
    }
    else {
      self::clearMappingOldIdNewIdForEntity($entity);
    }
  }

  public function clearHiddenCustomFieldsIds() {
    CRM_Core_DAO::executeQuery("update civicrm_custom_field set help_post = NULL");
  }

  private static function recreateMigrationIdsTable() {
    CRM_Core_DAO::executeQuery("drop table if exists migration_ids");
    CRM_Core_DAO::executeQuery("
      create table migration_ids (
         entity varchar(80),
         old_id int(10),
         new_id int(10),
         index migridx (entity, old_id, new_id)
      )
    ");
  }

  private static function clearMappingOldIdNewIdForEntity($entity) {
    CRM_Core_DAO::executeQuery("delete from migration_ids where entity = '$entity'");
  }

  public function storeIds($entity, $oldId, $newId) {
    CRM_Core_DAO::executeQuery("insert into migration_ids (entity, old_id, new_id) values ('$entity', $oldId, $newId)");
  }

  public function getNewId($entity, $oldId) {
    $altOldId = $this->checkForAlternateId($entity, $oldId);

    return CRM_Core_DAO::singleValueQuery("select new_id from migration_ids where entity = '$entity' and old_id = $altOldId");
  }

  private function checkForAlternateId($entity, $oldId) {
    if (empty($this->alternateIds[$entity][$oldId])) {
      return $oldId;
    }
    else {
      return $this->alternateIds[$entity][$oldId];
    }
  }

  public function insertIntoTable($tableName, $columnSpecs, $sourceValues) {
    $i = 0;
    $sqlParams = [];
    $columnList = [];
    $valueList = [];

    foreach ($columnSpecs as $columnName => $columnDataType) {
      if (!empty($sourceValues[$columnName])) {
        $i++;
        $columnList[] = $columnName;
        $valueList[] = "%$i";
        $sqlParams[$i] = [$sourceValues[$columnName], $columnDataType];
      }
    }

    $sql = "insert into $tableName (" . implode(',', $columnList) . ') values (' . implode(',', $valueList) . ')';
    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }
}
