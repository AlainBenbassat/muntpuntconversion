#!/bin/bash
REMOTE_ICONTACT_CREDENTIALS=settings/icontacts_remote.cnf
if [[ ! -f "$REMOTE_ICONTACT_CREDENTIALS" ]]
then
  echo "ERROR: Cannot find $REMOTE_ICONTACT_CREDENTIALS. Create it first."
  exit 1
fi

LOCAL_ICONTACT_CREDENTIALS=settings/icontacts_local.cnf
if [[ ! -f "$LOCAL_ICONTACT_CREDENTIALS" ]]
then
  echo "ERROR: Cannot find $LOCAL_ICONTACT_CREDENTIALS. Create it first."
  exit 1
fi

echo "Dumping remote icontact database locally..."
mysqldump  --defaults-file="$REMOTE_ICONTACT_CREDENTIALS" -h 172.25.17.2 -P 3307 db16377 \
  civicrm_activity \
  civicrm_activity_contact \
  civicrm_address \
  civicrm_contact \
  civicrm_custom_field \
  civicrm_custom_group \
  civicrm_email \
  civicrm_event \
  civicrm_group \
  civicrm_group_contact \
  civicrm_loc_block \
  civicrm_option_group \
  civicrm_option_value \
  civicrm_participant \
  civicrm_phone \
  civicrm_relationship \
  civicrm_relationship_type \
  civicrm_uf_match \
  civicrm_value_evenet_doelpgroep_109 \
  civicrm_value_private_event_info_115 \
  civicrm_value_private_bios_117 \
> ../tmp/icontact.sql

[[ $? != 0 ]] && exit 1

echo "Replacing DEFINER..."
sed -i 's/DEFINER=`db16377`@`%`/DEFINER=`db19666`@`localhost`/g' ../tmp/icontact.sql
[[ $? != 0 ]] && exit 1

echo "Clearing local icontact database..."
mysql --defaults-file="$LOCAL_ICONTACT_CREDENTIALS" db19666 < tools/drop_all_tables.sql
[[ $? != 0 ]] && exit 1

echo "Restoring the database locally..."
mysql --defaults-file="$LOCAL_ICONTACT_CREDENTIALS" db19666 < ../tmp/icontact.sql
