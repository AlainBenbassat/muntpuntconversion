#!/bin/bash
CVCOMMAND=../vendor/totten/cv

CIVI_CREDENTIALS=settings/civi.cnf
if [[ ! -f "$CIVI_CREDENTIALS" ]]
then
  echo "ERROR: Cannot find $CIVI_CREDENTIALS. Create it first."
  exit 1
fi

echo "Clearing civi database..."
mysql --defaults-file="$CIVI_CREDENTIALS" db18740 < tools/drop_all_tables.sql
[[ $? != 0 ]] && exit 1

echo "Restoring the clean NL civi..."
mysql --defaults-file="$CIVI_CREDENTIALS" db18740 < ../tmp/cleane_civi_NL.sql

"$CVCOMMAND" flush
