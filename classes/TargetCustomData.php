<?php

class TargetCustomData {
  public function createCustomGroup($sourceDAO) {
    $cols = $this->getCustomGroupTableSpecs();
    $this->createInsertStatement('civicrm_custom_group', $cols, $sourceDAO);
  }

  private function createInsertStatement($tableName, $columnSpecs, $sourceValues) {
    $i = 0;
    $sqlParams = [];
    $columnList = [];
    $valueList = [];

    foreach ($columnSpecs as $columnName => $columnDataType) {
      if (!empty($sourceValues[$columnName])) {
        $i++;
        $columnList[] = $columnName;
        $valueList[] = "%$i";
        $sqlParams[$i] = [$columnName, $columnDataType];
      }

      $sql = "insert into $tableName (" . implode(',', $columnList) . ') values (' . implode(',', $valueList) . ')';
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }

  }

  public function createOptionGroup($id, $name, $title) {
    $sql = "insert into civicrm_option_group (id, name, title) values (%!, %2, %3)";
    $sqlParams = [
      1 => [$id, 'Integer'],
      2 => [$name, 'String'],
      3 => [$name, 'String'],
    ];

    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function getCustomGroupTableSpecs() {
    $cols = [
      'id' => 'Integer',
      'name' => 'String',
      'title' => 'String',
      'extends' => 'String',
      'extends_entity_column_id' => 'Integer',
      'extends_entity_column_value' => 'String',
      'style' => 'String',
      'collapse_display' => 'Integer',
      'help_pre' => 'String',
      'help_post' => 'String',
      'weight' => 'Integer',
      'is_active' => 'Integer',
      'table_name' => 'String',
      'is_multiple' => 'Integer',
      'min_multiple' => 'Integer',
      'max_multiple' => 'Integer',
      'collapse_adv_display' => 'Integer',
      'is_reserved' => 'Integer',
    ];
  }
}
