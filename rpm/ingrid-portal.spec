%undefine __brp_mangle_shebangs

Name:           ingrid-portal
Version:        0.0.0
Release:        dev
Summary:        InGrid Portal Next Generation
Group:          Applications/Internet
License:        Proprietary
URL:            https://www.wemove.com/
BuildArch:      noarch
AutoReqProv: no
%define system_yaml %{buildroot}/var/www/ingrid-portal/user/config/system.yaml
%define context_path ingrid-portal

%description
InGrid Portal Next Generation

%prep
GRAV_VERSION=1.7.48
curl -o grav-admin.zip -SL https://getgrav.org/download/core/grav-admin/${GRAV_VERSION}

%build
# nothing to do

%install
rm -Rf %{buildroot}*

mkdir -p %{buildroot}/var/www
unzip -qq grav-admin.zip -d %{buildroot}/var/www/
rm grav-admin.zip
mv %{buildroot}/var/www/grav-admin %{buildroot}/var/www/%{context_path}

# COPY OUR ADDITIONAL THEMES AND PLUGINS
mkdir -p %{buildroot}/var/www/%{context_path}/user
# copy all except non-ingrid plugins
rsync -a \
  --include='/plugins/ingrid-*/' \
  --include='/plugins/ingrid-*/**' \
  --exclude='/plugins/**' \
  ${WORKSPACE}/user/ %{buildroot}/var/www/%{context_path}/user/
yq -i '.languages.supported = ["de"]' %{system_yaml}
yq -i '.languages.default_lang = "de"' %{system_yaml}
yq -i '.languages.include_default_lang = "false"' %{system_yaml}
yq -i '.pages.theme = "ingrid"' %{system_yaml}
yq -i '.timezone = "Europe/Berlin"' %{system_yaml}
yq -i '.cache.enabled = "true"' %{system_yaml}
#yq -i '.home.alias = env(HOMEPAGE)' %{system_yaml}

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
cd %{buildroot}/var/www/%{context_path}/user/plugins/ingrid-grav && composer update
cd %{buildroot}/var/www/%{context_path}/user/plugins/ingrid-grav-utils && composer update

%files
%defattr(0644,root,root,0755)
%attr(0755,www-data,www-data) /var/www/%{context_path}


%changelog
