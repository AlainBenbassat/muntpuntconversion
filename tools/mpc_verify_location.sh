#!/bin/bash

DRUPAL_SETTINGS_FILE=../web/sites/default/settings.php
if [[ ! -f "$DRUPAL_SETTINGS_FILE" ]]
then
  echo "ERROR: Cannot find $DRUPAL_SETTINGS_FILE"
  exit 1
fi

exit 0
