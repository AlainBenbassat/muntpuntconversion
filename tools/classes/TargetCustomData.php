<?php

class TargetCustomData {
  public function __construct() {
    die("TargetCustomData IS OBSOLETE!!!\nCustom data and option groups should be created with muntpunt config items\n");
  }

  public function createCustomGroup($sourceDAO) {
    if (!$this->existsCustomGroup($sourceDAO['name'])) {
      $cols = $this->getCustomGroupTableSpecs();
      $this->insertIntoTable('civicrm_custom_group', $cols, $sourceDAO);
    }
  }

  public function createCustomField($customField) {
    if (!$this->existsCustomField($customField['id'])) {
      $cols = $this->getCustomFieldTableSpecs();
      $this->insertIntoTable('civicrm_custom_field', $cols, $customField);
    }
  }

  public function createOptionGroup($id, $name, $title) {
    if ($this->existsOptionGroup($id)) {
      $this->updateOptionGroup($id, $name, $title);
    }
    else {
      $this->insertOptionGroup($id, $name, $title);
    }
  }

  public function createOptionValue($optionValue) {
    if (!$this->existsOptionValue($optionValue['option_group_id'], $optionValue['value'])) {
      $cols = $this->getOptionValueTableSpecs();
      $this->insertIntoTable('civicrm_option_value', $cols, $optionValue);
    }
  }

  private function existsOptionValue($optionGroupId, $optionValue) {
    $id = CRM_Core_DAO::singleValueQuery("select id from civicrm_option_value where option_group_id = $optionGroupId and value = %1", [1 => [$optionValue, 'String']]);
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function existsCustomGroup($name) {
    $id = CRM_Core_DAO::singleValueQuery("select name from civicrm_group where name = '$name'");
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function existsCustomField($id) {
    $id = CRM_Core_DAO::singleValueQuery("select id from civicrm_field where id = $id");
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function insertIntoTable($tableName, $columnSpecs, $sourceValues) {
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



  private function insertOptionGroup($id, $name, $title) {
    $sql = "insert into civicrm_option_group (id, name, title) values (%1, %2, %3)";
    $sqlParams = [
      1 => [$id, 'Integer'],
      2 => [$name, 'String'],
      3 => [$title, 'String'],
    ];

    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function updateOptionGroup($id, $name, $title) {
    $sql = "update civicrm_option_group set name = %2, title = %3 where id = %1";
    $sqlParams = [
      1 => [$id, 'Integer'],
      2 => [$name, 'String'],
      3 => [$title, 'String'],
    ];

    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function existsOptionGroup($id) {
    $id = CRM_Core_DAO::singleValueQuery("select id from civicrm_option_group where id = $id");
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
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

    return $cols;
  }

  private function getOptionValueTableSpecs() {
    $cols = [
      'option_group_id' => 'Integer',
      'label' => 'String',
      'value' => 'String',
      'name' => 'String',
      'grouping' => 'String',
      'filter' => 'Integer',
      'is_default' => 'Integer',
      'weight' => 'Integer',
      'description' => 'String',
      'is_optgroup' => 'Integer',
      'is_reserved' => 'Integer',
      'is_active' => 'Integer',
      'component_id' => 'Integer',
      'domain_id' => 'Integer',
      'visibility_id' => 'Integer',
      'icon' => 'String',
      'color' => 'String',
    ];

    return $cols;
  }

  private function getCustomFieldTableSpecs() {
    $cols = [
      'id' => 'Integer',
      'custom_group_id' => 'Integer',
      'name' => 'String',
      'label' => 'String',
      'data_type' => 'String',
      'html_type' => 'String',
      'default_value' => 'String',
      'is_required' => 'Integer',
      'is_searchable' => 'Integer',
      'is_search_range' => 'Integer',
      'weight' => 'Integer',
      'help_pre' => 'String',
      'help_post' => 'String',
      'mask' => 'String',
      'attributes' => 'String',
      'javascript' => 'String',
      'is_active' => 'Integer',
      'is_view' => 'Integer',
      'options_per_line' => 'Integer',
      'text_length' => 'Integer',
      'start_date_years' => 'Integer',
      'end_date_years' => 'Integer',
      'date_format' => 'String',
      'time_format' => 'Integer',
      'note_columns' => 'Integer',
      'note_rows' => 'Integer',
      'column_name' => 'String',
      'option_group_id' => 'Integer',
      'serialize' => 'Integer',
      'filter' => 'String',
      'in_selector' => 'Integer',
    ];

    return $cols;
  }


}
