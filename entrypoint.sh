#!/bin/sh

THEME=${THEME:-ingrid}
GRAV_FOLDER=${GRAV_FOLDER:-html}
ENABLE_MVIS=${ENABLE_MVIS:-true}
ENABLE_CACHE=${ENABLE_CACHE:-true}
THEME_COPY_PAGES_INIT=${THEME_COPY_PAGES_INIT:-false}
ENABLE_SCHEDULER_CODELIST=${ENABLE_SCHEDULER_CODELIST:-true}
ENABLE_SCHEDULER_RSS=${ENABLE_SCHEDULER_RSS:-true}
ENABLE_SCHEDULER_BACKUP=${ENABLE_SCHEDULER_BACKUP:-false}
MARKDOWN_AUTO_LINE_BREAKS=${MARKDOWN_AUTO_LINE_BREAKS:-true}
HOMEPAGE=${HOMEPAGE:-/home}
SITE_DEFAULT_LANG=${SITE_DEFAULT_LANG:-de}
SERVICE_WAIT_TIMEOUT=${SERVICE_WAIT_TIMEOUT:-60}
SERVICE_WAIT_INTERVAL=${SERVICE_WAIT_INTERVAL:-5}
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-1024M}
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-300}
PHP_FPM_PM=${PHP_FPM_PM:-dynamic}
PHP_FPM_PM_MAX_CHILDREN=${PHP_FPM_PM_MAX_CHILDREN:-20}
PHP_FPM_PM_START_SERVERS=${PHP_FPM_PM_START_SERVERS:-5}
PHP_FPM_PM_MIN_SPARE_SERVERS=${PHP_FPM_PM_MIN_SPARE_SERVERS:-5}
PHP_FPM_PM_MAX_SPARE_SERVERS=${PHP_FPM_PM_MAX_SPARE_SERVERS:-15}

#####################
# PHP ini
#####################
PHP_INI_FILE="$PHP_INI_DIR"/php.ini
sed -i "s/memory_limit = 128M/memory_limit = $PHP_MEMORY_LIMIT/" "$PHP_INI_FILE"
sed -i "s/max_execution_time = 30/max_execution_time = $PHP_MAX_EXECUTION_TIME/" "$PHP_INI_FILE"

#####################
# PHP FPM
#####################
PHP_FPM_FILE=/usr/local/etc/php-fpm.d/www.conf
sed -i "s/pm = dynamic/pm = $PHP_FPM_PM/" "$PHP_FPM_FILE"
sed -i "s/pm.max_children = 5/pm.max_children = $PHP_FPM_PM_MAX_CHILDREN/" "$PHP_FPM_FILE"
sed -i "s/pm.start_servers = 2/pm.start_servers = $PHP_FPM_PM_START_SERVERS/" "$PHP_FPM_FILE"
sed -i "s/pm.min_spare_servers = 1/pm.min_spare_servers = $PHP_FPM_PM_MIN_SPARE_SERVERS/" "$PHP_FPM_FILE"
sed -i "s/pm.max_spare_servers = 3/pm.max_spare_servers = $PHP_FPM_PM_MAX_SPARE_SERVERS/" "$PHP_FPM_FILE"

# Function to wait for a service to be ready
wait_for_codelist_repo() {
  if [ -z "$CODELIST_API" ]; then
    echo "No service to wait for, continuing..."
    return 0
  fi
  # Initialize timeout counter
  elapsed=0

  # Loop until service is available or timeout is reached
  while [ $elapsed -lt "$SERVICE_WAIT_TIMEOUT" ]; do
      # For HTTP services
    if wget -q --spider --timeout=1 --tries=1 $CODELIST_API --user $CODELIST_USER --password $CODELIST_PASS >/dev/null 2>&1; then
      echo "Service at $CODELIST_API is ready! (HTTP connection successful)"
      return 0
    fi

    sleep "$SERVICE_WAIT_INTERVAL"
    elapsed=$((elapsed + SERVICE_WAIT_INTERVAL))
    echo "Still waiting for service at $CODELIST_API... ($elapsed/$SERVICE_WAIT_TIMEOUT seconds elapsed)"
  done

  echo "Timeout reached while waiting for service at $CODELIST_API"
  return 1
}

# Wait for the service to be ready before continuing
if ! wait_for_codelist_repo; then
  echo "Critical service is not available, exiting..."
fi

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
             --exclude /user/pages \
             /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"
else
  rsync -rlD --delete \
             --exclude /backup/ \
             --exclude /logs/ \
             --exclude /tmp/ \
             --exclude /user/accounts/admin.yaml \
             --exclude /user/pages \
             /usr/share/grav-admin/ /var/www/"$GRAV_FOLDER"
fi

#####################
# Default admin config
#####################
ADMIN_YAML=/var/www/"$GRAV_FOLDER"/user/accounts/admin.yaml

# Add admin user
if [ ! -f "$ADMIN_YAML" ] && [ -n "$ADMIN_PASSWORD" ]; then
  cp /usr/share/grav-admin/user/accounts/admin.yaml.template "$ADMIN_YAML"
  yq -i '.email = env(ADMIN_EMAIL)' "$ADMIN_YAML"
  yq -i '.fullname = env(ADMIN_FULL_NAME)' "$ADMIN_YAML"

  hashed_password=$(htpasswd -bnBC 8 "" "$ADMIN_PASSWORD" | grep -oP '\$2[ayb]\$.{56}') \
  yq -i '.hashed_password = env(hashed_password)' "$ADMIN_YAML"
fi

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

# Display errors
yq -i '.errors.display = -1' "$SYSTEM_YAML"

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

