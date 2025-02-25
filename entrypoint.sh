#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}
MVIS_VERSION=${MVIS_VERSION:-2.0.9}
ENABLE_MVIS=${ENABLE_MVIS:-true}
ENABLE_CACHE=${ENABLE_CACHE:-true}

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

# Add admin user
if [ ! -f "$ADMIN_YAML" ] && [ -n "$ADMIN_PASSWORD" ]; then
  cp /usr/share/grav-admin/user/accounts/admin.yaml.template "$ADMIN_YAML"
  yq -i '.email = env(ADMIN_EMAIL)' "$ADMIN_YAML"
  yq -i '.fullname = env(ADMIN_FULL_NAME)' "$ADMIN_YAML"

  hashed_password=$(htpasswd -bnBC 8 "" "$ADMIN_PASSWORD" | grep -oP '\$2[ayb]\$.{56}') \
  yq -i '.hashed_password = env(hashed_password)' "$ADMIN_YAML"
fi

SYSTEM_YAML=/var/www/"$GRAV_FOLDER"/user/config/system.yaml

# Add languages
yq -i '.languages.supported = ["de"]' "$SYSTEM_YAML"
yq -i '.languages.default_lang = "de"' "$SYSTEM_YAML"
yq -i '.languages.include_default_lang = false' "$SYSTEM_YAML"
if [ "$ENABLE_LANG_EN" ]; then
  yq -i '.languages.supported = ["de", "en"]' "$SYSTEM_YAML"
fi

# Add theme
yq -i '.pages.theme = env(THEME)' "$SYSTEM_YAML"

# Update system markdown
yq -i '.pages.markdown.auto_line_breaks = env(MARKDOWN_AUTO_LINE_BREAKS)' "$SYSTEM_YAML"

# Update timezone
if [ "$TZ" ]; then
  yq -i '.timezone = env(TZ)' "$SYSTEM_YAML"
else
  yq -i '.timezone = "Europe/Berlin"' "$SYSTEM_YAML"
fi

# Update cache
if [ "$ENABLE_CACHE" ]; then
  yq -i '.cache.enabled = true' "$SYSTEM_YAML"
else
  yq -i '.cache.enabled = false' "$SYSTEM_YAML"
fi

# copy default cms pages
if [ -d "/var/www/$GRAV_FOLDER/user/themes/$THEME/pages/cms" ]; then
  if yq '.theme_add_cms'; then
    echo "Init cms pages has been copied."
  else
    echo "Copy init cms pages."
    yq -i '.theme_add_cms = true' "$SYSTEM_YAML"
    cp -rf /var/www/"$GRAV_FOLDER"/user/themes/"$THEME"/pages/cms/* /var/www/"$GRAV_FOLDER"/user/pages/
  fi
fi

INGRID_GRAV_YAML=/var/www/"$GRAV_FOLDER"/user/plugins/ingrid-grav/ingrid-grav.yaml

# Update ingrid api
if [ "$INGRID_API" ]; then
  yq -i '.ingrid_api.url = env(INGRID_API)' "$INGRID_GRAV_YAML"
fi

# Update geo api
if [ "$GEO_API_URL" ]; then
  yq -i '.geo_api.url = env(GEO_API_URL)' "$INGRID_GRAV_YAML"
fi

if [ "$GEO_API_USER" ]; then
  yq -i '.geo_api.user = env(GEO_API_USER)' "$INGRID_GRAV_YAML"
fi

if [ "$GEO_API_PASS" ]; then
  yq -i '.geo_api.pass = env(GEO_API_PASS)' "$INGRID_GRAV_YAML"
fi

if [ "$CODELIST_API" ]; then
  yq -i '.codelist_api.url = env(CODELIST_API)' "$INGRID_GRAV_YAML"
fi

if [ "$CODELIST_USER" ]; then
  yq -i '.codelist_api.user = env(CODELIST_USER)' "$INGRID_GRAV_YAML"
fi

if [ "$CODELIST_PASS" ]; then
  yq -i '.codelist_api.pass = env(CODELIST_PASS)' "$INGRID_GRAV_YAML"
fi

if [ "$CSW_URL" ]; then
  yq -i '.csw.url = env(CSW_URL)' "$INGRID_GRAV_YAML"
fi

if [ "$RDF_URL" ]; then
  yq -i '.rdf.url = env(RDF_URL)' "$INGRID_GRAV_YAML"
fi

SITE_YAML=/var/www/"$GRAV_FOLDER"/user/config/site.yaml

if [ "$SITE_DEFAULT_LANG" ]; then
  yq -i '.default_lang = env(SITE_DEFAULT_LANG)' "$SITE_YAML"
else
  yq -i '.default_lang = "de"' "$SITE_YAML"
fi

SCHEDULER_YAML=/var/www/"$GRAV_FOLDER"/user/config/scheduler.yaml

if [ "$ENABLE_SCHEDULER_CODELIST" ]; then
  yq -i '.status.ingrid-codelist-index = "enabled"' "$SCHEDULER_YAML"
fi

if [ "$ENABLE_SCHEDULER_RSS" ]; then
  yq -i '.status.ingrid-rss-index = "enabled"' "$SCHEDULER_YAML"
fi

mkdir -p assets backup cache images logs tmp

# Install mvis
if [ "$ENABLE_MVIS" ]; then
  cd /var/www
  curl -o mvis.zip -SL https://nexus.informationgrid.eu/repository/maven-public/de/ingrid/measurement-client/${MVIS_VERSION}/measurement-client-${MVIS_VERSION}.zip && \
  unzip mvis.zip && \
  mv /var/www/measurement-client-"$MVIS_VERSION" /var/www/"$GRAV_FOLDER"/assets/mvis && \
  rm mvis.zip
fi

chown www-data /proc/self/fd/1 /proc/self/fd/2
chown -R www-data:www-data /var/www/"$GRAV_FOLDER"

# init gravcms scheduler
ln -s /usr/local/bin/php /usr/bin/php
(echo "* * * * * cd /var/www/$GRAV_FOLDER;/usr/local/bin/php bin/grav scheduler 1>> /dev/null 2>&1") | crontab -u www-data -

# sync on startup
cd /var/www/"$GRAV_FOLDER"
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-codelist-index
runuser -u www-data -- /usr/local/bin/php bin/grav scheduler -r ingrid-rss-index

service cron start

exec gosu www-data "$@"
