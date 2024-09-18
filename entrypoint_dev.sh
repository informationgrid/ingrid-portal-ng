#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

sed -ri "s/theme: quark/theme: ${THEME}/" /var/www/html/user/config/system.yaml

# recover base plugins
cp -R /var/www/grav-admin/user/plugins/* /var/www/html/user/plugins
cd /var/www/html/user/plugins/ingrid-search-result && composer update
cd /var/www/html/user/plugins/ingrid-detail && composer update
cd /var/www/html/user/plugins/ingrid-rss && composer update
cd /var/www/html/user/plugins/ingrid-codelist && composer update
cd /var/www/html/user/plugins/ingrid-providers && composer update
cd /var/www/html/user/plugins/ingrid-help && composer update
cd /var/www/html/user/plugins/ingrid-catalog && composer update
cd /var/www/html/user/plugins/ingrid-grav-utils && composer update

exec "$@"