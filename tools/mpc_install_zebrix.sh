#!/bin/bash

ZEBRIXPATH=../web

cd "$ZEBRIXPATH"
if [[ ! -d zebrix ]]
then
  git clone https://github.com/AlainBenbassat/zebrix.git
elif
  cd zebrix
  git pull
  cd ..
fi
