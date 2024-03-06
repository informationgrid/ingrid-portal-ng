#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

sed -ri "s/theme: quark/theme: ${THEME}/" /var/www/html/user/config/system.yaml

# recover base plugins
cp -R /var/www/grav-admin/user/plugins/* /var/www/html/user/plugins

exec "$@"