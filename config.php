<? // OSM Auth Proxy configuration file. Written by Ilya Zverev, licensed WTFPL.

// OpenStreetMap OAuth parameters, see https://wiki.openstreetmap.org/wiki/OAuth
const CLIENT_ID     = '';
const CLIENT_SECRET = '';

const AUTHORIZATION_ENDPOINT = 'https://www.openstreetmap.org/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://www.openstreetmap.org/oauth/access_token';
const REQUEST_ENDPOINT       = 'https://www.openstreetmap.org/oauth/request_token';
const OSM_API                = 'https://api.openstreetmap.org/api/0.6/';

// Database credentials
const DB_HOST     = 'localhost';
const DB_USER     = 'zverik';
const DB_PASSWORD = '';
const DB_DATABASE = 'osmauth';

// Miscellaneous
const TOKEN_LENGTH = 10;
const MAX_ONETIME_TOKENS = 5;

?>
