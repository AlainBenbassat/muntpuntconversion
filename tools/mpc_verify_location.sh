#!/bin/bash

DRUPAL_SETTINGS_FILE=../web/sites/default/settings.php
if [[ ! -f "$DRUPAL_SETTINGS_FILE" ]]
then
  echo "ERROR: Cannot find $DRUPAL_SETTINGS_FILE"
  exit 1
fi

EXT_FOLDER=../web/sites/default/files/civicrm/ext
if [[ ! -f "$EXT_FOLDER" ]]
then
  echo "ERROR: Cannot find $EXT_FOLDER"
  exit 1
fi

TMP_FOLDER=../tmp
if [[ ! -d "$TMP_FOLDER" ]]
then
  echo "ERROR: Cannot find $TMP_FOLDER"
  exit 1
fi

