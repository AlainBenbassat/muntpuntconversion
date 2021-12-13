<?php

require 'common.php';
require 'classes/SourceDB.php';


function main() {
  bootstrapCiviCRM();

  exportOptionGroups([
    263 => 'Doelgroep',
    267 => 'Taal',
    361 => 'Evenement status',
    519 => 'Muntpunt zalen',
  ]);
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
  $name = strtolower($title);

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
