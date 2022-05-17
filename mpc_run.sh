#!/bin/bash

function mpc_exit {
  echo "Exit with errors"
  exit 1
}

#===========================
# Show the current date/time
#===========================
echo "----------- starting the conversion -----------"
date
echo "-----------------------------------------------"
echo

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

#=================================================================
# Mark the contacts we will migrate in the local icontact database
#=================================================================
php tools/mpc_convert.php score_source_contacts
php tools/mpc_convert.php mark_duplicates

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

#=======================
# Prepare the conversion
#=======================
php tools/mpc_convert.php clear_migration_ids

#=====================
# Start the conversion
#=====================
tools/mpc_convert_message_templates.sh
[[ $? != 0 ]] && mpc_exit
php tools/mpc_convert.php convert_profiles
php tools/mpc_convert.php convert_campaigns
php tools/mpc_convert.php convert_contacts
php tools/mpc_convert.php convert_relationships
php tools/mpc_convert.php convert_groups
php tools/mpc_convert.php convert_event_types
tools/mpc_configure_event_calendar.sh
[[ $? != 0 ]] && mpc_exit
php tools/mpc_convert.php convert_events

#======================
# Post conversion steps
#======================
php tools/mpc_convert.php clear_hidden_custom_fields_ids
tools/mpc_install_zebrix.sh

#===========================
# Show the current date/time
#===========================
echo
echo "----------- conversion finished -----------"
date
echo "-------------------------------------------"
echo
