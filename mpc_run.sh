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
tools/mpc_restore_blank__civi_database.sh
[[ $? != 0 ]] && mpc_exit

echo "OK"
