<?php

class ConvertorOBSOLETE {
  // onderstaande code mag wellicht weg

  private function convertCustomGroup($customGroupName) {

    $sourceCustomDataFetcher = new SourceCustomDataFetcher();
    $targetCustomData = new TargetCustomData();

    $this->convertOptionGroupsFromCustomGroup($customGroupName);

    echo "Converting custom group $customGroupName...\n";
    $customGroup = $sourceCustomDataFetcher->getCustomDataGroup($customGroupName);
    $targetCustomData->createCustomGroup($customGroup);

    $customFieldDAO = $sourceCustomDataFetcher->getCustomFields($customGroup['id']);
    while ($customField = $customFieldDAO->fetch()) {
      echo "Converting custom field $customGroupName...\n";
      $targetCustomData->createCustomField($customField);
    }
  }

  private function convertOptionGroupsFromCustomGroup($customGroupName) {
    $sourceCustomDataFetcher = new SourceCustomDataFetcher();
    $targetCustomData = new TargetCustomData();

    $optionGroupListDao = $sourceCustomDataFetcher->getCustomGroupOptionGroups($customGroupName);
    while ($sourceOptionGroup = $optionGroupListDao->fetch()) {
      echo 'Converting option group ' . $sourceOptionGroup['option_group_id'] . "...\n";

      [$optionGroupId, $name, $title] = $sourceCustomDataFetcher->getOptionGroupDetails($sourceOptionGroup['option_group_id']);
      $targetCustomData->createOptionGroup($optionGroupId, $name, $title);

      $optionValuesDAO = $sourceCustomDataFetcher->getOptionValues($optionGroupId);
      while ($sourceOptionValue = $optionValuesDAO->fetch()) {
        echo 'Convertion option value ' . $sourceOptionValue['label'] . "...\n";
        $targetCustomData->createOptionValue($sourceOptionValue);
      }
    }
  }


}
