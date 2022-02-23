#!/bin/bash

function mpc_exit {
  echo "Exit with errors"
  exit 1
}

#===========================
tools/mpc_verify_location.sh
[[ $? != 0 ]] && mpc_exit
#===========================


echo "OK"
