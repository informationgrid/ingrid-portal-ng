# InGrid Portal-NG

## Development

Just start up the development container and develop your pages, plugins and themes.

```shell
docker-compose up -d --build
```

The website will be available on http://localhost:8080 and the admin pages on http://localhost:8080/admin

There's already an administrator configured with the following credentials:
```
Username: admin
Password: admin
```

### Recommended Plugins for IntelliJ

* Php Plugin
* Twig
* Symfony Support
  * enable plugin and configure Twig/Template and add namespace:
    * Namespace: <empty>
    * Project-Path: user/themes/ingrid/templates
    * Type: ADD_PATH

### Debugging

In IntelliJ you need to have the Python plugin installed. Then you need to run `Run -> Start Listening for PHP Debug Connections` and set your breakpoints in your php-files.