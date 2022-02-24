#!/bin/bash

DRUPAL_SETTINGS_FILE=../web/sites/default/settings.php
if [[ ! -f "$DRUPAL_SETTINGS_FILE" ]]
then
  echo "ERROR: Cannot find $DRUPAL_SETTINGS_FILE"
  exit 1
fi

TMP_FOLDER=../tmp
if [[ ! -d "$TMP_FOLDER" ]]
then
  echo "ERROR: Cannot find $TMP_FOLDER"
  exit 1
fi


exit 0
