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

  public static function initialize() {
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
}
