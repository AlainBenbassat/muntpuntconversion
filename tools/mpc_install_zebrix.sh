#!/bin/bash

ZEBRIXPATH=../web

cd "$ZEBRIXPATH"
if [[ ! -d zebrix ]]
then
  git clone https://github.com/AlainBenbassat/zebrix.git
else
  cd zebrix
  git pull
  cd ..
fi
