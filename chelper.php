<?php

require 'common.php';
require 'classes/SourceDB.php';


function main() {
  bootstrapCiviCRM();

  $optionGroups = [
    263 => 'Doelgroep',
    267 => 'Taal',
    361 => 'Evenement status',
    519 => 'Muntpunt zalen',
    271 => 'Gevoerde promotie',
    269 => 'Kernfunctie',
    273 => 'Activiteitensoort',
    275 => 'Doelstelling',
    277 => 'Soorten doelgroepen',
  ];
  //exportOptionGroups($optionGroups);

  $groups = [
    'private_extraevent' => 'Evenement extra info',
    'Private_event_info' => 'Evenement planning',
    'Private_Bios' =>'Evenement BIOS',
  ];
  exportCustomGroups($groups, $optionGroups);
}

function exportOptionGroups($groups) {
  echo "{\n";
  echo "  \"entity\": \"OptionGroup\",\n";
  echo "  \"data\": {\n";

  $i = 1;
  $numGroups = count($groups);
  foreach ($groups as $optionGroupId => $title) {
    exportOptionValues($optionGroupId, $title);

    addNewLineAndOrComma($i, $numGroups);
  }

  echo "  }\n";
  echo "}\n";
}

function exportOptionValues($optionGroupId, $title) {
  $name = convertName($title);

  echo "    \"$name\": {\n";
  echo "      \"name\": \"$name\",\n";
  echo "      \"title\": \"$title\",\n";
  echo "      \"is_reserved\": \"0\",\n";
  echo "      \"is_active\": \"1\",\n";
  echo "      \"is_locked\": \"0\",\n";
  echo "      \"option_values\": {\n";


  $pdo = SourceDB::getPDO();
  $sql = "select * from civicrm_option_value where option_group_id = $optionGroupId order by weight";
  $numValues = getSingleVal("select count(*) from civicrm_option_value where option_group_id = $optionGroupId");
  $dao = $pdo->query($sql);
  $i = 1;
  while ($optionValue = $dao->fetch()) {
    echo "        \"" . $optionValue['label'] . "\": {\n";
    echo "          \"label\": \"" . $optionValue['label'] . "\",\n";
    echo "          \"value\": \"" . $optionValue['value'] . "\",\n";
    echo "          \"name\": \"" . $optionValue['name'] . "\",\n";
    echo "          \"filter\": \"" . $optionValue['filter'] . "\",\n";
    echo "          \"is_default\": \"" . $optionValue['is_default'] . "\",\n";
    echo "          \"weight\": \"" . $optionValue['weight'] . "\",\n";
    echo "          \"is_optgroup\": \"" . $optionValue['is_optgroup'] . "\",\n";
    echo "          \"is_reserved\": \"" . $optionValue['is_reserved'] . "\",\n";
    echo "          \"is_active\": \"" . $optionValue['is_active'] . "\",\n";
    echo "          \"option_group\": \"" . $name . "\"\n";
    echo "        }";
    addNewLineAndOrComma($i, $numValues);
  }

  echo "      }\n";
  echo "    }";
}

function exportCustomGroups($groups, $optionGroups) {
  echo "{\n";
  echo "  \"entity\": \"CustomGroup\",\n";
  echo "  \"data\": {\n";

  $i = 1;
  $numGroups = count($groups);
  foreach ($groups as $customGroupOldName => $customGroupNewName) {
    exportCustomGroup($customGroupOldName, $customGroupNewName, $optionGroups);

    addNewLineAndOrComma($i, $numGroups);
  }

  echo "  }\n";
  echo "}\n";
}

function exportCustomGroup($customGroupOldName, $customGroupNewName, $optionGroups) {
  $name = convertName($customGroupNewName);

  echo "    \"$name\": {\n";
  echo "      \"name\": \"$name\",\n";
  echo "      \"title\": \"$customGroupNewName\",\n";
  echo "      \"extends\": \"Event\",\n";
  echo "      \"is_reserved\": \"0\",\n";
  echo "      \"is_active\": \"1\",\n";
  echo "      \"style\": \"Inline\",\n";
  echo "      \"collapse_display\": \"0\",\n";
  echo "      \"table_name\": \"civicrm_value_$name\",\n";
  echo "      \"fields\": {\n";

  exportCustomFields($customGroupOldName, $optionGroups);

  echo "      }\n";
  echo "    }";
}

function exportCustomFields($customGroupOldName, $optionGroups) {
  $pdo = SourceDB::getPDO();
  $sql = "select * from civicrm_custom_field where custom_group_id in (select id from civicrm_custom_group where name = '$customGroupOldName') order by weight";
  $numValues = getSingleVal("select count(*) from civicrm_custom_field where custom_group_id in (select id from civicrm_custom_group where name = '$customGroupOldName')");
  $dao = $pdo->query($sql);
  $i = 1;
  while ($customField = $dao->fetch()) {
    $name = convertName($customField['name']);
    $label = convertLabel($customField['label']);

    echo "        \"" . $name . "\": {\n";
    echo "          \"name\": \"" . $name . "\",\n";
    echo "          \"label\": \"" . $label . "\",\n";

    $fields = ['data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'help_pre', 'help_post', 'mask', 'attributes', 'javascript', 'is_active', 'is_view', 'options_per_line', 'text_length', 'start_date_years', 'end_date_years', 'date_format', 'time_format', 'note_columns', 'note_rows', 'filter', 'in_selector'];
    foreach ($fields as $field) {
      echo "          \"$field\": \"" . $customField[$field] . "\",\n";
    }

    echo "          \"default_value\": \"" . convertDefaultValue($customField['default_value']) . "\",\n";
    echo "          \"column_name\": \"" . convertColumnName($customField['column_name']) . "\",\n";

    if ($customField['option_group_id']) {
      echo "          \"option_group\": \"" . convertOptionGroupIdToName($customField['option_group_id'], $optionGroups) . "\",\n";
    }

    echo "          \"weight\": \"" . $i . "\"\n";

    echo "        }";
    addNewLineAndOrComma($i, $numValues);
  }
}

function convertName($s) {
  if ($s == 'Organizer') {
    $s = 'Organisator';
  }

  $s = trim(strtolower($s));
  $s = str_replace(' ', '_', $s);

  return $s;
}

function convertColumnName($s) {
  $s = trim(preg_replace('/_[0-9]+$/', '', $s));
  return $s;
}

function convertDefaultValue($s) {
  $sep = chr(1);
  $s = preg_replace("/^$sep/", '', $s);
  $s = preg_replace("/$sep$/", '', $s);
  $s = preg_replace("/$sep/", ',', $s);
  return $s;
}

function convertOptionGroupIdToName($optionGroupId, $optionGroups) {
  if (empty($optionGroupId)) {
    return '';
  }

  if (!array_key_exists($optionGroupId, $optionGroups)) {
    throw new Exception("Cannot find $optionGroupId");
  }
  $title = $optionGroups[$optionGroupId];
  return convertName($title);
}

function convertLabel($s) {
  $s = str_replace(':', '', $s);
  return $s;
}

function addNewLineAndOrComma(&$i, $total) {
  if ($i == $total) {
    echo "\n";
  }
  else {
    echo ",\n";
  }

  $i++;
}

function getSingleVal($sql) {
  $pdo = SourceDB::getPDO();
  $q = $pdo->prepare($sql);
  $q->execute();
  return $q->fetchColumn();
}

main();
