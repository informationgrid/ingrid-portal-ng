#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

mkdir -p /var/www/"$GRAV_FOLDER"
cd /var/www/"$GRAV_FOLDER"

rsync -rlD --delete \
           --exclude /backup/ \
           --exclude /logs/ \
           --exclude /tmp/ \
           --exclude /user/config/ \
           --exclude /user/accounts/admin.yaml \
           /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"

sed -ri "s/theme: quark/theme: ${THEME}/" /var/www/"$GRAV_FOLDER"/user/config/system.yaml
sed -ri "s/supported: null/supported:\n    - de/" /var/www/"$GRAV_FOLDER"/user/config/system.yaml
sed -ri "s/default_lang: null/default_lang: de/" /var/www/"$GRAV_FOLDER"/user/config/system.yaml
mkdir -p assets backup cache images logs tmp

chown www-data /proc/self/fd/1 /proc/self/fd/2
chown -R www-data:www-data /var/www/"$GRAV_FOLDER"

exec gosu www-data "$@"
