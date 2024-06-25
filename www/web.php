<?php // OSM Authentication Proxy web interface. Written by Ilya Zverev, licensed WTFPL
require __DIR__ . '/../vendor/autoload.php';
require('config.php');

$session_lifetime = 365 * 24 * 3600; // a year
session_set_cookie_params($session_lifetime);
session_start([
  'cookie_lifetime' => $session_lifetime,
  'use_only_cookies' => true,
  'use_strict_mode' => true,
]);

header('Content-type: text/html; charset=utf-8');
$user_id = isset($_SESSION['osm_user_id']) ? $_SESSION['osm_user_id'] : 0;
$redirect = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/';

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
if( $db->connect_errno )
    die('Cannot connect to database: ('.$db->connect_errno.') '.$db->connect_error);
$db->set_charset('utf8');

function oauth_make() {
  global $redirect;
  return new \JBelien\OAuth2\Client\Provider\OpenStreetMap([
      'clientId'     => CLIENT_ID,
      'clientSecret' => CLIENT_SECRET,
      'redirectUri'  => $redirect.'oauth',
      'dev'          => false
  ]);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
if( $action == 'login' ) {
    $oauth = oauth_make();
    $options = ['scope' => 'read_prefs'];
    $auth_url = $oauth->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $oauth->getState();
    header('Location: '.$auth_url);
    exit;
} elseif( $action == 'oauth' ) {
    if(empty($_GET['code'])) {
        error("Error: there is no OAuth code.");
    } elseif(empty($_SESSION['oauth2state'])) {
        error("Error: there is no OAuth state.");
    } elseif(empty($_GET['state']) || $_GET['state'] != $_SESSION['oauth2state']) {
        error("Error: invalid state.");
    } else {
        unset($_SESSION['oauth2state']);
        $oauth = oauth_make();
        $accessToken = $oauth->getAccessToken(
          'authorization_code', ['code' => $_GET['code']]
        );
        $resourceOwner = $oauth->getResourceOwner($accessToken);

        $user_id = intval($resourceOwner->getId());
        if( !$user_id )
            error('OSM API returned no user id');
        $user_name = $db->escape_string($resourceOwner->getDisplayName());
        $created = $db->escape_string($resourceOwner->getAccountCreated());

        $langs_a = $resourceOwner->getLanguages();
        $langs = $db->escape_string(implode(',', $langs_a));

        $_SESSION['osm_user_id'] = strval($user_id);

        $res = equery($db, 'select user_name, languages from osmauth_users where user_id = '.$user_id);
        if( $res->num_rows == 0 ) {
            // register user
            equery($db, "insert into osmauth_users (user_id, user_name, create_date, languages) values ($user_id, '$user_name', '$created', '$langs')");
            // create a master key
            generate_token($db, $user_id, 0);
            // and one-time keys
            for( $i = 0; $i < MAX_ONETIME_TOKENS; $i++ )
                generate_token($db, $user_id, 1);
        } else {
            equery($db, "update osmauth_users set user_name = '$user_name', languages = '$langs' where user_id = $user_id");
        }
        $res->free();
    }
} elseif( $user_id ) {
    if( $action == 'logout' ) {
        unset($_SESSION['osm_user_id']);
    } elseif( $action == 'newmaster' ) {
        delete_master_tokens($db, $user_id);
        generate_token($db, $user_id, 0);
    } elseif( $action == 'newtokens' ) {
        // delete old unsused tokens
        equery($db, 'delete from osmauth_tokens where onetime = 1 and last_used is null and user_id = '.$user_id);
        for( $i = 0; $i < MAX_ONETIME_TOKENS; $i++ )
            generate_token($db, $user_id, 1);
    } elseif( $action == 'nomaster' ) {
        delete_master_tokens($db, $user_id);
    } elseif( $action == 'disable' ) {
        equery($db, 'update osmauth_users set active = 0 where user_id = '.$user_id);
    } elseif( $action == 'enable' ) {
        equery($db, 'update osmauth_users set active = 1 where user_id = '.$user_id);
    }
}

if( strlen($action) > 0 ) {
    header("Location: $redirect");
    exit;
}

function equery($db, $query) {
    $res = $db->query($query);
    if( !$res )
        error('Database error: '.$db->error);
    return $res;
}

function delete_master_tokens( $db, $user_id ) {
    equery($db, 'delete from osmauth_tokens where onetime = 0 and last_used is null and user_id = '.$user_id);
    equery($db, 'update osmauth_tokens set onetime = 2 where onetime = 0 and user_id = '.$user_id);
}

function generate_token( $db, $user_id, $onetime ) {
    $chars = '23456789abcdefghijklmnopqrstuvwxyz';
    $token = '';
    $try = 0;
    while( strlen($token) == 0 && $try++ < 10 ) {
        for( $i = 0; $i < TOKEN_LENGTH; $i++ )
            $token .= $chars[rand(0, strlen($chars) - 1)];
        $result = equery($db, "select user_id from osmauth_tokens where token = '$token'");
        if( $result->num_rows > 0 )
            $token = '';
    }
    if( strlen($token) == 0 )
        error('Could not generate a unique token in '.$try.' tries');
    $ot = $onetime ? 1 : 0;
    equery($db, "insert into osmauth_tokens (user_id, token, onetime) values($user_id, '$token', $ot)");
}

$accept = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
if( $user_id ) {
    $result = equery($db, 'select * from osmauth_users where user_id = '.$user_id);
    $user = $result->fetch_assoc();
    $result->free;
    $accept = $user['languages'].','.$accept;
}

// determine user language
$found = false;
foreach( explode(',', $accept) as $lang ) {
    $p = strpos($lang, ';');
    if( $p !== false )
        $lang = substr($lang, 0, $p);
    if( file_exists("lang/$lang.php") ) {
        include("lang/$lang.php");
        $found = true;
        break;
    }
}
if( !$found )
    include('lang/en.php');

?><html>
<head>
<title><?=$title ?></title>
<script src="ZeroClipboard.min.js"></script>
<script>
function replace( link, tokenId ) {
    link.style.display = 'none';
    document.getElementById(tokenId).style.display = 'block';
}
function attachClipboard( divId ) {
    var div = document.getElementById(divId);
    var clip = new ZeroClipboard(div);
/*    clip.on('complete', function() {
        div.style.backgroundColor = '#efe';
        setTimeout(function() { div.style.backgroundColor = '#eee'; }, 1000);
});*/
}
</script>
<style>
body {
    /* font-family: Verdana, sans-serif; */
    font-size: 12pt;
    margin: 10;
    width: 700px;
    margin: 40px auto;
}
h1 {
    font-size: 18pt;
    text-align: center;
}
.button {
    background-color: green;
    border-radius: 10px;
    /*border: #070 1px solid;*/
    padding: 20px 40px;
    margin: 0 10px;
    color: white;
    font-weight: bold;
    display: inline-block;
}
.button:hover {
    background-color: #0a0;
}
.button a {
    color: white;
    text-decoration: none;
}
#login {
    margin-top: 200px;
    text-align: center;
    font-size: 16pt;
}
.token {
    text-align: center;
    font-size: 16pt;
    margin: 0 150px;
    padding: 20px 50px;
    border-radius: 10px;
    background-color: #eee;
}
.hidden {
    display: none;
}
.hidelink {
    text-align: center;
    font-size: 16pt;
    color: green;
    text-decoration: underline;
    cursor: pointer;
}
.copy {
    float: right;
    background-color: #eee;
    padding: 4px;
    cursor: pointer;
    border-radius: 5px;
}
</style>
</head>
<body>
<h1><?=$title ?></h1>
<?php if( !$user_id ) { ?>
<div id="login"><a href="/login" class="button"><?=$login ?></a></div>
<?php } else {
    // we initialized user array before <html>
    if( !$user['active'] ) { ?>
<div id="login">
<a class="button" href="/enable"><?=$activate ?></a> <a class="button" href="/logout"><?=$logout ?></a>
</div>
<?php  } else {
    $result = equery($db, 'select token, onetime, last_used from osmauth_tokens where (onetime = 0 or last_used is null) and user_id = '.$user_id);
    $tokens = array();
    $master = array();
    while( $row = $result->fetch_assoc() ) {
        if( $row['onetime'] > 0 )
            $tokens[] = $row['token'];
        else {
            $master['token'] = $row['token'];
            $master['last'] = $row['last_used'];
        }
    }
    $result->free;
    print_page($user, $master, $tokens);
  } } ?>
</body>
</html>
