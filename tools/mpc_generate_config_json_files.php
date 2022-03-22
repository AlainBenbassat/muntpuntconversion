<?php

require 'common.php';
require 'classes/SourceDB.php';

$fp = null;

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
  exportOptionGroups($optionGroups);

  $groups = [
    'private_extraevent' => 'Evenement extra info',
    'Private_event_info' => 'Evenement planning',
    'Private_Bios' =>'Evenement BIOS',
  ];
  exportCustomGroups($groups, $optionGroups);
}

function exportOptionGroups($groups) {
  openLogFile('option_groups.json');
  logLine("{\n");
  logLine("  \"entity\": \"OptionGroup\",\n");
  logLine("  \"data\": {\n");

  $i = 1;
  $numGroups = count($groups);
  foreach ($groups as $optionGroupId => $title) {
    exportOptionValues($optionGroupId, $title);

    addNewLineAndOrComma($i, $numGroups);
  }

  logLine("  }\n");
  logLine("}\n");
  closeLogFile();
}

function exportOptionValues($optionGroupId, $title) {
  $name = convertName($title);

  logLine("    \"$name\": {\n");
  logLine("      \"name\": \"$name\",\n");
  logLine("      \"title\": \"$title\",\n");
  logLine("      \"is_reserved\": \"0\",\n");
  logLine("      \"is_active\": \"1\",\n");
  logLine("      \"is_locked\": \"0\",\n");
  logLine("      \"option_values\": {\n");


  $pdo = SourceDB::getPDO();
  $sql = "select * from civicrm_option_value where option_group_id = $optionGroupId order by weight";
  $numValues = getSingleVal("select count(*) from civicrm_option_value where option_group_id = $optionGroupId");
  $dao = $pdo->query($sql);
  $i = 1;
  while ($optionValue = $dao->fetch()) {
    logLine("        \"" . $optionValue['label'] . "\": {\n");
    logLine("          \"label\": \"" . $optionValue['label'] . "\",\n");
    logLine("          \"value\": \"" . $optionValue['value'] . "\",\n");
    logLine("          \"name\": \"" . $optionValue['name'] . "\",\n");
    logLine("          \"filter\": \"" . $optionValue['filter'] . "\",\n");
    logLine("          \"is_default\": \"" . $optionValue['is_default'] . "\",\n");
    logLine("          \"weight\": \"" . $optionValue['weight'] . "\",\n");
    logLine("          \"is_optgroup\": \"" . $optionValue['is_optgroup'] . "\",\n");
    logLine("          \"is_reserved\": \"" . $optionValue['is_reserved'] . "\",\n");
    logLine("          \"is_active\": \"" . $optionValue['is_active'] . "\",\n");
    logLine("          \"option_group\": \"" . $name . "\"\n");
    logLine("        }");
    addNewLineAndOrComma($i, $numValues);
  }

  logLine("      }\n");
  logLine("    }");
}

function exportCustomGroups($groups, $optionGroups) {
  openLogFile('custom_groups.json');
  logLine("{\n");
  logLine("  \"entity\": \"CustomGroup\",\n");
  logLine("  \"data\": {\n");

  $i = 1;
  $numGroups = count($groups);
  $weight = 1;
  foreach ($groups as $customGroupOldName => $customGroupNewName) {
    exportCustomGroup($customGroupOldName, $customGroupNewName, $optionGroups, $weight);

    addNewLineAndOrComma($i, $numGroups);
    $weight++;
  }

  logLine("  }\n");
  logLine("}\n");
  closeLogFile();
}

function exportCustomGroup($customGroupOldName, $customGroupNewName, $optionGroups, $weight) {
  $name = convertName($customGroupNewName);

  logLine("    \"$name\": {\n");
  logLine("      \"name\": \"$name\",\n");
  logLine("      \"title\": \"$customGroupNewName\",\n");
  logLine("      \"extends\": \"Event\",\n");
  logLine("      \"is_reserved\": \"0\",\n");
  logLine("      \"is_active\": \"1\",\n");
  logLine("      \"is_public\": \"0\",\n");
  logLine("      \"style\": \"Inline\",\n");
  logLine("      \"collapse_display\": \"0\",\n");
  logLine("      \"table_name\": \"civicrm_value_$name\",\n");
  logLine("      \"weight\": \"$weight\",\n");
  logLine("      \"fields\": {\n");

  exportCustomFields($customGroupOldName, $optionGroups);

  logLine("      }\n");
  logLine("    }");
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

    logLine("        \"" . $name . "\": {\n");
    logLine("          \"name\": \"" . $name . "\",\n");
    logLine("          \"label\": \"" . $label . "\",\n");

    $fields = ['data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'help_pre', 'help_post', 'mask', 'attributes', 'javascript', 'is_active', 'is_view', 'options_per_line', 'text_length', 'start_date_years', 'end_date_years', 'date_format', 'time_format', 'note_columns', 'note_rows', 'filter', 'in_selector'];
    foreach ($fields as $field) {
      logLine("          \"$field\": \"" . $customField[$field] . "\",\n");
    }

    logLine("          \"default_value\": \"" . convertDefaultValue($customField['default_value']) . "\",\n");
    logLine("          \"column_name\": \"" . convertColumnName($customField['column_name']) . "\",\n");

    if ($customField['option_group_id']) {
      logLine("          \"option_group\": \"" . convertOptionGroupIdToName($customField['option_group_id'], $optionGroups) . "\",\n");
    }

    logLine("          \"weight\": \"" . $i . "\"\n");

    logLine("        }");
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

  if ($s == 'organizer') {
    $s = 'organisator';
  }

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
    logLine("\n");
  }
  else {
    logLine(",\n");
  }

  $i++;
}

function getSingleVal($sql) {
  $pdo = SourceDB::getPDO();
  $q = $pdo->prepare($sql);
  $q->execute();
  return $q->fetchColumn();
}

function logLine($s) {
  global $fp;
  fwrite($fp, $s);
}

function openLogFile($fileName) {
  global $fp;
  $fp = fopen("/home/alain/public_html/muntpunt/web/sites/default/files/civicrm/ext/be.muntpunt.muntpuntconfig/resources/$fileName", 'w');
}

function closeLogFile() {
  global $fp;
  fclose($fp);
}

main();
