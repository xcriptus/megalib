#!/bin/bash
# this file read a joomla configuration script defining variable
# this script is used by various joomla-xxx commands.

echo "The configuration file should be indicated without the .joomla.config.sh"
echo "The suffix will be automatically added."

CONFIGSCRIPT=${1?$USAGE}.joomla.config.sh
if source ${CONFIGSCRIPT?}
then
  echo "Script ${CONFIGSCRIPT?} executed"
else
  echo "Configuration script ${CONFIGSCRIPT?} not found" >/dev/stderr
  exit 1
fi
UNDEF="not defined in the configuration script"
echo
echo "--- CONFIGURATION for $1 ---"
echo "J_DIRECTORY=${J_DIRECTORY?$UNDEF}"
echo "J_DATABASE=${J_DATABASE?$UNDEF}"
echo "J_USER=${J_USER?$UNDEF}"
echo "J_PASSWORD=${J_PASSWORD?$UNDEF}"
echo "J_PREFIX=${J_PREFIX?$UNDEF}"
J_NAME=`basename ${J_DIRECTORY?$UNDEF}`
J_ROOTDIR=`dirname ${J_DIRECTORY?$UNDEF}`
J_BACKUPDIR=${J_ROOTDIR?}/_BACKUPS_
J_BACKUP_DBFILE_PREFIX=${J_BACKUPDIR?}/${J_NAME?}_DB_
J_LOGDIR=${J_ROOTDIR?}/_LOGS_
echo "-->"
echo "J_ROOTDIR=$J_ROOTDIR"
echo "J_BACKUPDIR=$J_BACKUPDIR"
echo "J_BACKUP_DBFILE_PREFIX=${J_BACKUP_DBFILE_PREFIX?}"
echo "J_LOGDIR=$J_LOGDIR"
echo "-----------------------------------"
echo

