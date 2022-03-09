#!/bin/bash

EXTPATH=../web/sites/default/files/civicrm/ext
CVCOMMAND=$(pwd)/../vendor/totten/cv
#DRUSHCOMMAND=$(pwd)/../vendor/drush/drush/drush
DRUSHCOMMAND=drush

function enableExtension() {
  "$CVCOMMAND" ext:enable $1
}

function installExtensionWithGit() {
  if [[ ! -d "$1" ]]
  then
    git clone $2
  fi

  enableExtension $1
}

function installExtensionConfigItems() {
  if [[ ! -d "configitems" ]]
  then
    git clone https://lab.civicrm.org/extensions/configitems.git
  fi

  enableExtension civiconfig
}

function installExtensionWithCv() {
  "$CVCOMMAND" ext:download -k $1

  enableExtension $1
}

cd "$EXTPATH"

# choose the appropriate installation method:
#  install extension with git
#  install extension with cv
#  or custom
installExtensionConfigItems
installExtensionWithGit nz.co.fuzion.omnipaymultiprocessor https://github.com/eileenmcnaughton/nz.co.fuzion.omnipaymultiprocessor.git
installExtensionWithCv uk.co.vedaconsulting.mosaico
installExtensionWithGit de.systopia.identitytracker https://github.com/systopia/de.systopia.identitytracker.git
installExtensionWithGit be.muntpunt.muntpuntconfig https://github.com/AlainBenbassat/be.muntpunt.muntpuntconfig.git
installExtensionWithGit be.muntpunt.eventlist https://github.com/AlainBenbassat/be.muntpunt.eventlist.git
installExtensionWithGit com.osseed.eventcalendar https://github.com/osseed/com.osseed.eventcalendar.git
installExtensionWithGit finsburypark https://lab.civicrm.org/extensions/finsburypark.git
installExtensionWithGit mosaicomsgtpl https://lab.civicrm.org/extensions/mosaicomsgtpl.git
installExtensionWithGit prettyworkflowmessages https://lab.civicrm.org/extensions/prettyworkflowmessages.git
installExtensionWithCv org.civicrm.recentmenu
installExtensionWithCv dataprocessor
installExtensionWithGit dataprocessor-duplicatecontacts https://lab.civicrm.org/extensions/dataprocessor-duplicatecontacts.git

"$CVCOMMAND" ext:upgrade-db
"$DRUSHCOMMAND" cr

