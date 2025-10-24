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
%define install_root /var/www/%{ingrid-portal-grav}
%define system_yaml %{buildroot}%{install_root}/user/config/system.yaml
%define version_grav 1.7.48
%define version_mvis 2.0.11

################################################################################
%description
InGrid Portal Next Generation

################################################################################
%prep
curl -o grav-admin.zip -SL https://getgrav.org/download/core/grav-admin/%{version_grav}
curl -o mvis.zip -SL https://nexus.informationgrid.eu/repository/maven-public/de/ingrid/measurement-client/%{version_mvis}/measurement-client-%{version_mvis}.zip

################################################################################
%build
# nothing to do

################################################################################
%install
rm -Rf %{buildroot}*

mkdir -p %{buildroot}/var/www

unzip -qq grav-admin.zip -x 'grav-admin/user/pages/*' -d %{buildroot}/var/www/
rm grav-admin.zip
mv %{buildroot}/var/www/grav-admin %{buildroot}%{install_root}

unzip -qq mvis.zip -d %{buildroot}%{install_root}/assets
rm mvis.zip
mv %{buildroot}%{install_root}/assets/measurement-client-%{version_mvis} %{buildroot}%{install_root}/assets/mvis

# COPY OUR ADDITIONAL THEMES AND PLUGINS
mkdir -p %{buildroot}%{install_root}/user
# copy all except non-ingrid plugins
rsync -a \
  --include='/plugins/ingrid-*/' \
  --include='/plugins/ingrid-*/**' \
  --exclude='/plugins/**' \
  ${WORKSPACE}/user/ %{buildroot}%{install_root}/user/

yq -i '.languages.supported = ["de"]' %{system_yaml}
yq -i '.languages.default_lang = "de"' %{system_yaml}
yq -i '.languages.include_default_lang = false' %{system_yaml}
yq -i '.pages.theme = "ingrid"' %{system_yaml}
yq -i '.timezone = "Europe/Berlin"' %{system_yaml}
yq -i '.cache.enabled = true' %{system_yaml}

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
cd %{buildroot}%{install_root}/user/plugins/ingrid-grav && composer update
cd %{buildroot}%{install_root}/user/plugins/ingrid-grav-utils && composer update

################################################################################
%files
%defattr(0644,root,root,0755)
%attr(0755,www-data,www-data) %{install_root}
%config(noreplace) %{install_root}/user/pages

################################################################################
%pre

################################################################################
%preun

################################################################################
%postun

################################################################################
%changelog
