#!/bin/bash

LOCAL_ICONTACT_CREDENTIALS=settings/icontacts_local.cnf
CIVI_CREDENTIALS=settings/civi.cnf

mysqldump --skip-triggers --no-create-db --no-create-info --compact --skip-extended-insert --defaults-file="$LOCAL_ICONTACT_CREDENTIALS" db19666 \
  --tables civicrm_msg_template \
  --where="id in (343,373,375,377,379,399,411,419,421,423,426,443,444)" \
  | \
mysql --defaults-file="$CIVI_CREDENTIALS"

