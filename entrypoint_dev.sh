#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}
MVIS_VERSION=${MVIS_VERSION:-2.0.9}
ENABLE_MVIS=${ENABLE_MVIS:-true}
ENABLE_CACHE=${ENABLE_CACHE:-false}
ENABLE_SCHEDULER_CODELIST=${ENABLE_SCHEDULER_CODELIST:-true}
ENABLE_SCHEDULER_RSS=${ENABLE_SCHEDULER_RSS:-true}
MARKDOWN_AUTO_LINE_BREAKS=${MARKDOWN_AUTO_LINE_BREAKS:-true}
SITE_DEFAULT_LANG=${SITE_DEFAULT_LANG:-de}

#####################
# Default system config
#####################
SYSTEM_YAML=/var/www/"$GRAV_FOLDER"/user/config/system.yaml

# Add languages
yq -i '.languages.supported = ["de"]' "$SYSTEM_YAML"
yq -i '.languages.default_lang = "de"' "$SYSTEM_YAML"
yq -i '.languages.include_default_lang = false' "$SYSTEM_YAML"
if [ "$ENABLE_LANG_EN" ]; then
  yq -i '.languages.supported = ["de", "en"]' "$SYSTEM_YAML"
fi

# Add theme
THEME="$THEME" \
yq -i '.pages.theme = env(THEME)' "$SYSTEM_YAML"

# Update system markdown
MARKDOWN_AUTO_LINE_BREAKS="$MARKDOWN_AUTO_LINE_BREAKS" \
yq -i '.pages.markdown.auto_line_breaks = env(MARKDOWN_AUTO_LINE_BREAKS)' "$SYSTEM_YAML"

# Update timezone
if [ "$TZ" ]; then
  yq -i '.timezone = env(TZ)' "$SYSTEM_YAML"
else
  yq -i '.timezone = "Europe/Berlin"' "$SYSTEM_YAML"
fi

# Add pages.dirs for development
yq -i '.pages.dirs = ["page://", "theme://pages/init"]' "$SYSTEM_YAML"

# Disable cache for development
ENABLE_CACHE="$ENABLE_CACHE" \
yq -i '.cache.enabled  = env(ENABLE_CACHE)' "$SYSTEM_YAML"

#####################
# Default site config
#####################
SITE_YAML=/var/www/"$GRAV_FOLDER"/user/config/site.yaml

SITE_DEFAULT_LANG="$SITE_DEFAULT_LANG" \
yq -i '.default_lang = env(SITE_DEFAULT_LANG)' "$SITE_YAML"

#####################
# Default scheduler config
#####################
SCHEDULER_YAML=/var/www/"$GRAV_FOLDER"/user/config/scheduler.yaml

if [ ! -e "$SCHEDULER_YAML" ]; then
  touch "$SCHEDULER_YAML"
fi

if [ "$ENABLE_SCHEDULER_CODELIST" = "true" ]; then
  yq -i '.status.ingrid-codelist-index = "enabled"' "$SCHEDULER_YAML"
else
  yq -i '.status.ingrid-codelist-index = "disabled"' "$SCHEDULER_YAML"
fi

if [ "$ENABLE_SCHEDULER_RSS" = "true"  ]; then
  yq -i '.status.ingrid-rss-index = "enabled"' "$SCHEDULER_YAML"
else
  yq -i '.status.ingrid-rss-index = "disabled"' "$SCHEDULER_YAML"
fi

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