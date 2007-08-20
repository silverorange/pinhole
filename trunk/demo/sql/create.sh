#!/bin/sh

WHOAMI=`whoami`
WD="/so/sites/gallery/work-${WHOAMI}/sql"

if [ -z $2 ]; then
    echo "usage: $0 <database> <host> [-d]"
    exit
fi

DB=$1
HOST=$2
DSN="pgsql://php@$HOST/$DB"
PEAR_PATH="/usr/share/pear"

# Clear any old database, and create a fresh one.
if [ "$3" ==  "-d" ]; then
	echo "Dropping the old database"
	dropdb -h $HOST -U php $DB
fi

echo "Creating the new database"
createdb -h $HOST -U php -E UTF8 $DB
if [ "$?" !=  "0" ]; then
	exit
fi

createlang -h $HOST -U postgres plpgsql $DB

# Create database objects from the Admin package.
./create.php $DSN $PEAR_PATH/data/Admin/sql/*/*.sql

# Create database objects from the NateGoSearch package.
./create.php $DSN $PEAR_PATH/data/NateGoSearch/sql/*/*.sql

# Create database objects from the Pinhole package.
psql -h $HOST -d $DB -U php -f $PEAR_PATH/data/Pinhole/demo/sql/demo-users.sql 
./create.php $DSN $PEAR_PATH/data/Pinhole/sql/*/*.sql
psql -h $HOST -d $DB -U php -f $PEAR_PATH/data/Pinhole/sql/admin-changes.sql 

