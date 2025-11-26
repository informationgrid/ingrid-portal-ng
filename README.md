# InGrid Portal-NG

<img src="https://ingrid-oss.eu/8.2.0/assets/components/ingrid-plattform.png" alt="InformationGrid illustration" width="480" align="right">

This repository is part of **[InGrid](https://ingrid-oss.eu)**, an open-source solution for building, managing, and exposing metadata-driven information systems. 

**About InGrid Portal-NG:**  
User-friendly search across the InGrid data space and services — catalog browsing, map client, time series — with faceted search, news aggregation, and profile-based customization.

## Development

Just start up the development container and develop your pages, plugins and themes.

```shell
docker compose up -d --build
```

The website will be available on http://localhost:8000 and the admin pages on http://localhost:8000/admin

There's already an administrator configured with the following credentials:
```
Username: admin
Password: admin
```

For theme development, go to the theme directory, e.g. "user/themes/ingrid" and run

```shell
yarn
yarn run dev
```

Any changes made on the styles will be compiled and made available in the portal.

### Recommended Plugins for IntelliJ

* Php Plugin
* Twig
* Symfony Support
  * enable plugin and configure Twig/Template and add namespace:
    * Namespace: <empty>
    * Project-Path: user/themes/ingrid/templates
    * Type: ADD_PATH

### Debugging

In IntelliJ, you need to have the Python plugin installed. Then you need to run `Run -> Start Listening for PHP Debug Connections` and set your breakpoints in your php-files.
