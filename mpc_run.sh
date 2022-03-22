#!/bin/bash

function mpc_exit {
  echo "Exit with errors"
  exit 1
}

#=======================================
# Make sure we're in the right directory
#=======================================
tools/mpc_verify_location.sh
[[ $? != 0 ]] && mpc_exit

#==============================================
# Copy the production icontact database locally
#==============================================
tools/mpc_copy_icontact_database.sh
[[ $? != 0 ]] && mpc_exit

#================================
# Restore the blank civi database
#================================
tools/mpc_restore_blank_civi_database.sh
[[ $? != 0 ]] && mpc_exit

#===================
# install extensions
#===================
tools/mpc_install_extensions.sh
[[ $? != 0 ]] && mpc_exit

#=============================
# Enable Muntpunt config items
#=============================
php tools/mpc_set_muntpunt_config.php
[[ $? != 0 ]] && mpc_exit

../vendor/totten/cv flush

#=====================
# Start the conversion
#=====================
php tools/mpc_convert.php profiles
php tools/mpc_convert.php score_source_contacts
php tools/mpc_convert.php mark_duplicates
php tools/mpc_convert.php convert_contacts
php tools/mpc_convert.php convert_relationships
php tools/mpc_convert.php convert_groups
php tools/mpc_convert.php convert_event_types_roles_status
php tools/mpc_convert.php convert_events

echo "OK"
