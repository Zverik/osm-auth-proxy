<? // OSM Auth Proxy web interface, English translation. Written by Ilya Zverev, licensed WTFPL
$title = 'Посредник аутентификации OpenStreetMap';
$login = 'Войти';
$activate = 'Активировать сервис';
$logout = 'Выйти';

function print_page( $user, $master, $tokens ) { ?>
<p>Вы <i><?=$user['user_name'] ?></i> (<a href="/logout">выйти</a>), и <? if( is_null($user['last_use']) ) { ?>вы ни разу не воспользовались токеном<? } else { ?>последний раз вы пользовались токеном <i><?=$user['last_use'] ?> UTC</i><? } ?>.
С помощью этого сервиса вы сможете подтвердить свою личность в программах, связанных с OpenStreetMap, которые не умеют работать с OAuth.
Всё, что вам потребуется, &mdash; скопировать токен в приложение, и оно узнает ваше имя и идентификатор в OSM.</p>
<p>Есть два вида токенов. Главный токен можно использовать много раз.
Например, плагин для JOSM может сохранить его в настройках и передавать его для аутентификации внешнему сервису после каждого перезапуска редактора.
Токены спрятаны за ссылками, чтобы разочаровать парня, подглядывающего у вас из-за плеча.
Если токен скомпрометирован (то есть, кто-то его узнал), просто <a href="/newmaster">перевыпустите</a> его<? if( isset($master['token']) ) { ?> (или вообще <a href="/nomaster">отключите</a>)<? } ?>.
<? if( isset($master['last']) ) { ?>Этим токеном в последний раз пользовались <i><?=$master['last'] ?> UTC</i>.<? } ?></p>
<? if( isset($master['token']) ) { ?>
<div class="copy" id="copym" data-clipboard-text="<?=$master['token'] ?>">В буфер</div>
<script language="javascript">attachClipboard('copym');</script>
<div class="hidelink" onclick="javascript:replace(this, 'master');">Показать главный токен</div>
<div class="token hidden" id="master"><?=$master['token'] ?></div>
<? } else { ?><div class="token"><i>Токен отключен. <a href="/newmaster">Включить</a></i><? } ?>
<p>Когда вы не доверяете приложению, или планируете идентифицироваться только раз, или хотите поделиться ключом аутентификации, возьмите одноразовый токен.
Он станет нерабочим сразу после первого использования. Такие токены создаются сразу пачками: можете списать их в блокнот и взять в путешествие.
Как и главный токен, одноразовые можно <a href="/newtokens">перевыпустить</a>, все сразу.</p>
<? if( count($tokens) > 0 ) { ?>
<div class="copy" id="copyo" data-clipboard-target="copyosrc">В буфер</div>
<textarea id="copyosrc" class="hidden"><?=implode("\n", $tokens) ?></textarea>
<script language="javascript">attachClipboard('copyo');</script>
<div class="hidelink" onclick="javascript:replace(this, 'onetime');">Показать одноразовые токены</div>
    <div class="token hidden" id="onetime">
    <? foreach( $tokens as $token ) { ?><div><?=$token ?></div><? } ?></div>
<? } else { ?><div class="token"><i>Токены закончились. <a href="/newtokens">Сделать ещё</a></i><? } ?>
    </div>
<p>Если вы считаете этот сервис вредным, <a href="/disable">отключите учётную запись</a>, чтобы вас было невозможно аутентифицировать по токенам. Если передумаете, запись можно включить обратно.</p>
<p>Сервис поддерживается <a href="http://wiki.openstreetmap.org/wiki/User:Zverik">Ильёй Зверевым</a>,
исходные коды выложены на <a href="https://github.com/Zverik/osm-auth-proxy">github</a>.</p>
<? } ?>
