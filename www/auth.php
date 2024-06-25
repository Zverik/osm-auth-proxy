<?php // OSM Authentication Proxy. Written by Ilya Zverev, licensed WTFPL.
require('config.php');

header('Access-Control-Allow-Origin: *'); // CORS

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
if( $db->connect_errno )
    die('Cannot connect to database: ('.$db->connect_errno.') '.$db->connect_error);
$db->set_charset('utf8');

if( !isset($_REQUEST['token']) || strlen($_REQUEST['token']) == 0 )
    error("Please specify a token to check");
$token = $_REQUEST['token'];
if( !preg_match('/^[a-zA-Z0-9]{'.TOKEN_LENGTH.'}$/', $token ) )
    error('Invalid token format');

$result = $db->query("select user_id from osmauth_tokens where token = '$token' and (onetime = 0 or last_used is null)");
if( !$result )
    error('Database query failed: '.$db->error);
if( $result->num_rows > 1 )
    error('Database consistency problem. Please contact zverik@textual.ru immediately.');
if( $result->num_rows == 0 )
    error('Incorrect token');

$tmp = $result->fetch_row();
$user_id = $tmp[0];
$result->free();

$result = $db->query('select * from osmauth_users where user_id = '.$user_id);
if( !$result )
    error('Database query failed: '.$db->error);
if( $result->num_rows == 0 )
    error('Database consistency problem. Please contact zverik@textual.ru immediately.');
$user = $result->fetch_assoc();
$result->free();

if( !$user['active'] )
    error('Incorrect token');

// update user's and token's last use time
$result = $db->query("update osmauth_tokens set last_used = utc_timestamp() where token = '$token'");
if( !$result )
    error('Could not update token: '.$db->error);
$result = $db->query("update osmauth_users set last_use = utc_timestamp() where user_id = $user_id");
if( !$result )
    error('Could not update user: '.$db->error);

$format = out_fmt();
if( $format == 'json' ) {
    $item = array();
    $item['id'] = $user['user_id'];
    $item['name'] = $user['user_name'];
    $item['created'] = $user['create_date'];
    $item['languages'] = $user['languages'];
    print_json($item);
} elseif( $format == 'xml' ) {
    print xmlheader();
    print '<osm-user id="'.$user['user_id'].'" name="'.htmlspecialchars($user['user_name']).'" created="'.$user['create_date'].'" languages="'.htmlspecialchars($user['languages']).'" />';
} elseif( $format == 'csv' ) {
    print_csv(array($user['user_id'], $user['user_name'], $user['create_date'], $user['languages']));
} elseif( $format == 'text' ) {
    print $user['user_id']."\n".$user['user_name']."\n".$user['create_date']."\n".$user['languages']."\n";
}

// -----------------------------------------------------------------------------

// Prints an error message (respecting output format) and shuts down.
function error( $msg ) {
    $fmt = out_fmt();
    if( $fmt == 'json' ) {
        print_json(array('error' => $msg));
    } elseif ( $fmt == 'xml' ) {
        print xmlheader()."<error>".htmlspecialchars($msg)."</error>\n";
    } elseif ( $fmt == 'csv' ) {
        print_csv(array($msg));
    } else { // text
        print $msg;
    }
    exit;
}

// Returns requested output format.
function out_fmt() {
    $fmt = isset($_REQUEST['format']) ? strtolower($_REQUEST['format']) : '';
    if( $fmt == 'xml' || $fmt == 'json' || $fmt == 'csv' || $fmt == 'text' ) return $fmt;
    return 'text';
}

// Prints json respecting jsonp parameter.
function print_json( $data ) {
    header('Content-type: application/json');
    $str = json_encode($data);
    print isset($_REQUEST['jsonp']) ? $_REQUEST['jsonp']."($str);" : $str;
}

// Prints CSV escaping commas
function print_csv( $array ) {
    header('Content-type: text/csv');
    $first = true;
    foreach( $array as $item ) {
        if( !$first ) print ','; else $first = false;
        $item = str_replace('"', '""', $item);
        print strpos($item, ',') === false && strpos($item, '"') == false ? $item : '"'.$item.'"';
    }
    print "\r\n";
}

// Prints XML header.
function xmlheader() {
    header('Content-type: application/xml');
    return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
}

?>
