# OpenStreetMap Authentication Proxy

The service is extensively described on its main instance, http://auth.osmz.ru/.

## Installation

Copy all files, including `.htaccess`, to a server directory. It would be best to place it at the domain level, or else you'll have to tweak links in `web.php`.

Register the application [in OpenStreetMap](http://www.openstreetmap.org/user/username/oauth_clients/new) and put OAuth and database settings into `config.php`. Then run `createdb.php` and immediately after that delete it.

## Translation

Copy a file you understand in `lang` directory to your language name, for example, `it.php`. And translate it. Don't forget to create pull request.

## License

[ZeroClipboard](https://github.com/jonrohan/ZeroClipboard) was written by Jon Rohan and published under MIT license.

OSM Auth Proxy was written by Ilya Zverev and published under WTFPL license.