# Update cache
ENABLE_CACHE="$ENABLE_CACHE" \
yq -i '.cache.enabled = env(ENABLE_CACHE)' "$SYSTEM_YAML"

# Add home
yq -i '.home.alias = env(HOMEPAGE)' "$SYSTEM_YAML"

# copy default cms pages
if [ ! -e /var/www/"$GRAV_FOLDER"/user/config/initialized ] || [ "$THEME_COPY_PAGES_INIT" = "true" ]; then

  if [ ! -d "/var/www/$GRAV_FOLDER/user/pages/" ]; then
    cp -rf /usr/share/grav-admin/user/pages/ /var/www/"$GRAV_FOLDER"/user/pages/
  fi

  if [ -d "/var/www/$GRAV_FOLDER/user/themes/$THEME/pages/" ]; then
      echo "Copy theme init pages."
      cp -rf /var/www/"$GRAV_FOLDER"/user/themes/"$THEME"/pages/init/* /var/www/"$GRAV_FOLDER"/user/pages/
    fi
    touch /var/www/"$GRAV_FOLDER"/user/config/initialized
else
    echo "No theme init pages process."
fi

# Set cache clear to type 'cache-only'
yq -i ".cache.clear_job_type = 'cache-only'" "$SYSTEM_YAML"

#####################
# Default ingrid grav plugin config
#####################
INGRID_GRAV_YAML=/var/www/"$GRAV_FOLDER"/user/plugins/ingrid-grav/ingrid-grav.yaml

# Update ingrid api
if [ "$INGRID_API" ]; then
  yq -i '.ingrid_api.url = env(INGRID_API)' "$INGRID_GRAV_YAML"
fi

if [ "$CSW_URL" ]; then
  yq -i '.csw.url = env(CSW_URL)' "$INGRID_GRAV_YAML"
fi

if [ "$RDF_URL" ]; then
  yq -i '.rdf.url = env(RDF_URL)' "$INGRID_GRAV_YAML"
fi

#####################
# Default ingrid grav utils plugin config
#####################
INGRID_GRAV_UTILS_YAML=/var/www/"$GRAV_FOLDER"/user/plugins/ingrid-grav-utils/ingrid-grav-utils.yaml

if [ "$CODELIST_API" ]; then
  yq -i '.codelist_api.url = env(CODELIST_API)' "$INGRID_GRAV_UTILS_YAML"
fi

if [ "$CODELIST_USER" ]; then
  yq -i '.codelist_api.user = env(CODELIST_USER)' "$INGRID_GRAV_UTILS_YAML"
fi

if [ "$CODELIST_PASS" ]; then
  yq -i '.codelist_api.pass = env(CODELIST_PASS)' "$INGRID_GRAV_UTILS_YAML"
fi

# Update geo api
if [ "$GEO_API_URL" ]; then
  yq -i '.geo_api.url = env(GEO_API_URL)' "$INGRID_GRAV_UTILS_YAML"
fi

if [ "$GEO_API_USER" ]; then
  yq -i '.geo_api.user = env(GEO_API_USER)' "$INGRID_GRAV_UTILS_YAML"
fi

if [ "$GEO_API_PASS" ]; then
  yq -i '.geo_api.pass = env(GEO_API_PASS)' "$INGRID_GRAV_UTILS_YAML"
fi

#####################
# Active theme config
#####################
INGRID_GRAV_THEME_YAML=/var/www/"$GRAV_FOLDER"/user/themes/"$THEME"/"$THEME".yaml

if [ "$ENABLE_FOOTER_BANNER" ]; then
  yq -i '.footer.banner.enabled = env(ENABLE_FOOTER_BANNER)' "$INGRID_GRAV_THEME_YAML"
fi

if [ "$TEXT_FOOTER_BANNER" ]; then
  yq -i '.footer.banner.text = env(TEXT_FOOTER_BANNER)' "$INGRID_GRAV_THEME_YAML"
fi

if [ "$MAP_URL" ]; then
  yq -i '.map.url = env(MAP_URL)' "$INGRID_GRAV_THEME_YAML"
fi

if [ "$MAP_IS_MASTERPORTAL" ]; then
  yq -i '.map.is_masterportal = env(MAP_IS_MASTERPORTAL)' "$INGRID_GRAV_THEME_YAML"
fi
#####################
# Default admin config
#####################
THEMES_CONFIG_FOLDER=/var/www/"$GRAV_FOLDER"/user/config/themes/
THEME_CONFIG_YAML="$THEMES_CONFIG_FOLDER"/"$THEME".yaml

if [ ! -f "$THEME_CONFIG_YAML" ]; then
  if [ ! -d "$THEMES_CONFIG_FOLDER" ]; then
    mkdir "$THEMES_CONFIG_FOLDER"
  fi
  # cp /var/www/"$GRAV_FOLDER"/user/themes/"$THEME"/"$THEME".yaml "$THEME_CONFIG_YAML"
fi

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

if [ "$ENABLE_SCHEDULER_RSS" = "true" ]; then
  yq -i '.status.ingrid-rss-index = "enabled"' "$SCHEDULER_YAML"
else
  yq -i '.status.ingrid-rss-index = "disabled"' "$SCHEDULER_YAML"
fi

if [ "$ENABLE_SCHEDULER_BACKUP" = "true" ]; then
  yq -i '.status.default-site-backup = "enabled"' "$SCHEDULER_YAML"
else
  yq -i '.status.default-site-backup = "disabled"' "$SCHEDULER_YAML"
fi

mkdir -p assets backup cache images logs tmp

# Install mvis
if [ "$ENABLE_MVIS" = "false" ]; then
  rm -rf /var/www/"$GRAV_FOLDER"/assets/mvis
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
