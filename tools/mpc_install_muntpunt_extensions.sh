#!/bin/bash

EXTPATH=$(cd ../..; pwd)

which cv >/dev/null
if [ $? -eq 0 ]
then
  CVCOMMAND="cv"
else
  CVCOMMAND="$EXTPATH/../../../../../../cv"
fi

function installExtension() {
  if [! -d "$EXTPATH/$1" ]
  then
    cd "$EXTPATH"
    git clone $2
  fi
exit
  cv en $1
}

installExtension configitems https://lab.civicrm.org/extensions/configitems.git
installExtension be.muntpunt.muntpuntconfig https://github.com/AlainBenbassat/be.muntpunt.muntpuntconfig.git
installExtension be.muntpunt.eventlist https://github.com/AlainBenbassat/be.muntpunt.eventlist.git
installExtension de.systopia.identitytracker https://github.com/systopia/de.systopia.identitytracker.git
installExtension com.osseed.eventcalendar https://github.com/osseed/com.osseed.eventcalendar.git
installExtension finsburypark https://lab.civicrm.org/extensions/finsburypark.git
installExtension mosaicomsgtpl https://lab.civicrm.org/extensions/mosaicomsgtpl.git
installExtension nz.co.fuzion.omnipaymultiprocessor https://github.com/eileenmcnaughton/nz.co.fuzion.omnipaymultiprocessor.git
installExtension prettyworkflowmessages https://lab.civicrm.org/extensions/prettyworkflowmessages.git
cv en org.civicrm.recentmenu

