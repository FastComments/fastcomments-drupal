#!/bin/bash
# Copy all module files to the drupal.org repo.
# Usage: ./drupal_copy.sh

SRC="$(cd "$(dirname "$0")" && pwd)"
DEST="$SRC/../fastcomments-drupal-drupalorg"

cp -rv "$SRC"/* "$DEST"/
cp -v "$SRC"/.cspell.json "$SRC"/.gitlab-ci.yml "$DEST"/
