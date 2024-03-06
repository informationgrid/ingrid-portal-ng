#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

sed -ri "s/theme: quark/theme: ${THEME}/" /usr/share/grav-admin/user/config/system.yaml
mkdir -p /var/www/"$GRAV_FOLDER"
cd /var/www/"$GRAV_FOLDER"

#           --exclude /vendor/ \
rsync -rlD --delete \
           --exclude /backup/ \
           --exclude /logs/ \
           --exclude /tmp/ \
           --exclude /user/accounts/admin.yaml \
           --exclude /user/themes \
           --exclude /user/pages \
           /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"

mkdir -p assets backup cache images logs tmp
#           --exclude /user/ \
#mv html/user/plugins portal-ng/user/

chown www-data /proc/self/fd/1 /proc/self/fd/2
chown -R www-data:www-data /var/www/"$GRAV_FOLDER"

exec gosu www-data "$@"