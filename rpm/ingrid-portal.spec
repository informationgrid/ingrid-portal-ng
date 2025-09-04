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

%define context_path ingrid-portal-grav
%define system_yaml %{buildroot}/var/www/%{context_path}/user/config/system.yaml

################################################################################
%description
InGrid Portal Next Generation

################################################################################
%prep
GRAV_VERSION=1.7.48
curl -o grav-admin.zip -SL https://getgrav.org/download/core/grav-admin/${GRAV_VERSION}

MVIS_VERSION=2.0.11
curl -o mvis.zip -SL https://nexus.informationgrid.eu/repository/maven-public/de/ingrid/measurement-client/${MVIS_VERSION}/measurement-client-${MVIS_VERSION}.zip

################################################################################
%build
# nothing to do

################################################################################
%install
rm -Rf %{buildroot}*

mkdir -p %{buildroot}/var/www

unzip -qq grav-admin.zip -d %{buildroot}/var/www/
rm grav-admin.zip
mv %{buildroot}/var/www/grav-admin %{buildroot}/var/www/%{context_path}

unzip -qq mvis.zip -d %{buildroot}/var/www/%{context_path}/assets
rm mvis.zip
mv %{buildroot}/var/www/%{context_path}/assets/measurement-client-${MVIS_VERSION} %{buildroot}/var/www/%{context_path}/assets/mvis

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
yq -i '.languages.include_default_lang = false' %{system_yaml}
yq -i '.pages.theme = "ingrid"' %{system_yaml}
yq -i '.timezone = "Europe/Berlin"' %{system_yaml}
yq -i '.cache.enabled = true' %{system_yaml}

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
cd %{buildroot}/var/www/%{context_path}/user/plugins/ingrid-grav && composer update
cd %{buildroot}/var/www/%{context_path}/user/plugins/ingrid-grav-utils && composer update

################################################################################
%files
%defattr(0644,root,root,0755)
%attr(0755,www-data,www-data) /var/www/%{context_path}

################################################################################
%changelog
