#!/bin/sh

#Create list of constants and variables
hg log -r 74:tip --template '- {desc}\n' | sort > changelog_raw.txt
