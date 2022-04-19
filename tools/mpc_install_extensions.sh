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

  cd configitems
  git checkout 1.3.5
  cd ..

  enableExtension civiconfig
}

function installExtensionMuntpuntConfig() {
  installExtensionWithGit be.muntpunt.muntpuntconfig https://github.com/AlainBenbassat/be.muntpunt.muntpuntconfig.git
  cd be.muntpunt.muntpuntconfig
  git pull
  cd ..
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
#installExtensionWithGit nz.co.fuzion.omnipaymultiprocessor https://github.com/eileenmcnaughton/nz.co.fuzion.omnipaymultiprocessor.git
installExtensionWithCv uk.co.vedaconsulting.mosaico
installExtensionWithGit de.systopia.identitytracker https://github.com/systopia/de.systopia.identitytracker.git
installExtensionMuntpuntConfig
installExtensionWithGit be.muntpunt.eventlist https://github.com/AlainBenbassat/be.muntpunt.eventlist.git
installExtensionWithGit com.osseed.eventcalendar https://github.com/osseed/com.osseed.eventcalendar.git
installExtensionWithGit finsburypark https://lab.civicrm.org/extensions/finsburypark.git
installExtensionWithGit mosaicomsgtpl https://lab.civicrm.org/extensions/mosaicomsgtpl.git
installExtensionWithGit prettyworkflowmessages https://lab.civicrm.org/extensions/prettyworkflowmessages.git
installExtensionWithGit agendabe https://github.com/AlainBenbassat/agendabe.git
installExtensionWithCv org.civicrm.recentmenu
installExtensionWithGit com.aghstrategies.airmail https://github.com/aghstrategies/com.aghstrategies.airmail.git
#installExtensionWithCv dataprocessor
#installExtensionWithGit dataprocessor-duplicatecontacts https://lab.civicrm.org/extensions/dataprocessor-duplicatecontacts.git

"$CVCOMMAND" ext:upgrade-db
"$DRUSHCOMMAND" cr

