#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

sed -ri "s/theme: quark/theme: ${THEME}/" /var/www/html/user/config/system.yaml

# Copy grav sources
cp -R /var/www/grav-admin/system/* /var/www/html/system

# recover base plugins
cp -R /var/www/grav-admin/user/plugins/* /var/www/html/user/plugins
cd /var/www/html/user/plugins/ingrid-search-result && composer update
cd /var/www/html/user/plugins/ingrid-rss && composer update
cd /var/www/html/user/plugins/ingrid-codelist && composer update
cd /var/www/html/user/plugins/ingrid-grav && composer update

# init gravcms scheduler
ln -s /usr/local/bin/php /usr/bin/php
(echo "* * * * * cd /var/www/$GRAV_FOLDER;/usr/local/bin/php bin/grav scheduler 1>> /dev/null 2>&1") | crontab -u www-data -

# sync on startup
cd /var/www/html
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-codelist-index
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-rss-index

service cron start

exec "$@"
