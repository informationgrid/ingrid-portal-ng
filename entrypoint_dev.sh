#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}
MVIS_VERSION=${MVIS_VERSION:-2.0.9}

sed -ri "s/theme: quark/theme: ${THEME}/" /var/www/html/user/config/system.yaml

# Copy grav sources
cp -R /var/www/grav-admin/system/* /var/www/html/system

# recover base plugins
cp -R /var/www/grav-admin/user/plugins/* /var/www/html/user/plugins
cd /var/www/html/user/plugins/ingrid-grav && composer update

# Install mvis
cd /var/www
curl -o mvis.zip -SL https://nexus.informationgrid.eu/repository/maven-public/de/ingrid/measurement-client/${MVIS_VERSION}/measurement-client-${MVIS_VERSION}.zip && \
unzip mvis.zip && \
mv /var/www/measurement-client-"$MVIS_VERSION" /var/www/"$GRAV_FOLDER"/assets/mvis && \
rm mvis.zip

# init gravcms scheduler
ln -s /usr/local/bin/php /usr/bin/php
(echo "* * * * * cd /var/www/$GRAV_FOLDER;/usr/local/bin/php bin/grav scheduler 1>> /dev/null 2>&1") | crontab -u www-data -

# sync on startup
cd /var/www/"$GRAV_FOLDER"
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-codelist-index
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-rss-index

service cron start

exec "$@"