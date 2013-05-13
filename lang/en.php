<? // OSM Auth Proxy web interface, English translation. Written by Ilya Zverev, licensed WTFPL
$title = 'OpenStreetMap Authentication Proxy';
$login = 'Login';
$activate = 'Activate account';
$logout = 'Log out';

function print_page( $user, $master, $tokens ) { ?>
<p>You are <i><?=$user['user_name'] ?></i> (<a href="/logout">log out</a>) and <? if( is_null($user['last_use']) ) { ?>you have never used a token<? } else { ?>the last time you have used a token was on <i><?=$user['last_use'] ?> UTC</i><? } ?>.
With this service you will be able to identify yourself in OpenStreetMap-related software that is not able to use OAuth.
All you have to do is pass a token to an application, and it would know your login name and OSM identifier.</p>
<p>There are two types of tokens. A master token can be used repeatedly.
For example, a JOSM plugin can store it in preferences to identify itself to an external service every time you restart the editor.
Tokens are hidden behind a link to disappoint people looking over your shoulder.
If a token has been compromised (that is, someone learned it), just <a href="/newmaster">reissue</a> it<? if( isset($master['token']) ) { ?> (or <a href="/nomaster">disable</a> it altogether)<? } ?>.
<? if( isset($master['last']) ) { ?>This token has been last used on <i><?=$master['last'] ?> UTC</i>.<? } ?></p>
<? if( isset($master['token']) ) { ?>
<div class="copy" id="copym" data-clipboard-text="<?=$master['token'] ?>">Copy</div>
<script language="javascript">attachClipboard('copym');</script>
<div class="hidelink" onclick="javascript:replace(this, 'master');">Show Master Token</div>
<div class="token hidden" id="master"><?=$master['token'] ?></div>
<? } else { ?><div class="token"><i>Token is disabled. <a href="/newmaster">Enable</a></i><? } ?>
<p>If an application is not trustworthy, or you plan to identify yourself only once, or need to share your identification, use a one-time token.
It will expire immediately after having been used. Those tokens are generated in a bunch: you can copy them to a mobile storage and use in trips.
Like a master token, those also can be <a href="/newtokens">reissued</a>, all at once.</p>
<? if( count($tokens) > 0 ) { ?>
<div class="copy" id="copyo" data-clipboard-target="copyosrc">Copy</div>
<textarea id="copyosrc" class="hidden"><?=$tokens[0] ?></textarea>
<script language="javascript">attachClipboard('copyo');</script>
<div class="hidelink" onclick="javascript:replace(this, 'onetime');">Show One-Time Tokens</div>
    <div class="token hidden" id="onetime">
    <? foreach( $tokens as $token ) { ?><div><?=$token ?></div><? } ?></div>
<? } else { ?><div class="token"><i>No tokens left. <a href="/newtokens">Make more</a></i><? } ?>
    </div>
<p>If you consider this service a threat, <a href="/disable">deactivate your account</a>, so no token can be used to identify with your OpenStreetMap account.
You will be able to reactivate it later if you change your mind.</p>
<p>This service is maintained by <a href="http://wiki.openstreetmap.org/wiki/User:Zverik">Ilya Zverev</a>,
sources are published on <a href="https://github.com/Zverik/osm-auth-proxy">github</a>.</p>
<? } ?>
