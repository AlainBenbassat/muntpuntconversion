<?php

require 'common.php';

function main() {
  try {
    loadClasses();
    bootstrapCiviCRM();

    $settings = getSettings();

    assertMailchimpZipFolder($settings);
    assertExtractedZipFolder($settings);

    extractZipFiles($settings);
    createMailchimpTables();
    importMailchimpGroups($settings);
  }
  catch (Exception $e) {
    echo "==============================================\n";
    echo 'ERROR in ' . $e->getFile() . ', line ' . $e->getLine() . ":\n";
    echo  $e->getMessage() . "\n";
    echo "==============================================\n\n";
    echo "\n\n";
  }

  echo "\nDone.\n";
}

function getSettings() {
  $settings = [
    'zipfolder' => __DIR__ . '/mailchimp/zip',
    'extractedzipfolder' => __DIR__ . '/mailchimp/zip/extracted',
  ];

  return $settings;
}

function assertMailchimpZipFolder($settings) {
  if (!is_dir($settings['zipfolder'])) {
    throw new Exception("Folder {$settings['zipfolder']} does not exist.\nDownload all marketing audiences as zip files in that folder.");
  }
}

function assertExtractedZipFolder($settings) {
  if (!is_dir($settings['extractedzipfolder'])) {
    mkdir($settings['extractedzipfolder']);
  }
}

function extractZipFiles($settings) {
  $files = glob($settings['zipfolder'] . '/*.zip');
  foreach($files as $file) {
    shell_exec("unzip '$file' -d " . $settings['extractedzipfolder']);
    renameSubscribed($settings['extractedzipfolder'], $file);
  }

  removeUnneededFiles($settings);
}

function getCsvFileName($path) {
  $fileName = basename($path);

  $n = strpos($fileName, '_members_export_');
  if ($n === FALSE) {
    throw new Exception("Unexpected file name: $path. Need _members_export_ in file name.");
  }

  $fileNameWithoutExtension = substr($fileName, 0, $n);
  return $fileNameWithoutExtension . '.csv';
}

function renameSubscribed($folder, $zipfileName) {
  $oldSubscribedFileName = "$folder/subscribed_members_export_*";
  $newSubscribedFileName = $folder . '/' . getCsvFileName($zipfileName);

  shell_exec("mv $oldSubscribedFileName '$newSubscribedFileName'");
}

function removeUnneededFiles($settings) {
  $patterns = [
    'cleaned_members_export_*',
    'nonsubscribed_members_export_*',
    'unsubscribed_members_export_*',
  ];

  foreach ($patterns as $pattern) {
    shell_exec('rm ' . $settings['extractedzipfolder'] . '/' . $pattern);
  }
}

function importMailchimpGroups($settings) {
  $files = glob($settings['extractedzipfolder'] . '/*.csv');
  foreach ($files as $file) {
    $groupName = getGroupNameFromFileName($file);
    $groupId = createGroup($groupName);

    parseMailchimpCSV($file, $groupId);
  }
}

function parseMailchimpCSV($file, $groupId) {
  $row = 1;
  $handle = fopen($file, "r");
  if ($handle === FALSE) {
    throw new Exception("Cannot open csv-file: $file");
  }

  $logger = new SourceContactLogger();
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    if ($row > 1) {
      $logger->logMailChimpGroupContact($groupId, $data[0]);
    }

    $row++;
  }
  fclose($handle);
}

function getGroupNameFromFileName($path) {
  $fileName = basename($path);
  return str_replace('.csv', '', $fileName);
}

function createGroup($groupName) {
  $logger = new SourceContactLogger();
  return $logger->logMailChimpGroup($groupName);
}

function createMailchimpTables() {
  $logger = new SourceContactLogger();
  $logger->clearLogTableMailchimp();
  $logger->createMailChimpTables();
}

main();
