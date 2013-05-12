<? // OSM Auth Proxy tables initialization. Written by Ilya Zverev, licensed WTFPL.
require('config.php');

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
if( $db->connect_errno )
    die('Cannot connect to database: ('.$db->connect_errno.') '.$db->connect_error);
$db->set_charset('utf8');

print("Creating the tables: users");
$db->query('drop table if exists osmauth_users');
$query = <<<CSQL
create table osmauth_users (
    user_id int unsigned not null primary key,
    user_name varchar(200) not null,
    create_date varchar(30) not null,
    languages varchar(100) not null,
    active tinyint(1) not null default 1,
    last_use datetime default null
) Engine=MyISAM DEFAULT CHARACTER SET utf8
CSQL;
$result = $db->query($query);
if( !$result ) {
    print " - failed: ".$db->error."\n";
    exit;
}

print ', tokens';
$db->query('drop table if exists osmauth_tokens');
$query = <<<CSQL
create table osmauth_tokens (
    token varchar(32) not null primary key,
    user_id int unsigned not null,
    last_used datetime default null,
    onetime tinyint(1) not null,
    index(user_id)
) Engine=MyISAM DEFAULT CHARACTER SET utf8
CSQL;
$result = $db->query($query);
if( !$result ) {
    print " - failed: ".$db->error."\n";
    exit;
}
print " OK\n";

?>
