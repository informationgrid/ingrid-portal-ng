#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}

mkdir -p /var/www/"$GRAV_FOLDER"
cd /var/www/"$GRAV_FOLDER"

# exclude user config when it's already there
if [ -d "/var/www/$GRAV_FOLDER/user/config" ]; then
  rsync -rlD --delete \
             --exclude /backup/ \
             --exclude /logs/ \
             --exclude /tmp/ \
             --exclude /user/config/ \
             --exclude /user/accounts/admin.yaml \
             /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"
else
  rsync -rlD --delete \
               --exclude /backup/ \
               --exclude /logs/ \
               --exclude /tmp/ \
               --exclude /user/accounts/admin.yaml \
               /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"
fi

ADMIN_YAML=/var/www/"$GRAV_FOLDER"/user/accounts/admin.yaml

if [ ! -f "$ADMIN_YAML" ] && [ -n "$ADMIN_PASSWORD" ]; then
  cp /usr/share/grav-admin/user/accounts/admin.yaml.template "$ADMIN_YAML"
  hashed_password=$(htpasswd -bnBC 8 "" "$ADMIN_PASSWORD" | grep -oP '\$2[ayb]\$.{56}')
  sed -ri "s/email:/email: ${ADMIN_EMAIL}/" "$ADMIN_YAML"
  sed -ri "s/fullname:/fullname: ${ADMIN_FULL_NAME}/" "$ADMIN_YAML"
  echo "hashed_password: ${hashed_password}" >> "$ADMIN_YAML"
fi

SYSTEM_YAML=/var/www/"$GRAV_FOLDER"/user/config/system.yaml

# add language part to yaml if it doesn't exist yet
if ! grep -q "^languages:" "$SYSTEM_YAML"; then
  echo "languages:" >> "$SYSTEM_YAML"
  echo "  supported:" >> "$SYSTEM_YAML"
  echo "    - de" >> "$SYSTEM_YAML"
  echo "  default_lang: de" >> "$SYSTEM_YAML"
  echo "  include_default_lang: false" >> "$SYSTEM_YAML"
else
  sed -ri "s/supported: null/supported:\n    - de/" "$SYSTEM_YAML"
  sed -ri "s/default_lang: null/default_lang: de/" "$SYSTEM_YAML"
  sed -ri "s/include_default_lang: true/include_default_lang: false/" "$SYSTEM_YAML"
fi

if ! grep -q "^pages:" "$SYSTEM_YAML"; then
  echo "pages:" >> "$SYSTEM_YAML"
  echo "  theme: ${THEME}" >> "$SYSTEM_YAML"
else
  sed -ri "s/theme: quark/theme: ${THEME}/" "$SYSTEM_YAML"
fi

sed -ri "s/timezone: null/timezone: 'Europe/Berlin'/" "$SYSTEM_YAML"

mkdir -p assets backup cache images logs tmp

chown www-data /proc/self/fd/1 /proc/self/fd/2
chown -R www-data:www-data /var/www/"$GRAV_FOLDER"

# Update codelist plugin
if [ "$CODELIST_API" ]; then
  sed -i -e "s@    url: '.*@    url: '${$CODELIST_API}'@" ${GRAV_FOLDER}/user/plugins/ingrid-codelist/ingrid-codelist.yaml
  if [ "$CODELIST_USER" ]; then
    sed -i -e "s@    user: '.*@    user: '${$CODELIST_USER}'@" ${GRAV_FOLDER}/user/plugins/ingrid-codelist/ingrid-codelist.yaml
  fi
  if [ "$CODELIST_PASS" ]; then
    sed -i -e "s@    pass: '.*@    pass: '${$CODELIST_PASS}'@" ${GRAV_FOLDER}/user/plugins/ingrid-codelist/ingrid-codelist.yaml
  fi
fi

# init gravcms scheduler
ln -s /usr/local/bin/php /usr/bin/php
(echo "* * * * * cd /var/www/$GRAV_FOLDER;/usr/local/bin/php bin/grav scheduler 1>> /dev/null 2>&1") | crontab -u www-data -

# sync on startup
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-codelist-index
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-rss-index

service cron start

exec gosu www-data "$@"
