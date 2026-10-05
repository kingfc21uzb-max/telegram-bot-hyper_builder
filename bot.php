<?php
/*
 ╔══════════════════════════════════════════════════════════════╗
 ║  HYPER BUILDER — Telegram bot konstruktor platformasi        ║
 ║  Yagona fayl · PHP 8.0+ · SQLite · Webhook · 100% oʻzbekcha  ║
 ╚══════════════════════════════════════════════════════════════╝
 TALABLAR: PHP 8.0+, ext: curl, pdo_sqlite, gd, mbstring. HTTPS domen shart.
 ISHGA TUSHIRISH: faylning oxiridagi sozlamalarni toʻldiring, soʻng brauzerda:
   https://DOMEN/bot.php?setup=SETUP_KEY
*/
error_reporting(E_ALL);
@ini_set('display_errors', '0');
date_default_timezone_set('Asia/Tashkent');

const BOT_TYPES = [
 'builder' => ['🏗 Builder Bot', 'Ichma-ich menyular va tugmalar yaratuvchi bot'],
 'smm'     => ['📈 SMM Bot (Nakrutka)', 'Ijtimoiy tarmoq xizmatlari sotuvchi panel'],
 'nomer'   => ['☎️ Virtual Nomer Bot', 'Virtual telefon raqamlarini sotish va boshqarish'],
 'stars'   => ['⭐️ Stars Premium Bot', 'Telegram Stars orqali raqamli mahsulot sotish'],
 'kino'    => ['🎬 Kino Bot', 'Kod orqali kino qidirish va yuborish'],
 'kinopro' => ['🎞 Kino Bot Pro', 'Statistika, majburiy obuna, saqlanganlar va avto-eʼlon'],
 'emoji'   => ['😎 Emoji Yaratuvchi Bot', 'Rasmdan maxsus emoji va stikerlar yasaydi'],
 'anon'    => ['🕵️ Anonim Chat Bot', 'Tasodifiy suhbatdosh bilan anonim yozishish'],
 'ai'      => ['🤖 AI Bot', 'OpenAI / Claude asosidagi suhbat boti'],
 'obuna'   => ['📢 Obunachi Bot', 'Majburiy obuna va obunachilarni kuzatish'],
];

const TX = [
 'start'    => "👋 <b>Assalomu alaykum, {ism}!</b>\n\n🚀 <b>HYPER BUILDER</b> — oʻz Telegram botingizni bir necha daqiqada yarating.\n\nQuyidagi menyudan kerakli boʻlimni tanlang.",
 'menu'     => '🏠 Asosiy menyu',
 'b_create' => '🤖 Bot yaratish',
 'b_mybots' => '📂 Botlarim',
 'b_topup'  => '💳 Hisobni toʻldirish',
 'b_balance'=> '👤 Hisobim',
 'b_ref'    => '👥 Referal',
 'b_help'   => '☎️ Yordam',
 'b_admin'  => '⚙️ Admin panel',
 'cancel'   => '❌ Bekor qilish',
 'banned'   => '🚫 Siz bloklangansiz.',
 'sub_need' => '📢 Botdan foydalanish uchun quyidagi kanallarga aʼzo boʻling, soʻng «Tekshirish» tugmasini bosing:',
 'ref_got'  => "🎉 Sizning havolangiz orqali <b>{ism}</b> qoʻshildi!\n💰 Hisobingizga <b>{sum}</b> qoʻshildi.",
 'help'     => "☎️ <b>Yordam</b>\n\nSavol va takliflar boʻyicha {admin} bilan bogʻlaning.",
 'ref_text' => "👥 <b>Referal tizimi</b>\n\nHar bir taklif qilingan doʻstingiz uchun <b>{sum}</b> olasiz.\n\n🔗 Sizning havolangiz:\n<code>{link}</code>\n\n👤 Taklif qilinganlar: <b>{n}</b> ta",
 'topup_in' => "💳 <b>Hisobni toʻldirish</b>\n\nQuyidagi kartalardan biriga pul oʻtkazing:\n\n{cards}\n\n✍️ Oʻtkazgan <b>summangizni</b> yuboring (faqat raqam):",
 'topup_ok' => '✅ Chek qabul qilindi. Admin tekshirgach, hisobingiz toʻldiriladi.',
];

$CFG = [];
function cfg($k) { global $CFG; return $CFG[$k] ?? null; }

/* ───────────────────────── Yordamchi funksiyalar ───────────────────────── */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function nf($n) { return number_format((float)$n, 0, '.', ' ') . ' soʻm'; }
function now() { return date('Y-m-d H:i:s'); }
function today() { return date('Y-m-d'); }
function sig($x) { return substr(hash_hmac('sha256', (string)$x, (string)cfg('token')), 0, 24); }

/* ───────────────────────── Maʼlumotlar bazasi ───────────────────────── */
function db() {
 static $d = null;
 if (!$d) {
  $d = new PDO('sqlite:' . cfg('db'));
  $d->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $d->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $d->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=8000;');
  migrate($d);
 }
 return $d;
}
function q($s, $a = []) { $st = db()->prepare($s); $st->execute($a); return $st; }
function row($s, $a = []) { $r = q($s, $a)->fetch(); return $r ?: null; }
function rows($s, $a = []) { return q($s, $a)->fetchAll(); }
function val($s, $a = []) { $r = q($s, $a)->fetchColumn(); return $r === false ? null : $r; }
function ins($s, $a = []) { q($s, $a); return (int)db()->lastInsertId(); }

function migrate($d) {
 $d->exec("
 CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,name TEXT,username TEXT,balance REAL DEFAULT 0,ref_by INTEGER,refs INTEGER DEFAULT 0,state TEXT DEFAULT '',data TEXT DEFAULT '{}',banned INTEGER DEFAULT 0,created TEXT);
 CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY,v TEXT);
 CREATE TABLE IF NOT EXISTS texts(k TEXT PRIMARY KEY,v TEXT);
 CREATE TABLE IF NOT EXISTS admins(id INTEGER PRIMARY KEY,added_by INTEGER);
 CREATE TABLE IF NOT EXISTS bots(id INTEGER PRIMARY KEY AUTOINCREMENT,owner INTEGER,type TEXT,name TEXT,token TEXT,username TEXT,tariff_id INTEGER,expires TEXT,active INTEGER DEFAULT 1,cfg TEXT DEFAULT '{}',created TEXT);
 CREATE TABLE IF NOT EXISTS templates(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,lang TEXT,path TEXT,fid TEXT,added TEXT);
 CREATE TABLE IF NOT EXISTS tariffs(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,days INTEGER,price REAL,daily_limit INTEGER DEFAULT 0);
 CREATE TABLE IF NOT EXISTS payments(id INTEGER PRIMARY KEY AUTOINCREMENT,user INTEGER,amount REAL,status TEXT,fid TEXT,kind TEXT,created TEXT);
 CREATE TABLE IF NOT EXISTS cards(id INTEGER PRIMARY KEY AUTOINCREMENT,number TEXT,holder TEXT);
 CREATE TABLE IF NOT EXISTS channels(id INTEGER PRIMARY KEY AUTOINCREMENT,chat TEXT,title TEXT,link TEXT);
 CREATE TABLE IF NOT EXISTS bu(bot_id INTEGER,uid INTEGER,name TEXT,bal REAL DEFAULT 0,state TEXT DEFAULT '',data TEXT DEFAULT '{}',partner INTEGER DEFAULT 0,waiting INTEGER DEFAULT 0,verified INTEGER DEFAULT 0,joined TEXT,PRIMARY KEY(bot_id,uid));
 CREATE TABLE IF NOT EXISTS daily(bot_id INTEGER,uid INTEGER,day TEXT,PRIMARY KEY(bot_id,uid,day));
 CREATE TABLE IF NOT EXISTS menus(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,parent INTEGER,title TEXT,body TEXT);
 CREATE TABLE IF NOT EXISTS services(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,name TEXT,price REAL,sid TEXT,min INTEGER,max INTEGER);
 CREATE TABLE IF NOT EXISTS orders(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,uid INTEGER,service_id INTEGER,link TEXT,qty INTEGER,cost REAL,api_id TEXT,status TEXT,created TEXT);
 CREATE TABLE IF NOT EXISTS numbers(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,country TEXT,num TEXT,price REAL,status TEXT DEFAULT 'bor',buyer INTEGER DEFAULT 0,code TEXT DEFAULT '');
 CREATE TABLE IF NOT EXISTS products(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,title TEXT,stars INTEGER,delivery TEXT);
 CREATE TABLE IF NOT EXISTS sales(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,uid INTEGER,product_id INTEGER,stars INTEGER,created TEXT);
 CREATE TABLE IF NOT EXISTS movies(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,code TEXT,file_id TEXT,kind TEXT,title TEXT,views INTEGER DEFAULT 0,created TEXT,UNIQUE(bot_id,code));
 CREATE TABLE IF NOT EXISTS favs(bot_id INTEGER,uid INTEGER,movie_id INTEGER,PRIMARY KEY(bot_id,uid,movie_id));
 CREATE TABLE IF NOT EXISTS bch(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,chat TEXT,title TEXT,link TEXT);
 CREATE TABLE IF NOT EXISTS sublog(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,uid INTEGER,chat TEXT,ev TEXT,created TEXT);
 CREATE TABLE IF NOT EXISTS aihist(id INTEGER PRIMARY KEY AUTOINCREMENT,bot_id INTEGER,uid INTEGER,role TEXT,content TEXT);
 ");
 if (!(int)$d->query('SELECT COUNT(*) FROM tariffs')->fetchColumn()) {
  $d->exec("INSERT INTO tariffs(name,days,price,daily_limit) VALUES('Boshlangʻich',30,10000,1000),('Standart',90,25000,5000),('Premium',365,80000,0)");
 }
}
function gs($k, $d = null) { $v = val('SELECT v FROM settings WHERE k=?', [$k]); return $v === null ? $d : $v; }
function ss($k, $v) { q('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v', [$k, (string)$v]); }
function t($k, $r = []) {
 $v = val('SELECT v FROM texts WHERE k=?', [$k]) ?? (TX[$k] ?? $k);
 foreach ($r as $a => $b) $v = str_replace('{' . $a . '}', (string)$b, $v);
 return $v;
}

/* ───────────────────────── Telegram API ───────────────────────── */
function api($tok, $m, $p = [], $files = []) {
 $ch = curl_init("https://api.telegram.org/bot$tok/$m");
 if ($files) {
  foreach ($p as $k => $v) if (is_array($v)) $p[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
  foreach ($files as $k => $f) $p[$k] = new CURLFile($f);
  $post = $p; $hdr = [];
 } else { $post = json_encode($p, JSON_UNESCAPED_UNICODE); $hdr = ['Content-Type: application/json']; }
 curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_HTTPHEADER => $hdr, CURLOPT_TIMEOUT => 60]);
 $r = curl_exec($ch); curl_close($ch);
 $j = json_decode((string)$r, true);
 return is_array($j) ? $j : ['ok' => false, 'description' => 'tarmoq xatosi'];
}
function send($tok, $chat, $text, $kb = null, $e = []) {
 $p = ['chat_id' => $chat, 'text' => $text, 'disable_web_page_preview' => true] + $e;
 if (!isset($p['plain'])) $p['parse_mode'] = 'HTML';
 unset($p['plain']);
 if ($kb) $p['reply_markup'] = $kb;
 return api($tok, 'sendMessage', $p);
}
function edit($tok, $chat, $mid, $text, $kb = null) {
 $p = ['chat_id' => $chat, 'message_id' => $mid, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
 if ($kb) $p['reply_markup'] = $kb;
 $r = api($tok, 'editMessageText', $p);
 if (!($r['ok'] ?? false) && stripos($r['description'] ?? '', 'not modified') === false) send($tok, $chat, $text, $kb);
}
function dl($tok, $fid, $to) {
 $r = api($tok, 'getFile', ['file_id' => $fid]);
 if (!($r['ok'] ?? false)) return false;
 $bin = @file_get_contents('https://api.telegram.org/file/bot' . $tok . '/' . $r['result']['file_path']);
 return $bin === false ? false : (file_put_contents($to, $bin) !== false);
}

/* ───────────────────────── Klaviaturalar ───────────────────────── */
function B($t, $d, $x = []) {
 $b = ['text' => $t, 'callback_data' => $d] + $x;
 $s = gs('btn_style');
 if ($s && in_array($s, ['primary', 'success', 'danger'])) $b['style'] = $s;
 $i = gs('icon:' . $d);
 if ($i) $b['icon_custom_emoji_id'] = $i;
 return $b;
}
function U($t, $u) { return ['text' => $t, 'url' => $u]; }
function IK($rows) { return ['inline_keyboard' => $rows]; }
function RK($rows) {
 $s = gs('btn_style');
 $rows = array_map(fn($r) => array_map(function ($b) use ($s) {
  $b = is_array($b) ? $b : ['text' => $b];
  if ($s && in_array($s, ['primary', 'success', 'danger'])) $b['style'] = $s;
  return $b;
 }, $r), $rows);
 return ['keyboard' => $rows, 'resize_keyboard' => true];
}
function cancelKb() { return RK([[t('cancel')]]); }
function mainKb($uid) {
 $r = [[t('b_create'), t('b_mybots')], [t('b_topup'), t('b_balance')], [t('b_ref'), t('b_help')]];
 if (isAdmin($uid)) $r[] = [t('b_admin')];
 return RK($r);
}

/* ───────────────────────── Foydalanuvchi / admin ───────────────────────── */
function isMain($id) { return (int)$id === (int)cfg('admin'); }
function isAdmin($id) { return isMain($id) || (bool)val('SELECT 1 FROM admins WHERE id=?', [(int)$id]); }
function adminIds() { return array_unique(array_merge([(int)cfg('admin')], array_map('intval', array_column(rows('SELECT id FROM admins'), 'id')))); }
function user($f, $ref = null) {
 $id = (int)$f['id'];
 $name = trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
 $u = row('SELECT * FROM users WHERE id=?', [$id]);
 if (!$u) {
  $rb = ($ref && $ref !== $id && val('SELECT 1 FROM users WHERE id=?', [$ref])) ? $ref : null;
  q('INSERT INTO users(id,name,username,ref_by,created) VALUES(?,?,?,?,?)', [$id, $name, $f['username'] ?? '', $rb, now()]);
  if ($rb) {
   $r = (float)gs('ref_reward', '1000');
   q('UPDATE users SET balance=balance+?,refs=refs+1 WHERE id=?', [$r, $rb]);
   send(cfg('token'), $rb, t('ref_got', ['sum' => nf($r), 'ism' => h($name)]));
  }
  $u = row('SELECT * FROM users WHERE id=?', [$id]);
 } else q('UPDATE users SET name=?,username=? WHERE id=?', [$name, $f['username'] ?? '', $id]);
 return $u;
}
function setSt($uid, $st, $data = []) { q('UPDATE users SET state=?,data=? WHERE id=?', [$st, json_encode($data, JSON_UNESCAPED_UNICODE), $uid]); }
function ask($uid, $st, $data, $text) { setSt($uid, $st, $data); send(cfg('token'), $uid, $text, cancelKb()); }
function notifyAdmins($text, $kb = null) { foreach (adminIds() as $a) send(cfg('token'), $a, $text, $kb); }

function missing($tok, $uid, $chans) {
 $m = [];
 foreach ($chans as $c) {
  $r = api($tok, 'getChatMember', ['chat_id' => $c['chat'], 'user_id' => $uid]);
  if (($r['ok'] ?? false) && in_array($r['result']['status'] ?? '', ['left', 'kicked'])) $m[] = $c;
 }
 return $m;
}
function chanUrl($c) { return !empty($c['link']) ? $c['link'] : 'https://t.me/' . ltrim($c['chat'], '@'); }
function gate($uid) {
 if (isAdmin($uid)) return true;
 $ch = rows('SELECT * FROM channels');
 if (!$ch) return true;
 $m = missing(cfg('token'), $uid, $ch);
 if (!$m) return true;
 $kb = [];
 foreach ($m as $c) $kb[] = [U('📢 ' . ($c['title'] ?: $c['chat']), chanUrl($c))];
 $kb[] = [B('✅ Tekshirish', 'chk')];
 send(cfg('token'), $uid, t('sub_need'), IK($kb));
 return false;
}

/* ───────────────────────── Yordam matnlari (bot egasi uchun) ───────────────────────── */
const HELP = [
 'builder' => "🏗 <b>Builder Bot buyruqlari</b>\n/qosh ota_id|Sarlavha|Matn — boʻlim qoʻshish (asosiy menyu uchun ota_id = 0)\n/ochir id — boʻlimni oʻchirish\n/royxat — boʻlimlar daraxti\n/salom matn — boshlangʻich matn",
 'smm' => "📈 <b>SMM Bot buyruqlari</b>\n/xizmat Nom|1000 ta narxi|service_id|min|maks\n/xizmatochir id\n/xizmatlar — roʻyxat\n/api url|kalit — SMM panel API ulash\n/balans user_id summa — mijoz balansini oʻzgartirish\n/aloqa matn — toʻldirish uchun aloqa matni",
 'nomer' => "☎️ <b>Virtual Nomer Bot buyruqlari</b>\n/nomer Mamlakat|+99890...|narx\n/nomerochir id\n/nomerlar — roʻyxat\n/kod id kod — xaridorga SMS kodini yuborish\n/balans user_id summa\n/aloqa matn",
 'stars' => "⭐️ <b>Stars Premium Bot buyruqlari</b>\n/mahsulot Nom|stars|yetkazish matni\n/mahsulotochir id\n/mahsulotlar — roʻyxat\n/sotuvlar — oxirgi sotuvlar",
 'kino' => "🎬 <b>Kino Bot</b>\nKino qoʻshish: videoni yuboring, izohiga <code>kod | Nomi</code> yozing (faqat nom yozsangiz kod avtomatik beriladi).\n/kinoochir kod\n/kinolar — soni",
 'kinopro' => "🎞 <b>Kino Bot Pro</b>\nKino qoʻshish: videoni yuboring, izohiga <code>kod | Nomi</code> yozing. Yangi kino hamma foydalanuvchiga avtomatik eʼlon qilinadi.\n/kinoochir kod\n/kanal @kanal — majburiy obuna\n/kanalochir @kanal\n/kanallar\n/stat — statistika",
 'emoji' => "😎 <b>Emoji Bot</b>\nSozlama kerak emas. Foydalanuvchilar rasm yuboradi, bot emoji/stiker toʻplami yaratadi.",
 'anon' => "🕵️ <b>Anonim Chat</b>\nSozlama kerak emas. /stat — faol suhbatlar soni.",
 'ai' => "🤖 <b>AI Bot buyruqlari</b>\n/kalit API_KALIT\n/provayder claude|openai\n/model model_nomi\n/prompt tizim koʻrsatmasi",
 'obuna' => "📢 <b>Obunachi Bot buyruqlari</b>\n/kanal @kanal [Sarlavha] — majburiy kanal (bot kanalda admin boʻlishi shart)\n/kanalochir @kanal\n/kanallar\n/xabar matn — obuna tasdiqlangach chiqadigan xabar\n/stat — obuna statistikasi",
];

/* ═════════════════════════ PLATFORMA (asosiy bot) ═════════════════════════ */
function P($u) {
 $tok = cfg('token');
 if (isset($u['callback_query'])) {
  $c = $u['callback_query'];
  api($tok, 'answerCallbackQuery', ['callback_query_id' => $c['id']]);
  pcb($c);
  return;
 }
 if (isset($u['message']) && ($u['message']['chat']['type'] ?? '') === 'private') pmsg($u['message']);
}

function pmsg($m) {
 $tok = cfg('token'); $f = $m['from']; $uid = (int)$f['id']; $txt = trim($m['text'] ?? '');
 if (preg_match('~^/start(?:\s+(\d+))?~', $txt, $mm)) {
  $u = user($f, isset($mm[1]) ? (int)$mm[1] : null);
  setSt($uid, '');
  if ($u['banned']) { send($tok, $uid, t('banned')); return; }
  if (!gate($uid)) return;
  send($tok, $uid, t('start', ['ism' => h($f['first_name'] ?? '')]), mainKb($uid));
  return;
 }
 $u = user($f);
 if ($u['banned']) { send($tok, $uid, t('banned')); return; }
 if (!gate($uid)) return;
 $menus = [t('b_create'), t('b_mybots'), t('b_topup'), t('b_balance'), t('b_ref'), t('b_help'), t('b_admin'), t('cancel'), '/bekor'];
 if (in_array($txt, $menus, true) && $u['state'] !== '') { setSt($uid, ''); $u['state'] = ''; if ($txt === t('cancel') || $txt === '/bekor') { send($tok, $uid, t('menu'), mainKb($uid)); return; } }
 if ($u['state'] !== '' && pstate($u, $m, $txt)) return;
 if ($txt === t('cancel') || $txt === '/bekor') { send($tok, $uid, t('menu'), mainKb($uid)); return; }
 if ($txt === t('b_create')) { send($tok, $uid, "🤖 <b>Qaysi turdagi bot yaratmoqchisiz?</b>", typesKb()); return; }
 if ($txt === t('b_mybots')) { myBots($uid, $uid, null); return; }
 if ($txt === t('b_topup')) {
  $cards = rows('SELECT * FROM cards');
  if (!$cards) { send($tok, $uid, '⚠️ Hozircha toʻlov usullari qoʻshilmagan.'); return; }
  $c = ''; foreach ($cards as $k) $c .= "💳 <code>" . h($k['number']) . "</code>\n👤 " . h($k['holder']) . "\n\n";
  ask($uid, 'tp_amount', [], t('topup_in', ['cards' => $c]));
  return;
 }
 if ($txt === t('b_balance')) {
  $u = row('SELECT * FROM users WHERE id=?', [$uid]);
  $s = "👤 <b>Hisobim</b>\n\n🆔 ID: <code>$uid</code>\n💰 Balans: <b>" . nf($u['balance']) . "</b>\n🤖 Botlar: <b>" . val('SELECT COUNT(*) FROM bots WHERE owner=?', [$uid]) . "</b> ta\n\n📜 <b>Soʻnggi toʻlovlar:</b>\n";
  $ps = rows('SELECT * FROM payments WHERE user=? ORDER BY id DESC LIMIT 10', [$uid]);
  if (!$ps) $s .= "— Hozircha yoʻq";
  foreach ($ps as $p) $s .= "• " . substr($p['created'], 0, 16) . " — " . nf($p['amount']) . " — " . $p['status'] . "\n";
  send($tok, $uid, $s);
  return;
 }
 if ($txt === t('b_ref')) {
  $u = row('SELECT * FROM users WHERE id=?', [$uid]);
  send($tok, $uid, t('ref_text', ['sum' => nf(gs('ref_reward', '1000')), 'link' => 'https://t.me/' . botUser() . '?start=' . $uid, 'n' => $u['refs']]));
  return;
 }
 if ($txt === t('b_help')) { send($tok, $uid, t('help', ['admin' => '<a href="tg://user?id=' . cfg('admin') . '">admin</a>'])); return; }
 if ($txt === t('b_admin') && isAdmin($uid)) { send($tok, $uid, "⚙️ <b>Admin panel</b>", adminKb($uid)); return; }
 send($tok, $uid, t('menu'), mainKb($uid));
}
function botUser() {
 $u = gs('bot_username');
 if (!$u) { $r = api(cfg('token'), 'getMe'); $u = $r['result']['username'] ?? 'bot'; ss('bot_username', $u); }
 return $u;
}
function typesKb() {
 $kb = [];
 foreach (BOT_TYPES as $k => $v) $kb[] = [B($v[0], 'ct:' . $k)];
 return IK($kb);
}
function hook($b) {
 return api($b['token'], 'setWebhook', ['url' => cfg('url') . '?b=' . $b['id'], 'secret_token' => sig('c' . $b['id']),
  'allowed_updates' => ['message', 'callback_query', 'chat_member', 'my_chat_member', 'pre_checkout_query'], 'drop_pending_updates' => true]);
}
function myBot($uid, $id) {
 $b = row('SELECT * FROM bots WHERE id=?', [$id]);
 return ($b && ((int)$b['owner'] === (int)$uid || isAdmin($uid))) ? $b : null;
}
function myBots($uid, $cid, $mid) {
 $tok = cfg('token'); $kb = [];
 foreach (rows('SELECT * FROM bots WHERE owner=? ORDER BY id DESC', [$uid]) as $b)
  $kb[] = [B('@' . $b['username'] . ' · ' . BOT_TYPES[$b['type']][0], 'bt:' . $b['id'])];
 if (!$kb) { $s = "📂 Sizda hali bot yoʻq. «" . t('b_create') . "» tugmasi orqali yarating."; $mid ? edit($tok, $cid, $mid, $s) : send($tok, $cid, $s); return; }
 $s = "📂 <b>Botlarim</b>\nBotni tanlang:";
 $mid ? edit($tok, $cid, $mid, $s, IK($kb)) : send($tok, $cid, $s, IK($kb));
}
function botInfo($b) {
 $tr = row('SELECT * FROM tariffs WHERE id=?', [$b['tariff_id']]);
 $exp = strtotime($b['expires']) < time() ? '⛔️ tugagan' : substr($b['expires'], 0, 10);
 return "🤖 <b>@" . h($b['username']) . "</b> (#{$b['id']})\n🏷 Turi: " . BOT_TYPES[$b['type']][0] . "\n📦 Tarif: " . h($tr['name'] ?? '—') . "\n📊 Kunlik limit: " . (($tr['daily_limit'] ?? 0) ?: 'cheksiz') . "\n⏳ Amal qilish muddati: $exp\n👤 Egasi: <code>{$b['owner']}</code>";
}
function delBot($id) {
 $b = row('SELECT * FROM bots WHERE id=?', [$id]);
 if (!$b) return;
 api($b['token'], 'deleteWebhook');
 foreach (['bu', 'daily', 'menus', 'services', 'orders', 'numbers', 'products', 'sales', 'movies', 'favs', 'bch', 'sublog', 'aihist'] as $tb) q("DELETE FROM $tb WHERE bot_id=?", [$id]);
 q('DELETE FROM bots WHERE id=?', [$id]);
}

function pcb($c) {
 $tok = cfg('token'); $f = $c['from']; $uid = (int)$f['id']; $cid = $c['message']['chat']['id']; $mid = $c['message']['message_id'];
 $d = $c['data'] ?? ''; $p = explode(':', $d);
 $u = user($f);
 if ($u['banned']) return;
 if ($p[0] === 'chk') { if (gate($uid)) send($tok, $uid, t('start', ['ism' => h($f['first_name'] ?? '')]), mainKb($uid)); return; }
 if (!gate($uid)) return;
 $E = fn($tx, $kb = null) => edit($tok, $cid, $mid, $tx, $kb);
 switch ($p[0]) {
  case 'cr': $E("🤖 <b>Qaysi turdagi bot yaratmoqchisiz?</b>", typesKb()); return;
  case 'ct':
   $k = $p[1] ?? ''; if (!isset(BOT_TYPES[$k])) return;
   $tp = (float)gs('price_' . $k, '0'); $kb = [];
   foreach (rows('SELECT * FROM tariffs ORDER BY price') as $tr)
    $kb[] = [B($tr['name'] . ' · ' . $tr['days'] . ' kun · ' . nf($tr['price'] + $tp), "cf:$k:{$tr['id']}")];
   $kb[] = [B('⬅️ Orqaga', 'cr')];
   $E("<b>" . BOT_TYPES[$k][0] . "</b>\n" . BOT_TYPES[$k][1] . "\n\n💵 Bot narxi: " . nf($tp) . "\n📦 Tarifni tanlang (jami = tarif + bot narxi):", IK($kb));
   return;
  case 'cf':
   if (!isset(BOT_TYPES[$p[1] ?? ''])) return;
   ask($uid, 'cr_token', ['type' => $p[1], 'tid' => (int)$p[2]], "🔑 <b>@BotFather</b> orqali yangi bot yarating va uning <b>tokenini</b> shu yerga yuboring.\n\n<i>Token faqat bot ishlashi uchun ishlatiladi.</i>");
   return;
  case 'mb': myBots($uid, $cid, $mid); return;
  case 'bt':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   $E(botInfo($b), IK([[B('📖 Sozlash qoʻllanmasi', 'bh:' . $b['id'])], [B('⏳ Muddatni uzaytirish', 'bx:' . $b['id']), B('🔁 Egalikni oʻtkazish', 'bo:' . $b['id'])], [B('🗑 Oʻchirish', 'bd:' . $b['id'])], [B('⬅️ Orqaga', 'mb')]]));
   return;
  case 'bh':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   $E(HELP[$b['type']] . "\n\n<i>Buyruqlarni oʻz botingiz ichida yozing. Qoʻllanma: /panel</i>", IK([[B('⬅️ Orqaga', 'bt:' . $b['id'])]]));
   return;
  case 'bx':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   $tr = row('SELECT * FROM tariffs WHERE id=?', [$b['tariff_id']]);
   if (!$tr) { $E('⚠️ Tarif topilmadi.'); return; }
   $bal = (float)val('SELECT balance FROM users WHERE id=?', [$uid]);
   if ($bal < $tr['price']) { $E("❌ Balans yetarli emas. Kerak: " . nf($tr['price']) . "\nSizda: " . nf($bal), IK([[B('⬅️ Orqaga', 'bt:' . $b['id'])]])); return; }
   q('UPDATE users SET balance=balance-? WHERE id=?', [$tr['price'], $uid]);
   $base = max(time(), strtotime($b['expires']));
   q('UPDATE bots SET expires=?,active=1 WHERE id=?', [date('Y-m-d H:i:s', $base + $tr['days'] * 86400), $b['id']]);
   $E("✅ Muddat {$tr['days']} kunga uzaytirildi.\n\n" . botInfo(row('SELECT * FROM bots WHERE id=?', [$b['id']])), IK([[B('⬅️ Orqaga', 'bt:' . $b['id'])]]));
   return;
  case 'bo':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   ask($uid, 'tr_id', ['bot' => $b['id']], "🔁 <b>Egalikni oʻtkazish</b>\n\nYangi egasining Telegram ID raqamini yuboring.\n⚠️ U avval shu platformada /start bosgan boʻlishi kerak.");
   return;
  case 'bd':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   $E("🗑 <b>@" . h($b['username']) . "</b> botini oʻchirishni tasdiqlaysizmi?\nBarcha maʼlumotlari yoʻqoladi.", IK([[B('✅ Ha, oʻchirish', 'by:' . $b['id']), B('❌ Yoʻq', 'bt:' . $b['id'])]]));
   return;
  case 'by':
   $b = myBot($uid, (int)$p[1]); if (!$b) return;
   delBot($b['id']); $E('✅ Bot oʻchirildi.', IK([[B('⬅️ Botlarim', 'mb')]]));
   return;
 }
 if (isAdmin($uid)) adminCb($p, $uid, $cid, $mid, $E);
}

/* ───────────────────────── Platforma holatlari (state) ───────────────────────── */
function pstate($u, $m, $txt) {
 $tok = cfg('token'); $uid = (int)$u['id']; $st = $u['state']; $d = json_decode($u['data'] ?: '{}', true) ?: [];
 switch ($st) {
  case 'cr_token':
   api($tok, 'deleteMessage', ['chat_id' => $uid, 'message_id' => $m['message_id']]);
   if (!preg_match('~^\d{6,}:[\w-]{30,}$~', $txt)) { send($tok, $uid, '❌ Token formati notoʻgʻri. Qayta yuboring:'); return true; }
   $me = api($txt, 'getMe');
   if (!($me['ok'] ?? false)) { send($tok, $uid, '❌ Token yaroqsiz yoki bekor qilingan. Qayta yuboring:'); return true; }
   if (val('SELECT 1 FROM bots WHERE token=?', [$txt])) { send($tok, $uid, '⚠️ Bu bot allaqachon platformaga ulangan.'); return true; }
   $tr = row('SELECT * FROM tariffs WHERE id=?', [$d['tid']]);
   if (!$tr) { setSt($uid, ''); send($tok, $uid, '⚠️ Tarif topilmadi.', mainKb($uid)); return true; }
   $total = $tr['price'] + (float)gs('price_' . $d['type'], '0');
   $bal = (float)val('SELECT balance FROM users WHERE id=?', [$uid]);
   if ($bal < $total) { setSt($uid, ''); send($tok, $uid, "❌ Balans yetarli emas.\nKerak: <b>" . nf($total) . "</b>\nSizda: <b>" . nf($bal) . "</b>\n\n«" . t('b_topup') . "» orqali hisobni toʻldiring.", mainKb($uid)); return true; }
   q('UPDATE users SET balance=balance-? WHERE id=?', [$total, $uid]);
   $id = ins('INSERT INTO bots(owner,type,name,token,username,tariff_id,expires,created) VALUES(?,?,?,?,?,?,?,?)',
    [$uid, $d['type'], $me['result']['first_name'], $txt, $me['result']['username'], $tr['id'], date('Y-m-d H:i:s', time() + $tr['days'] * 86400), now()]);
   $b = row('SELECT * FROM bots WHERE id=?', [$id]);
   $h = hook($b);
   setSt($uid, '');
   if (!($h['ok'] ?? false)) { send($tok, $uid, "⚠️ Bot yaratildi, lekin webhook ulanmadi: " . h($h['description'] ?? '') . "\nAdmin bilan bogʻlaning.", mainKb($uid)); return true; }
   send($tok, $uid, "🎉 <b>Bot muvaffaqiyatli yaratildi!</b>\n\n🤖 @" . h($b['username']) . "\n🏷 " . BOT_TYPES[$b['type']][0] . "\n💸 Yechildi: " . nf($total) . "\n\n⚙️ Sozlash: botingizga kirib /panel yuboring.", mainKb($uid));
   return true;
  case 'tr_id':
   if (!ctype_digit($txt)) { send($tok, $uid, '❌ Faqat raqamli ID yuboring:'); return true; }
   $b = myBot($uid, (int)$d['bot']); $new = (int)$txt;
   if (!$b) { setSt($uid, ''); return true; }
   if (!val('SELECT 1 FROM users WHERE id=?', [$new])) { send($tok, $uid, '❌ Bunday foydalanuvchi platformada topilmadi. U avval /start bosishi kerak.'); return true; }
   q('UPDATE bots SET owner=? WHERE id=?', [$new, $b['id']]);
   setSt($uid, '');
   send($tok, $uid, "✅ @" . h($b['username']) . " boti <code>$new</code> ga oʻtkazildi.", mainKb($uid));
   send($tok, $new, "🎁 Sizga <b>@" . h($b['username']) . "</b> boti egalik huquqi bilan oʻtkazildi. «" . t('b_mybots') . "» boʻlimini oching.");
   return true;
  case 'tp_amount':
   $a = (float)str_replace([' ', ','], '', $txt);
   if ($a < 1000) { send($tok, $uid, '❌ Eng kam summa 1 000 soʻm. Qayta yuboring:'); return true; }
   ask($uid, 'tp_receipt', ['amount' => $a], "📸 Endi toʻlov <b>chekini</b> (rasm yoki fayl) yuboring.");
   return true;
  case 'tp_receipt':
   $fid = null; $kind = 'photo';
   if (!empty($m['photo'])) $fid = end($m['photo'])['file_id'];
   elseif (!empty($m['document'])) { $fid = $m['document']['file_id']; $kind = 'document'; }
   if (!$fid) { send($tok, $uid, '❌ Iltimos, chekni rasm yoki fayl koʻrinishida yuboring.'); return true; }
   $pid = ins('INSERT INTO payments(user,amount,status,fid,kind,created) VALUES(?,?,?,?,?,?)', [$uid, $d['amount'], 'kutilmoqda', $fid, $kind, now()]);
   setSt($uid, '');
   send($tok, $uid, t('topup_ok'), mainKb($uid));
   foreach (adminIds() as $a) api($tok, $kind === 'photo' ? 'sendPhoto' : 'sendDocument', ['chat_id' => $a, $kind => $fid, 'parse_mode' => 'HTML',
    'caption' => "💳 <b>Yangi toʻlov #$pid</b>\n👤 <a href=\"tg://user?id=$uid\">$uid</a>\n💰 " . nf($d['amount']), 'reply_markup' => IK([[B('✅ Tasdiqlash', "pa:$pid"), B('❌ Rad etish', "pr:$pid")]])]);
   return true;
 }
 if (!isAdmin($uid)) { setSt($uid, ''); return false; }
 return adminState($u, $m, $txt, $d);
}

/* ───────────────────────── Admin panel ───────────────────────── */
function adminKb($uid) {
 $r = [
  [B('📊 Statistika', 'a:stat'), B('📨 Xabar yuborish', 'a:bc')],
  [B('🤖 Botlar', 'a:bots'), B('📦 Bot (shablon) qoʻshish', 'a:tpl')],
  [B('💳 Kartalar', 'a:cards'), B('🏷 Tariflar', 'a:tar')],
  [B('💵 Bot narxlari', 'a:prices'), B('🎁 Referal summasi', 'a:ref')],
  [B('📢 Majburiy kanallar', 'a:ch'), B('🎨 Dizayn', 'a:dz')],
  [B('💰 Balansni oʻzgartirish', 'a:bal'), B('⏳ Kutilayotgan toʻlovlar', 'a:pay')],
  [B('🚫 Ban / Unban', 'a:ban')],
 ];
 if (isMain($uid)) $r[] = [B('👮 Adminlar', 'a:adm')];
 return IK($r);
}
function back($to = 'a') { return IK([[B('⬅️ Admin panel', $to)]]); }

function adminCb($p, $uid, $cid, $mid, $E) {
 $tok = cfg('token');
 switch ($p[0]) {
  case 'a':
   $s = $p[1] ?? '';
   switch ($s) {
    case '': $E("⚙️ <b>Admin panel</b>", adminKb($uid)); return;
    case 'stat':
     $E("📊 <b>Statistika</b>\n\n👥 Foydalanuvchilar: <b>" . val('SELECT COUNT(*) FROM users') . "</b>\n🆕 Bugun: <b>" . val("SELECT COUNT(*) FROM users WHERE created LIKE ?", [today() . '%']) . "</b>\n🤖 Botlar: <b>" . val('SELECT COUNT(*) FROM bots') . "</b>\n✅ Faol botlar: <b>" . val("SELECT COUNT(*) FROM bots WHERE active=1 AND expires>?", [now()]) . "</b>\n💰 Umumiy balans: <b>" . nf(val('SELECT COALESCE(SUM(balance),0) FROM users')) . "</b>\n💳 Tasdiqlangan toʻlovlar: <b>" . nf(val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='tasdiqlandi'")) . "</b>", back());
     return;
    case 'bc': ask($uid, 'bc', [], "📨 Barcha foydalanuvchilarga yuboriladigan xabarni yuboring (matn, rasm, video — istalgan):"); return;
    case 'bots':
     $kb = [];
     foreach (rows('SELECT * FROM bots ORDER BY id DESC LIMIT 40') as $b) $kb[] = [B("#{$b['id']} @{$b['username']} · " . BOT_TYPES[$b['type']][0], 'ab:' . $b['id'])];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("🤖 <b>Barcha botlar</b> (oxirgi 40 ta)", IK($kb)); return;
    case 'tpl':
     $kb = [[B('➕ Bot qoʻshish', 'a:tplnew')]];
     foreach (rows('SELECT * FROM templates ORDER BY id DESC') as $t) $kb[] = [B('📄 ' . $t['name'] . ' [' . $t['lang'] . ']', 'tg:' . $t['id']), B('🗑', 'td:' . $t['id'])];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("📦 <b>Bot shablonlari</b>\nYuklangan manba kodlari. Nomini bossangiz fayl yuboriladi.", IK($kb)); return;
    case 'tplnew':
     $E("🧩 Dasturlash tilini tanlang:", IK([[B('PHP', 'tl:PHP'), B('Python', 'tl:Python'), B('JavaScript', 'tl:JavaScript')], [B('⬅️ Orqaga', 'a:tpl')]])); return;
    case 'cards':
     $kb = [[B('➕ Karta qoʻshish', 'a:cardnew')]];
     foreach (rows('SELECT * FROM cards') as $k) $kb[] = [B('💳 ' . $k['number'], 'cn:' . $k['id']), B('🗑', 'cd:' . $k['id'])];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("💳 <b>Toʻlov kartalari</b>", IK($kb)); return;
    case 'cardnew': ask($uid, 'card_add', [], "💳 Karta raqami va egasini yuboring:\n<code>8600 1234 5678 9012 | Ism Familiya</code>"); return;
    case 'tar':
     $kb = [[B('➕ Tarif qoʻshish', 'a:tarnew')]]; $s2 = '';
     foreach (rows('SELECT * FROM tariffs ORDER BY price') as $t) { $s2 .= "• <b>" . h($t['name']) . "</b> — {$t['days']} kun — " . nf($t['price']) . " — limit: " . ($t['daily_limit'] ?: 'cheksiz') . "\n"; $kb[] = [B('🗑 ' . $t['name'], 'tdel:' . $t['id'])]; }
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("🏷 <b>Tariflar</b>\n\n$s2", IK($kb)); return;
    case 'tarnew': ask($uid, 'tar_add', [], "🏷 Tarifni quyidagi koʻrinishda yuboring:\n<code>Nomi | Kun | Narx | Kunlik limit</code>\n\nMasalan: <code>Standart | 30 | 20000 | 1000</code>\n(Kunlik limit: bir kunda botdan foydalana oladigan odam soni; 1001-odam ertasiga qadar bloklanadi. 0 = cheksiz)"); return;
    case 'prices':
     $kb = []; foreach (BOT_TYPES as $k => $v) $kb[] = [B($v[0] . ' — ' . nf(gs('price_' . $k, '0')), 'ap:' . $k)];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("💵 <b>Bot turlari narxi</b>\nOʻzgartirish uchun turini tanlang:", IK($kb)); return;
    case 'ref': ask($uid, 'ref_set', [], "🎁 Joriy referal mukofoti: <b>" . nf(gs('ref_reward', '1000')) . "</b>\nYangi summani yuboring:"); return;
    case 'ch':
     $kb = [[B('➕ Kanal qoʻshish', 'a:chnew')]];
     foreach (rows('SELECT * FROM channels') as $c) $kb[] = [B('🗑 ' . ($c['title'] ?: $c['chat']), 'chd:' . $c['id'])];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("📢 <b>Majburiy obuna kanallari</b>\nBot kanalda admin boʻlishi shart.", IK($kb)); return;
    case 'chnew': ask($uid, 'ch_add', [], "📢 Kanal @username (yoki -100... ID) yuboring. Ixtiyoriy: probel bilan sarlavha.\nMasalan: <code>@mening_kanalim Yangiliklar</code>"); return;
    case 'dz':
     $E("🎨 <b>Dizayn sozlamalari</b>", IK([[B('📝 Matnlarni tahrirlash', 'dz:tx')], [B('🎨 Tugma uslubi', 'dz:st')], [B('💎 Premium emoji (tugma)', 'dz:em')], [B('⬅️ Admin panel', 'a')]])); return;
    case 'bal': ask($uid, 'bal_set', [], "💰 Format: <code>ID | +summa</code> yoki <code>ID | -summa</code>\nMasalan: <code>123456789 | +5000</code>"); return;
    case 'ban': ask($uid, 'ban_set', [], "🚫 Foydalanuvchi ID raqamini yuboring (ban/unban almashadi):"); return;
    case 'pay':
     $ps = rows("SELECT * FROM payments WHERE status='kutilmoqda' ORDER BY id LIMIT 10");
     if (!$ps) { $E("✅ Kutilayotgan toʻlovlar yoʻq.", back()); return; }
     foreach ($ps as $pp) api($tok, $pp['kind'] === 'photo' ? 'sendPhoto' : 'sendDocument', ['chat_id' => $uid, $pp['kind'] => $pp['fid'], 'parse_mode' => 'HTML', 'caption' => "💳 <b>Toʻlov #{$pp['id']}</b>\n👤 <code>{$pp['user']}</code>\n💰 " . nf($pp['amount']), 'reply_markup' => IK([[B('✅ Tasdiqlash', 'pa:' . $pp['id']), B('❌ Rad etish', 'pr:' . $pp['id'])]])]);
     return;
    case 'adm':
     if (!isMain($uid)) return;
     $kb = [[B('➕ Admin qoʻshish', 'a:admnew')]];
     foreach (rows('SELECT * FROM admins') as $a) $kb[] = [B('🗑 ' . $a['id'], 'ad:' . $a['id'])];
     $kb[] = [B('⬅️ Admin panel', 'a')];
     $E("👮 <b>Adminlar</b>\nAsosiy admin: <code>" . cfg('admin') . "</code>\nHamkor adminlar toʻlov, bot va foydalanuvchilarni boshqara oladi.", IK($kb)); return;
    case 'admnew': if (isMain($uid)) ask($uid, 'adm_add', [], "👮 Yangi admin Telegram ID raqamini yuboring:"); return;
   }
   return;
  case 'ab':
   $b = row('SELECT * FROM bots WHERE id=?', [(int)$p[1]]); if (!$b) return;
   $E(botInfo($b), IK([[B('🔁 Egalikni oʻtkazish', 'bo:' . $b['id']), B('🗑 Oʻchirish', 'bd:' . $b['id'])], [B('⬅️ Botlar', 'a:bots')]])); return;
  case 'tl': ask($uid, 'tpl_file', ['lang' => $p[1]], "📎 <b>{$p[1]}</b> manba kodini fayl (.php/.py/.js/.zip) koʻrinishida yuboring (20 MB gacha):"); return;
  case 'tg':
   $t = row('SELECT * FROM templates WHERE id=?', [(int)$p[1]]);
   if ($t) api($tok, 'sendDocument', ['chat_id' => $uid, 'document' => $t['fid'], 'caption' => '📄 ' . $t['name'] . ' [' . $t['lang'] . ']']);
   return;
  case 'td':
   $t = row('SELECT * FROM templates WHERE id=?', [(int)$p[1]]);
   if ($t) { @unlink($t['path']); q('DELETE FROM templates WHERE id=?', [$t['id']]); }
   $E("🗑 Shablon oʻchirildi.", IK([[B('⬅️ Shablonlar', 'a:tpl')]])); return;
  case 'cd': q('DELETE FROM cards WHERE id=?', [(int)$p[1]]); $E("🗑 Karta oʻchirildi.", IK([[B('⬅️ Kartalar', 'a:cards')]])); return;
  case 'tdel': q('DELETE FROM tariffs WHERE id=?', [(int)$p[1]]); $E("🗑 Tarif oʻchirildi.", IK([[B('⬅️ Tariflar', 'a:tar')]])); return;
  case 'ap': if (isset(BOT_TYPES[$p[1] ?? ''])) ask($uid, 'price_set', ['k' => $p[1]], "💵 <b>" . BOT_TYPES[$p[1]][0] . "</b> uchun yangi narxni yuboring (soʻmda):"); return;
  case 'chd': q('DELETE FROM channels WHERE id=?', [(int)$p[1]]); $E("🗑 Kanal oʻchirildi.", IK([[B('⬅️ Kanallar', 'a:ch')]])); return;
  case 'ad': if (isMain($uid)) { q('DELETE FROM admins WHERE id=?', [(int)$p[1]]); $E("🗑 Admin oʻchirildi.", IK([[B('⬅️ Adminlar', 'a:adm')]])); } return;
  case 'dz':
   $s = $p[1] ?? '';
   if ($s === 'tx') {
    $kb = []; foreach (array_chunk(array_keys(TX), 2) as $ch) $kb[] = array_map(fn($k) => B($k, 'tx:' . $k), $ch);
    $kb[] = [B('⬅️ Orqaga', 'a:dz')];
    $E("📝 Tahrirlanadigan matnni tanlang:", IK($kb));
   } elseif ($s === 'st') {
    $E("🎨 Tugma uslubini tanlang (joriy: <b>" . (gs('btn_style') ?: 'oddiy') . "</b>):", IK([[B('Oddiy', 'ds:none'), B('Asosiy (koʻk)', 'ds:primary')], [B('Yashil', 'ds:success'), B('Qizil', 'ds:danger')], [B('⬅️ Orqaga', 'a:dz')]]));
   } elseif ($s === 'em') {
    ask($uid, 'em_set', [], "💎 Tugmaga premium emoji biriktirish:\n<code>tugma_kaliti | emoji_id</code>\nOʻchirish: <code>tugma_kaliti | -</code>\n\nKalitlar: <code>cr</code> (Bot yaratish), <code>mb</code>, <code>a:stat</code>, <code>a:bc</code>, <code>a:bots</code>, <code>a:tpl</code>, <code>a:cards</code>, <code>a:tar</code>, <code>a:prices</code>, <code>a:ref</code>, <code>a:ch</code>, <code>a:dz</code>, <code>a:bal</code>, <code>a:pay</code>, <code>a:ban</code>\n\n💡 Matnlar ichida premium emoji: <code>&lt;tg-emoji emoji-id=\"ID\"&gt;🔥&lt;/tg-emoji&gt;</code>");
   }
   return;
  case 'ds': if (in_array($p[1] ?? '', ['none', 'primary', 'success', 'danger'])) { ss('btn_style', $p[1] === 'none' ? '' : $p[1]); $E("✅ Tugma uslubi saqlandi.", IK([[B('⬅️ Dizayn', 'a:dz')]])); } return;
  case 'tx':
   if (!isset(TX[$p[1] ?? ''])) return;
   ask($uid, 'tx_set', ['k' => $p[1]], "📝 <b>" . $p[1] . "</b>\n\nJoriy matn:\n<pre>" . h(t($p[1])) . "</pre>\nYangi matnni HTML formatda yuboring. Asliga qaytarish: /tozalash\nOʻrin belgilari: {ism} {sum} {link} {n} {admin} {cards}");
   return;
  case 'pa': case 'pr':
   $pay = row('SELECT * FROM payments WHERE id=?', [(int)$p[1]]);
   api($tok, 'editMessageReplyMarkup', ['chat_id' => $cid, 'message_id' => $mid, 'reply_markup' => ['inline_keyboard' => []]]);
   if (!$pay || $pay['status'] !== 'kutilmoqda') { send($tok, $uid, '⚠️ Bu toʻlov allaqachon koʻrib chiqilgan.'); return; }
   if ($p[0] === 'pa') {
    q("UPDATE payments SET status='tasdiqlandi' WHERE id=?", [$pay['id']]);
    q('UPDATE users SET balance=balance+? WHERE id=?', [$pay['amount'], $pay['user']]);
    send($tok, $pay['user'], "✅ Toʻlovingiz tasdiqlandi!\n💰 Hisobingizga <b>" . nf($pay['amount']) . "</b> qoʻshildi.");
    send($tok, $uid, "✅ Toʻlov #{$pay['id']} tasdiqlandi.");
   } else {
    q("UPDATE payments SET status='rad etildi' WHERE id=?", [$pay['id']]);
    send($tok, $pay['user'], "❌ Toʻlovingiz rad etildi. Muammo boʻlsa admin bilan bogʻlaning.");
    send($tok, $uid, "❌ Toʻlov #{$pay['id']} rad etildi.");
   }
   return;
 }
}

function adminState($u, $m, $txt, $d) {
 $tok = cfg('token'); $uid = (int)$u['id']; $st = $u['state'];
 $done = function ($t) use ($tok, $uid) { setSt($uid, ''); send($tok, $uid, $t, mainKb($uid)); return true; };
 switch ($st) {
  case 'bc':
   $ids = array_column(rows('SELECT id FROM users WHERE banned=0'), 'id'); $ok = 0;
   foreach ($ids as $id) { $r = api($tok, 'copyMessage', ['chat_id' => $id, 'from_chat_id' => $uid, 'message_id' => $m['message_id']]); if ($r['ok'] ?? false) $ok++; usleep(45000); }
   return $done("📨 Yuborildi: <b>$ok</b> / " . count($ids));
  case 'tpl_file':
   $doc = $m['document'] ?? null;
   if (!$doc) { send($tok, $uid, '❌ Iltimos, fayl yuboring.'); return true; }
   @mkdir(cfg('upload'), 0755, true);
   $path = cfg('upload') . '/' . time() . '_' . preg_replace('~[^\w.-]~', '_', $doc['file_name'] ?? 'bot.txt');
   if (!dl($tok, $doc['file_id'], $path)) { send($tok, $uid, '❌ Faylni yuklab boʻlmadi (20 MB dan kichik boʻlsin).'); return true; }
   ask($uid, 'tpl_name', $d + ['path' => $path, 'fid' => $doc['file_id']], "✍️ Bot nomini yuboring:");
   return true;
  case 'tpl_name':
   ins('INSERT INTO templates(name,lang,path,fid,added) VALUES(?,?,?,?,?)', [$txt, $d['lang'], $d['path'], $d['fid'], now()]);
   return $done("✅ <b>" . h($txt) . "</b> [{$d['lang']}] qoʻshildi.");
  case 'card_add':
   $x = array_map('trim', explode('|', $txt));
   if (count($x) < 2 || strlen($x[0]) < 8) { send($tok, $uid, '❌ Format: <code>raqam | ega</code>'); return true; }
   ins('INSERT INTO cards(number,holder) VALUES(?,?)', [$x[0], $x[1]]);
   return $done('✅ Karta qoʻshildi.');
  case 'tar_add':
   $x = array_map('trim', explode('|', $txt));
   if (count($x) < 4 || !ctype_digit($x[1]) || !is_numeric($x[2]) || !ctype_digit($x[3])) { send($tok, $uid, '❌ Format: <code>Nomi | Kun | Narx | Limit</code>'); return true; }
   ins('INSERT INTO tariffs(name,days,price,daily_limit) VALUES(?,?,?,?)', [$x[0], (int)$x[1], (float)$x[2], (int)$x[3]]);
   return $done('✅ Tarif qoʻshildi.');
  case 'price_set':
   if (!is_numeric($txt)) { send($tok, $uid, '❌ Raqam yuboring.'); return true; }
   ss('price_' . $d['k'], $txt); return $done('✅ Narx saqlandi.');
  case 'ref_set':
   if (!is_numeric($txt)) { send($tok, $uid, '❌ Raqam yuboring.'); return true; }
   ss('ref_reward', $txt); return $done('✅ Referal mukofoti saqlandi: ' . nf($txt));
  case 'ch_add':
   $x = preg_split('~\s+~', $txt, 2);
   $r = api($tok, 'getChat', ['chat_id' => $x[0]]);
   if (!($r['ok'] ?? false)) { send($tok, $uid, '❌ Kanal topilmadi yoki bot kanalda emas.'); return true; }
   ins('INSERT INTO channels(chat,title,link) VALUES(?,?,?)', [$x[0], $x[1] ?? ($r['result']['title'] ?? ''), $r['result']['invite_link'] ?? '']);
   return $done('✅ Kanal qoʻshildi.');
  case 'tx_set':
   if ($txt === '/tozalash') { q('DELETE FROM texts WHERE k=?', [$d['k']]); return $done('♻️ Matn asl holatiga qaytarildi.'); }
   q('INSERT INTO texts(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v', [$d['k'], $txt]);
   return $done('✅ Matn saqlandi.');
  case 'em_set':
   $x = array_map('trim', explode('|', $txt));
   if (count($x) < 2) { send($tok, $uid, '❌ Format: <code>kalit | emoji_id</code>'); return true; }
   if ($x[1] === '-') q('DELETE FROM settings WHERE k=?', ['icon:' . $x[0]]); else ss('icon:' . $x[0], $x[1]);
   return $done('✅ Saqlandi.');
  case 'adm_add':
   if (!isMain($uid) || !ctype_digit($txt)) { send($tok, $uid, '❌ Raqamli ID yuboring.'); return true; }
   q('INSERT OR IGNORE INTO admins(id,added_by) VALUES(?,?)', [(int)$txt, $uid]);
   send($tok, (int)$txt, "👮 Siz admin etib tayinlandingiz. /start ni bosing.");
   return $done('✅ Admin qoʻshildi.');
  case 'bal_set':
   $x = array_map('trim', explode('|', $txt));
   if (count($x) < 2 || !ctype_digit($x[0]) || !is_numeric($x[1])) { send($tok, $uid, '❌ Format: <code>ID | +summa</code>'); return true; }
   q('UPDATE users SET balance=balance+? WHERE id=?', [(float)$x[1], (int)$x[0]]);
   send($tok, (int)$x[0], "💰 Balansingiz oʻzgartirildi: <b>" . ((float)$x[1] >= 0 ? '+' : '') . nf($x[1]) . "</b>");
   return $done('✅ Bajarildi. Yangi balans: ' . nf(val('SELECT balance FROM users WHERE id=?', [(int)$x[0]])));
  case 'ban_set':
   if (!ctype_digit($txt) || isAdmin((int)$txt)) { send($tok, $uid, '❌ Notoʻgʻri ID.'); return true; }
   q('UPDATE users SET banned=1-banned WHERE id=?', [(int)$txt]);
   return $done('✅ Holat: ' . (val('SELECT banned FROM users WHERE id=?', [(int)$txt]) ? '🚫 bloklandi' : '✅ blokdan chiqarildi'));
 }
 return false;
}

/* ═════════════════════════ BOLA BOTLAR (yaratilgan botlar) ═════════════════════════ */
function bcfg($b, $k, $d = null) { $c = json_decode((string)val('SELECT cfg FROM bots WHERE id=?', [$b['id']]), true) ?: []; return $c[$k] ?? $d; }
function bset($b, $k, $v) { $c = json_decode((string)val('SELECT cfg FROM bots WHERE id=?', [$b['id']]), true) ?: []; $c[$k] = $v; q('UPDATE bots SET cfg=? WHERE id=?', [json_encode($c, JSON_UNESCAPED_UNICODE), $b['id']]); }
function bu($b, $uid, $f = null) {
 $r = row('SELECT * FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
 if (!$r) { q('INSERT INTO bu(bot_id,uid,name,joined) VALUES(?,?,?,?)', [$b['id'], $uid, $f ? trim($f['first_name'] ?? '') : '', now()]); $r = row('SELECT * FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid]); }
 return $r;
}
function bst($b, $uid) { $r = bu($b, $uid); return [$r['state'], json_decode($r['data'] ?: '{}', true) ?: []]; }
function bsave($b, $uid, $st, $d = []) { q('UPDATE bu SET state=?,data=? WHERE bot_id=? AND uid=?', [$st, json_encode($d, JSON_UNESCAPED_UNICODE), $b['id'], $uid]); }
function cmd($txt) { return preg_match('~^(/\w+)(?:@\w+)?\s*(.*)$~su', $txt, $mm) ? [strtolower($mm[1]), trim($mm[2])] : ['', '']; }
function bbal($b, $uid, $delta) { q('UPDATE bu SET bal=bal+? WHERE bot_id=? AND uid=?', [$delta, $b['id'], $uid]); }
function http_json($url, $headers, $body) {
 $ch = curl_init($url);
 curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 90]);
 $r = curl_exec($ch); curl_close($ch);
 return json_decode((string)$r, true) ?: [];
}

function C($b, $u) {
 $tok = $b['token'];
 if (isset($u['pre_checkout_query'])) { api($tok, 'answerPreCheckoutQuery', ['pre_checkout_query_id' => $u['pre_checkout_query']['id'], 'ok' => true]); return; }
 if (isset($u['chat_member'])) { trackMember($b, $u['chat_member']); return; }
 $cb = $u['callback_query'] ?? null;
 $m = $cb ? ($cb['message'] ?? null) : ($u['message'] ?? null);
 if (!$m || ($m['chat']['type'] ?? '') !== 'private') return;
 $f = $cb ? $cb['from'] : $m['from'];
 $uid = (int)$f['id']; $chat = $m['chat']['id'];
 if ($cb) api($tok, 'answerCallbackQuery', ['callback_query_id' => $cb['id']]);
 $owner = (int)$b['owner'] === $uid || isAdmin($uid);
 if (!$b['active'] || strtotime($b['expires']) < time()) {
  send($tok, $chat, $owner ? "⛔️ Bot muddati tugagan. Platformadagi «Botlarim» boʻlimidan uzaytiring." : "⛔️ Bot vaqtincha ishlamayapti.");
  return;
 }
 bu($b, $uid, $f);
 $tr = row('SELECT * FROM tariffs WHERE id=?', [$b['tariff_id']]);
 if (!$owner && $tr && (int)$tr['daily_limit'] > 0 && !val('SELECT 1 FROM daily WHERE bot_id=? AND uid=? AND day=?', [$b['id'], $uid, today()])) {
  if ((int)val('SELECT COUNT(*) FROM daily WHERE bot_id=? AND day=?', [$b['id'], today()]) >= (int)$tr['daily_limit']) {
   send($tok, $chat, "⏳ Bugungi foydalanuvchilar limiti tugadi. Iltimos, ertaga qayta urinib koʻring.");
   return;
  }
 }
 q('INSERT OR IGNORE INTO daily(bot_id,uid,day) VALUES(?,?,?)', [$b['id'], $uid, today()]);
 $txt = $cb ? '' : trim($m['text'] ?? '');
 if (!$cb && $owner && $txt !== '' && $txt[0] === '/' && ownerCmd($b, $uid, $chat, $txt, $m)) return;
 $fn = 'T_' . $b['type'];
 if (function_exists($fn)) $fn($b, $uid, $chat, $m, $txt, $cb['data'] ?? null, $f);
}

function ownerCmd($b, $uid, $chat, $txt, $m) {
 $tok = $b['token']; [$c, $a] = cmd($txt);
 switch ($c) {
  case '/panel': send($tok, $chat, HELP[$b['type']] . "\n\n/reklama matn — hammaga xabar (yoki xabarga javoban /reklama)\n/stat — statistika"); return true;
  case '/reklama':
   $ids = array_column(rows('SELECT uid FROM bu WHERE bot_id=?', [$b['id']]), 'uid'); $ok = 0;
   $rep = $m['reply_to_message']['message_id'] ?? null;
   if (!$rep && $a === '') { send($tok, $chat, "ℹ️ /reklama matn — yoki xabarga javoban /reklama"); return true; }
   foreach ($ids as $i) {
    $r = $rep ? api($tok, 'copyMessage', ['chat_id' => $i, 'from_chat_id' => $chat, 'message_id' => $rep]) : send($tok, $i, $a);
    if ($r['ok'] ?? false) $ok++; usleep(45000);
   }
   send($tok, $chat, "📨 Yuborildi: $ok / " . count($ids)); return true;
  case '/stat':
   if (in_array($b['type'], ['kinopro', 'obuna'])) break;
   send($tok, $chat, "📊 <b>Statistika</b>\n👥 Foydalanuvchilar: " . val('SELECT COUNT(*) FROM bu WHERE bot_id=?', [$b['id']]) . "\n🔥 Bugun faol: " . val('SELECT COUNT(*) FROM daily WHERE bot_id=? AND day=?', [$b['id'], today()]) . ($b['type'] === 'anon' ? "\n💬 Faol suhbatlar: " . (int)(val('SELECT COUNT(*) FROM bu WHERE bot_id=? AND partner<>0', [$b['id']]) / 2) : ''));
   return true;
  case '/balans':
   if (!in_array($b['type'], ['smm', 'nomer'])) return false;
   $x = preg_split('~\s+~', $a);
   if (count($x) < 2 || !ctype_digit($x[0]) || !is_numeric($x[1])) { send($tok, $chat, "❌ /balans user_id summa"); return true; }
   bu($b, (int)$x[0]); bbal($b, (int)$x[0], (float)$x[1]);
   send($tok, (int)$x[0], "💰 Balansingiz oʻzgartirildi: <b>" . nf($x[1]) . "</b>");
   send($tok, $chat, "✅ Yangi balans: " . nf(val('SELECT bal FROM bu WHERE bot_id=? AND uid=?', [$b['id'], (int)$x[0]]))); return true;
  case '/aloqa':
   if (!in_array($b['type'], ['smm', 'nomer'])) return false;
   bset($b, 'aloqa', $a); send($tok, $chat, '✅ Saqlandi.'); return true;
  case '/kanal': case '/kanalochir': case '/kanallar':
   if (!in_array($b['type'], ['kinopro', 'obuna'])) return false;
   if ($c === '/kanallar') { $s = "📢 <b>Kanallar</b>\n"; foreach (rows('SELECT * FROM bch WHERE bot_id=?', [$b['id']]) as $k) $s .= "• " . h($k['chat']) . "\n"; send($tok, $chat, $s); return true; }
   if ($c === '/kanalochir') { q('DELETE FROM bch WHERE bot_id=? AND chat=?', [$b['id'], $a]); send($tok, $chat, '🗑 Oʻchirildi.'); return true; }
   $x = preg_split('~\s+~', $a, 2); $r = api($tok, 'getChat', ['chat_id' => $x[0]]);
   if (!($r['ok'] ?? false) || $x[0] === '') { send($tok, $chat, "❌ Kanal topilmadi. Bot kanalda admin boʻlishi shart.\nFormat: /kanal @kanal [Sarlavha]"); return true; }
   q('INSERT INTO bch(bot_id,chat,title,link) VALUES(?,?,?,?)', [$b['id'], $x[0], $x[1] ?? ($r['result']['title'] ?? ''), $r['result']['invite_link'] ?? '']);
   send($tok, $chat, '✅ Kanal qoʻshildi.'); return true;
 }
 $fn = 'O_' . $b['type'];
 return function_exists($fn) ? $fn($b, $c, $a, $chat, $m) : false;
}

/* ── Majburiy obuna (Kino Pro va Obunachi uchun umumiy) ── */
function chGate($b, $uid, $chat) {
 $ch = rows('SELECT * FROM bch WHERE bot_id=?', [$b['id']]);
 if (!$ch) return true;
 $mi = missing($b['token'], $uid, $ch);
 if (!$mi) { if (!val('SELECT verified FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid])) q('INSERT INTO sublog(bot_id,uid,chat,ev,created) VALUES(?,?,?,?,?)', [$b['id'], $uid, '', 'tasdiq', now()]); q('UPDATE bu SET verified=1 WHERE bot_id=? AND uid=?', [$b['id'], $uid]); return true; }
 q('UPDATE bu SET verified=0 WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
 $kb = []; foreach ($mi as $c) $kb[] = [U('📢 ' . ($c['title'] ?: $c['chat']), chanUrl($c))];
 $kb[] = [B('✅ Tekshirish', 'ob:check')];
 send($b['token'], $chat, "📢 Botdan foydalanish uchun quyidagi kanallarga aʼzo boʻling, soʻng «Tekshirish» tugmasini bosing:", IK($kb));
 return false;
}
function trackMember($b, $cm) {
 $chat = $cm['chat']; $uid = (int)$cm['new_chat_member']['user']['id'];
 $mine = false;
 foreach (rows('SELECT * FROM bch WHERE bot_id=?', [$b['id']]) as $c) if ($c['chat'] === '@' . ($chat['username'] ?? '') || (string)$c['chat'] === (string)$chat['id']) $mine = true;
 if (!$mine) return;
 $new = $cm['new_chat_member']['status']; $old = $cm['old_chat_member']['status'] ?? '';
 if (in_array($new, ['member', 'administrator', 'creator']) && in_array($old, ['left', 'kicked', ''])) q('INSERT INTO sublog(bot_id,uid,chat,ev,created) VALUES(?,?,?,?,?)', [$b['id'], $uid, (string)$chat['id'], 'qoshildi', now()]);
 if (in_array($new, ['left', 'kicked'])) { q('INSERT INTO sublog(bot_id,uid,chat,ev,created) VALUES(?,?,?,?,?)', [$b['id'], $uid, (string)$chat['id'], 'chiqdi', now()]); q('UPDATE bu SET verified=0 WHERE bot_id=? AND uid=?', [$b['id'], $uid]); }
}

/* ── 1. BUILDER BOT ── */
function T_builder($b, $uid, $chat, $m, $txt, $cd, $f) {
 if ($cd !== null && str_starts_with($cd, 'm:')) { bnode($b, $chat, (int)substr($cd, 2), $m['message_id']); return; }
 if ($cd === null) bnode($b, $chat, 0, null);
}
function bnode($b, $chat, $id, $mid) {
 $tok = $b['token'];
 if ($id === 0) { $text = h(bcfg($b, 'salom', "👋 Xush kelibsiz! Quyidagi boʻlimlardan birini tanlang:")); $parent = null; }
 else {
  $n = row('SELECT * FROM menus WHERE id=? AND bot_id=?', [$id, $b['id']]); if (!$n) return;
  $text = "<b>" . h($n['title']) . "</b>\n\n" . h($n['body']); $parent = (int)$n['parent'];
 }
 $btn = []; foreach (rows('SELECT * FROM menus WHERE bot_id=? AND parent=? ORDER BY id', [$b['id'], $id]) as $c) $btn[] = B($c['title'], 'm:' . $c['id']);
 $kb = array_chunk($btn, 2);
 if ($parent !== null) $kb[] = [B('⬅️ Orqaga', 'm:' . $parent)];
 $mid ? edit($tok, $chat, $mid, $text, $kb ? IK($kb) : null) : send($tok, $chat, $text, $kb ? IK($kb) : null);
}
function O_builder($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 switch ($c) {
  case '/qosh':
   $x = array_map('trim', explode('|', $a, 3));
   if (count($x) < 3 || !ctype_digit($x[0])) { send($tok, $chat, "❌ Format: /qosh ota_id|Sarlavha|Matn"); return true; }
   if ($x[0] !== '0' && !val('SELECT 1 FROM menus WHERE id=? AND bot_id=?', [(int)$x[0], $b['id']])) { send($tok, $chat, '❌ Ota boʻlim topilmadi.'); return true; }
   $id = ins('INSERT INTO menus(bot_id,parent,title,body) VALUES(?,?,?,?)', [$b['id'], (int)$x[0], $x[1], $x[2]]);
   send($tok, $chat, "✅ Qoʻshildi. ID: <b>$id</b>"); return true;
  case '/ochir':
   $del = function ($id) use (&$del, $b) { foreach (rows('SELECT id FROM menus WHERE bot_id=? AND parent=?', [$b['id'], $id]) as $k) $del($k['id']); q('DELETE FROM menus WHERE id=? AND bot_id=?', [$id, $b['id']]); };
   $del((int)$a); send($tok, $chat, '🗑 Oʻchirildi.'); return true;
  case '/royxat':
   $out = ''; $walk = function ($p, $lv) use (&$walk, &$out, $b) { foreach (rows('SELECT * FROM menus WHERE bot_id=? AND parent=? ORDER BY id', [$b['id'], $p]) as $k) { $out .= str_repeat('— ', $lv) . "#{$k['id']} " . h($k['title']) . "\n"; $walk($k['id'], $lv + 1); } };
   $walk(0, 0); send($tok, $chat, "🌳 <b>Boʻlimlar</b>\n" . ($out ?: 'Hozircha boʻsh.')); return true;
  case '/salom': bset($b, 'salom', $a); send($tok, $chat, '✅ Saqlandi.'); return true;
 }
 return false;
}

/* ── 2. SMM BOT ── */
function smmApi($b, $data) {
 $url = bcfg($b, 'api_url'); $key = bcfg($b, 'api_key');
 if (!$url || !$key) return null;
 $ch = curl_init($url);
 curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data + ['key' => $key]), CURLOPT_TIMEOUT => 60]);
 $r = curl_exec($ch); curl_close($ch);
 return json_decode((string)$r, true) ?: [];
}
function T_smm($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 $kb = RK([['🛒 Buyurtma berish', '💰 Balansim'], ['📦 Buyurtmalarim', '☎️ Aloqa']]);
 if ($cd !== null) {
  if (str_starts_with($cd, 'sv:')) {
   $s = row('SELECT * FROM services WHERE id=? AND bot_id=?', [(int)substr($cd, 3), $b['id']]); if (!$s) return;
   bsave($b, $uid, 'link', ['sv' => $s['id']]);
   send($tok, $chat, "🔗 <b>" . h($s['name']) . "</b>\n💵 1000 ta: " . nf($s['price']) . "\n📏 Min: {$s['min']} · Maks: {$s['max']}\n\nHavola (link) yuboring:", cancelKbC());
  }
  return;
 }
 $menu = ['🛒 Buyurtma berish', '💰 Balansim', '📦 Buyurtmalarim', '☎️ Aloqa', '❌ Bekor qilish'];
 [$st, $d] = bst($b, $uid);
 if (in_array($txt, $menu, true) || str_starts_with($txt, '/start')) { bsave($b, $uid, ''); $st = ''; }
 if (str_starts_with($txt, '/start')) { send($tok, $chat, "👋 <b>Xush kelibsiz!</b>\nIjtimoiy tarmoq xizmatlari panelimizga marhamat.", $kb); return; }
 switch ($txt) {
  case '🛒 Buyurtma berish':
   $k = []; foreach (rows('SELECT * FROM services WHERE bot_id=?', [$b['id']]) as $s) $k[] = [B($s['name'] . ' · ' . nf($s['price']) . '/1000', 'sv:' . $s['id'])];
   send($tok, $chat, $k ? "🛒 Xizmatni tanlang:" : "⚠️ Hozircha xizmatlar yoʻq.", $k ? IK($k) : null); return;
  case '💰 Balansim':
   send($tok, $chat, "💰 Balansingiz: <b>" . nf(bu($b, $uid)['bal']) . "</b>\n🆔 ID: <code>$uid</code>\n\n" . h(bcfg($b, 'aloqa', "Balans toʻldirish uchun admin bilan bogʻlaning."))); return;
  case '☎️ Aloqa': send($tok, $chat, h(bcfg($b, 'aloqa', "Aloqa maʼlumoti kiritilmagan."))); return;
  case '📦 Buyurtmalarim':
   $s = "📦 <b>Soʻnggi buyurtmalar</b>\n";
   foreach (rows('SELECT * FROM orders WHERE bot_id=? AND uid=? ORDER BY id DESC LIMIT 10', [$b['id'], $uid]) as $o) $s .= "• #{$o['id']} — {$o['qty']} ta — " . nf($o['cost']) . " — {$o['status']}\n";
   send($tok, $chat, $s); return;
  case '❌ Bekor qilish': send($tok, $chat, '🏠 Menyu', $kb); return;
 }
 if ($st === 'link') {
  if (!preg_match('~^(https?://|@)~i', $txt)) { send($tok, $chat, '❌ Havola notoʻgʻri. Qayta yuboring:'); return; }
  bsave($b, $uid, 'qty', $d + ['link' => $txt]); send($tok, $chat, '🔢 Miqdorni yuboring:'); return;
 }
 if ($st === 'qty') {
  $s = row('SELECT * FROM services WHERE id=?', [$d['sv']]); $q = (int)$txt;
  if (!$s || $q < $s['min'] || $q > $s['max']) { send($tok, $chat, "❌ Miqdor {$s['min']} dan {$s['max']} gacha boʻlishi kerak:"); return; }
  $cost = $s['price'] * $q / 1000; $bal = (float)bu($b, $uid)['bal'];
  if ($bal < $cost) { bsave($b, $uid, ''); send($tok, $chat, "❌ Balans yetarli emas. Kerak: " . nf($cost) . "\nSizda: " . nf($bal), $kb); return; }
  $r = smmApi($b, ['action' => 'add', 'service' => $s['sid'], 'link' => $d['link'], 'quantity' => $q]);
  if ($r !== null && isset($r['error'])) { bsave($b, $uid, ''); send($tok, $chat, "❌ Xatolik: " . h($r['error']), $kb); return; }
  bbal($b, $uid, -$cost);
  $oid = ins('INSERT INTO orders(bot_id,uid,service_id,link,qty,cost,api_id,status,created) VALUES(?,?,?,?,?,?,?,?,?)', [$b['id'], $uid, $s['id'], $d['link'], $q, $cost, (string)($r['order'] ?? ''), $r === null ? 'qoʻlda' : 'jarayonda', now()]);
  bsave($b, $uid, '');
  send($tok, $chat, "✅ Buyurtma qabul qilindi!\n🆔 #$oid · " . h($s['name']) . " · $q ta\n💸 " . nf($cost), $kb);
  if ($r === null) send($tok, $b['owner'], "🛎 Yangi qoʻlda buyurtma #$oid\n" . h($s['name']) . " · $q ta\n" . h($d['link']));
  return;
 }
 send($tok, $chat, '🏠 Menyu', $kb);
}
function cancelKbC() { return RK([['❌ Bekor qilish']]); }
function O_smm($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 switch ($c) {
  case '/xizmat':
   $x = array_map('trim', explode('|', $a));
   if (count($x) < 5) { send($tok, $chat, "❌ /xizmat Nom|narx|service_id|min|maks"); return true; }
   $id = ins('INSERT INTO services(bot_id,name,price,sid,min,max) VALUES(?,?,?,?,?,?)', [$b['id'], $x[0], (float)$x[1], $x[2], (int)$x[3], (int)$x[4]]);
   send($tok, $chat, "✅ Xizmat qoʻshildi. ID: $id"); return true;
  case '/xizmatochir': q('DELETE FROM services WHERE id=? AND bot_id=?', [(int)$a, $b['id']]); send($tok, $chat, '🗑 Oʻchirildi.'); return true;
  case '/xizmatlar':
   $s = "📋 <b>Xizmatlar</b>\n"; foreach (rows('SELECT * FROM services WHERE bot_id=?', [$b['id']]) as $x) $s .= "#{$x['id']} " . h($x['name']) . " — " . nf($x['price']) . " — API:{$x['sid']}\n";
   send($tok, $chat, $s); return true;
  case '/api':
   $x = array_map('trim', explode('|', $a));
   if (count($x) < 2) { send($tok, $chat, "❌ /api url|kalit"); return true; }
   bset($b, 'api_url', $x[0]); bset($b, 'api_key', $x[1]);
   api($b['token'], 'deleteMessage', ['chat_id' => $chat, 'message_id' => $m['message_id']]);
   send($tok, $chat, '✅ API ulandi (kalit xabari oʻchirildi).'); return true;
 }
 return false;
}

/* ── 3. VIRTUAL NOMER BOT ── */
function T_nomer($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 $kb = RK([['📱 Raqam sotib olish', '💰 Balansim'], ['📋 Raqamlarim', '☎️ Aloqa']]);
 if ($cd !== null) {
  $p = explode(':', $cd);
  if ($p[0] === 'nc') {
   $k = []; foreach (rows("SELECT * FROM numbers WHERE bot_id=? AND country=? AND status='bor' LIMIT 20", [$b['id'], $p[1]]) as $n) $k[] = [B(substr($n['num'], 0, -4) . '**** · ' . nf($n['price']), 'nb:' . $n['id'])];
   send($tok, $chat, $k ? "📱 Raqamni tanlang:" : "⚠️ Raqam qolmadi.", $k ? IK($k) : null);
  } elseif ($p[0] === 'nb') {
   $n = row("SELECT * FROM numbers WHERE id=? AND bot_id=? AND status='bor'", [(int)$p[1], $b['id']]);
   if (!$n) { send($tok, $chat, '⚠️ Raqam band yoki topilmadi.'); return; }
   if ((float)bu($b, $uid)['bal'] < $n['price']) { send($tok, $chat, "❌ Balans yetarli emas. Kerak: " . nf($n['price'])); return; }
   bbal($b, $uid, -$n['price']); q("UPDATE numbers SET status='sotildi',buyer=? WHERE id=?", [$uid, $n['id']]);
   send($tok, $chat, "✅ <b>Raqam sotib olindi!</b>\n📞 <code>" . h($n['num']) . "</code>\n\nSMS kodi kelishi bilan sizga yuboriladi. «📋 Raqamlarim» orqali ham tekshirishingiz mumkin.");
   send($tok, $b['owner'], "🛎 #{$n['id']} raqam sotildi: " . h($n['num']) . "\nKod yuborish: /kod {$n['id']} KOD");
  } elseif ($p[0] === 'nk') {
   $n = row('SELECT * FROM numbers WHERE id=? AND buyer=?', [(int)$p[1], $uid]);
   send($tok, $chat, $n ? ($n['code'] ? "🔑 Kod: <code>" . h($n['code']) . "</code>" : "⏳ Kod hali kelmadi.") : '⚠️ Topilmadi.');
  }
  return;
 }
 switch ($txt) {
  case '📱 Raqam sotib olish':
   $k = []; foreach (rows("SELECT country,COUNT(*) c,MIN(price) p FROM numbers WHERE bot_id=? AND status='bor' GROUP BY country", [$b['id']]) as $c) $k[] = [B("{$c['country']} · {$c['c']} ta · " . nf($c['p']) . ' dan', 'nc:' . $c['country'])];
   send($tok, $chat, $k ? "🌍 Mamlakatni tanlang:" : "⚠️ Hozircha raqamlar yoʻq.", $k ? IK($k) : null); return;
  case '💰 Balansim': send($tok, $chat, "💰 Balansingiz: <b>" . nf(bu($b, $uid)['bal']) . "</b>\n🆔 ID: <code>$uid</code>\n\n" . h(bcfg($b, 'aloqa', 'Balans toʻldirish uchun admin bilan bogʻlaning.'))); return;
  case '☎️ Aloqa': send($tok, $chat, h(bcfg($b, 'aloqa', 'Aloqa maʼlumoti kiritilmagan.'))); return;
  case '📋 Raqamlarim':
   $k = []; foreach (rows('SELECT * FROM numbers WHERE bot_id=? AND buyer=? ORDER BY id DESC LIMIT 15', [$b['id'], $uid]) as $n) $k[] = [B($n['num'] . ($n['code'] ? ' ✅' : ' ⏳') . ' · 🔄 kod', 'nk:' . $n['id'])];
   send($tok, $chat, $k ? "📋 <b>Raqamlaringiz:</b>" : "Sizda hali raqam yoʻq.", $k ? IK($k) : null); return;
 }
 send($tok, $chat, "👋 <b>Virtual raqamlar botiga xush kelibsiz!</b>", $kb);
}
function O_nomer($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 switch ($c) {
  case '/nomer':
   $x = array_map('trim', explode('|', $a));
   if (count($x) < 3) { send($tok, $chat, "❌ /nomer Mamlakat|+99890...|narx"); return true; }
   $id = ins('INSERT INTO numbers(bot_id,country,num,price) VALUES(?,?,?,?)', [$b['id'], $x[0], $x[1], (float)$x[2]]); send($tok, $chat, "✅ Raqam qoʻshildi. ID: $id"); return true;
  case '/nomerochir': q('DELETE FROM numbers WHERE id=? AND bot_id=?', [(int)$a, $b['id']]); send($tok, $chat, '🗑 Oʻchirildi.'); return true;
  case '/nomerlar':
   $s = "📋 <b>Raqamlar</b>\n"; foreach (rows('SELECT * FROM numbers WHERE bot_id=? ORDER BY id DESC LIMIT 50', [$b['id']]) as $n) $s .= "#{$n['id']} " . h($n['country']) . " " . h($n['num']) . " — {$n['status']}\n";
   send($tok, $chat, $s); return true;
  case '/kod':
   $x = preg_split('~\s+~', $a, 2); $n = row('SELECT * FROM numbers WHERE id=? AND bot_id=?', [(int)$x[0], $b['id']]);
   if (!$n || !$n['buyer'] || count($x) < 2) { send($tok, $chat, "❌ /kod id KOD (raqam sotilgan boʻlishi kerak)"); return true; }
   q('UPDATE numbers SET code=? WHERE id=?', [$x[1], $n['id']]);
   send($tok, $n['buyer'], "🔑 <b>" . h($n['num']) . "</b> raqamiga kod keldi:\n<code>" . h($x[1]) . "</code>");
   send($tok, $chat, '✅ Kod yuborildi.'); return true;
 }
 return false;
}

/* ── 4. STARS PREMIUM BOT ── */
function T_stars($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 if (isset($m['successful_payment'])) {
  $sp = $m['successful_payment']; $pid = (int)substr($sp['invoice_payload'], 1);
  $p = row('SELECT * FROM products WHERE id=? AND bot_id=?', [$pid, $b['id']]);
  if ($p) {
   q('INSERT INTO sales(bot_id,uid,product_id,stars,created) VALUES(?,?,?,?,?)', [$b['id'], $uid, $pid, $sp['total_amount'], now()]);
   send($tok, $chat, "✅ <b>Toʻlov qabul qilindi!</b>\n\n" . h($p['delivery']));
   send($tok, $b['owner'], "⭐️ Yangi sotuv: " . h($p['title']) . " — {$sp['total_amount']} Stars\n👤 <a href=\"tg://user?id=$uid\">$uid</a>");
  }
  return;
 }
 if ($cd !== null && str_starts_with($cd, 'pr:')) {
  $p = row('SELECT * FROM products WHERE id=? AND bot_id=?', [(int)substr($cd, 3), $b['id']]); if (!$p) return;
  $r = api($tok, 'sendInvoice', ['chat_id' => $chat, 'title' => $p['title'], 'description' => 'Raqamli mahsulot: ' . $p['title'], 'payload' => 'p' . $p['id'], 'currency' => 'XTR', 'prices' => [['label' => $p['title'], 'amount' => (int)$p['stars']]]]);
  if (!($r['ok'] ?? false)) send($tok, $chat, '⚠️ Hisob-faktura yaratib boʻlmadi.');
  return;
 }
 $k = []; foreach (rows('SELECT * FROM products WHERE bot_id=?', [$b['id']]) as $p) $k[] = [B('⭐️ ' . $p['title'] . ' — ' . $p['stars'] . ' Stars', 'pr:' . $p['id'])];
 send($tok, $chat, $k ? "⭐️ <b>Stars Premium doʻkoni</b>\nMahsulotni tanlang va Telegram Stars bilan toʻlang:" : "⚠️ Hozircha mahsulotlar yoʻq.", $k ? IK($k) : null);
}
function O_stars($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 switch ($c) {
  case '/mahsulot':
   $x = array_map('trim', explode('|', $a, 3));
   if (count($x) < 3 || !ctype_digit($x[1])) { send($tok, $chat, "❌ /mahsulot Nom|stars|yetkazish matni"); return true; }
   $id = ins('INSERT INTO products(bot_id,title,stars,delivery) VALUES(?,?,?,?)', [$b['id'], $x[0], (int)$x[1], $x[2]]); send($tok, $chat, "✅ Qoʻshildi. ID: $id"); return true;
  case '/mahsulotochir': q('DELETE FROM products WHERE id=? AND bot_id=?', [(int)$a, $b['id']]); send($tok, $chat, '🗑 Oʻchirildi.'); return true;
  case '/mahsulotlar': $s = "📋 <b>Mahsulotlar</b>\n"; foreach (rows('SELECT * FROM products WHERE bot_id=?', [$b['id']]) as $p) $s .= "#{$p['id']} " . h($p['title']) . " — {$p['stars']}⭐️\n"; send($tok, $chat, $s); return true;
  case '/sotuvlar':
   $s = "🧾 <b>Oxirgi sotuvlar</b>\nJami: " . (int)val('SELECT COALESCE(SUM(stars),0) FROM sales WHERE bot_id=?', [$b['id']]) . "⭐️\n";
   foreach (rows('SELECT * FROM sales WHERE bot_id=? ORDER BY id DESC LIMIT 15', [$b['id']]) as $x) $s .= "• {$x['uid']} — {$x['stars']}⭐️ — " . substr($x['created'], 5, 11) . "\n";
   send($tok, $chat, $s); return true;
 }
 return false;
}

/* ── 5 va 6. KINO BOT / KINO BOT PRO ── */
function T_kino($b, $uid, $chat, $m, $txt, $cd, $f) { kinoCore($b, $uid, $chat, $m, $txt, $cd, false); }
function T_kinopro($b, $uid, $chat, $m, $txt, $cd, $f) { kinoCore($b, $uid, $chat, $m, $txt, $cd, true); }
function sendMovie($b, $chat, $uid, $mv, $pro) {
 $tok = $b['token']; q('UPDATE movies SET views=views+1 WHERE id=?', [$mv['id']]);
 $p = ['chat_id' => $chat, $mv['kind'] => $mv['file_id'], 'caption' => "🎬 <b>" . h($mv['title']) . "</b>\n🎟 Kod: <code>" . h($mv['code']) . "</code>" . ($pro ? "\n👁 " . ($mv['views'] + 1) : ''), 'parse_mode' => 'HTML', 'protect_content' => false];
 if ($pro) { $fav = val('SELECT 1 FROM favs WHERE bot_id=? AND uid=? AND movie_id=?', [$b['id'], $uid, $mv['id']]); $p['reply_markup'] = IK([[B($fav ? '💔 Saqlanganlardan olish' : '⭐ Saqlash', 'f:' . $mv['id'])]]); }
 api($tok, 'send' . ucfirst($mv['kind']), $p);
}
function kinoCore($b, $uid, $chat, $m, $txt, $cd, $pro) {
 $tok = $b['token']; $owner = (int)$b['owner'] === $uid || isAdmin($uid);
 $kb = RK($pro ? [['🔎 Qidirish', '🔥 Top kinolar'], ['⭐ Saqlanganlar']] : [['🔎 Qidirish']]);
 if ($pro && !($owner) && !chGate($b, $uid, $chat)) return;
 if ($cd === 'ob:check') { if ($pro) send($tok, $chat, "✅ Rahmat! Endi kino kodini yuboring.", $kb); return; }
 if ($cd !== null) {
  $p = explode(':', $cd); $mv = row('SELECT * FROM movies WHERE id=? AND bot_id=?', [(int)($p[1] ?? 0), $b['id']]); if (!$mv) return;
  if ($p[0] === 'k') sendMovie($b, $chat, $uid, $mv, $pro);
  if ($p[0] === 'f' && $pro) {
   if (val('SELECT 1 FROM favs WHERE bot_id=? AND uid=? AND movie_id=?', [$b['id'], $uid, $mv['id']])) { q('DELETE FROM favs WHERE bot_id=? AND uid=? AND movie_id=?', [$b['id'], $uid, $mv['id']]); send($tok, $chat, '💔 Saqlanganlardan olindi.'); }
   else { q('INSERT OR IGNORE INTO favs(bot_id,uid,movie_id) VALUES(?,?,?)', [$b['id'], $uid, $mv['id']]); send($tok, $chat, '⭐ Saqlandi!'); }
  }
  return;
 }
 $media = null;
 foreach (['video', 'document', 'animation'] as $k) if (isset($m[$k])) $media = [$k, $m[$k]['file_id']];
 if ($owner && $media) {
  $cap = trim($m['caption'] ?? '');
  if ($cap === '') { send($tok, $chat, "❌ Izohga <code>kod | Nomi</code> yozing."); return; }
  if (str_contains($cap, '|')) { [$code, $title] = array_map('trim', explode('|', $cap, 2)); }
  else { $code = (string)(1 + (int)val('SELECT COALESCE(MAX(CAST(code AS INTEGER)),0) FROM movies WHERE bot_id=?', [$b['id']])); $title = $cap; }
  if (val('SELECT 1 FROM movies WHERE bot_id=? AND code=?', [$b['id'], $code])) { send($tok, $chat, "⚠️ <b>$code</b> kodi band."); return; }
  q('INSERT INTO movies(bot_id,code,file_id,kind,title,created) VALUES(?,?,?,?,?,?)', [$b['id'], $code, $media[1], $media[0], $title, now()]);
  send($tok, $chat, "✅ Qoʻshildi: <b>" . h($title) . "</b>\n🎟 Kod: <code>" . h($code) . "</code>");
  if ($pro) foreach (rows('SELECT uid FROM bu WHERE bot_id=? AND uid<>?', [$b['id'], $uid]) as $x) { send($tok, $x['uid'], "🆕 <b>Yangi kino!</b>\n🎬 " . h($title) . "\n🎟 Kod: <code>" . h($code) . "</code>"); usleep(45000); }
  return;
 }
 if ($txt === '' ) return;
 if (str_starts_with($txt, '/start')) { send($tok, $chat, "🎬 <b>Kino botiga xush kelibsiz!</b>\nKino <b>kodini</b> yoki nomini yuboring.", $kb); return; }
 if ($txt === '🔎 Qidirish') { send($tok, $chat, "🔎 Kino kodi yoki nomini yozing:"); return; }
 if ($pro && $txt === '🔥 Top kinolar') {
  $k = []; foreach (rows('SELECT * FROM movies WHERE bot_id=? ORDER BY views DESC LIMIT 10', [$b['id']]) as $mv) $k[] = [B("🎬 {$mv['title']} · 👁 {$mv['views']}", 'k:' . $mv['id'])];
  send($tok, $chat, $k ? "🔥 <b>Eng koʻp koʻrilganlar:</b>" : "Hozircha kinolar yoʻq.", $k ? IK($k) : null); return;
 }
 if ($pro && $txt === '⭐ Saqlanganlar') {
  $k = []; foreach (rows('SELECT m.* FROM movies m JOIN favs f ON f.movie_id=m.id WHERE f.bot_id=? AND f.uid=? LIMIT 30', [$b['id'], $uid]) as $mv) $k[] = [B('🎬 ' . $mv['title'], 'k:' . $mv['id'])];
  send($tok, $chat, $k ? "⭐ <b>Saqlanganlar:</b>" : "Saqlangan kinolar yoʻq.", $k ? IK($k) : null); return;
 }
 $mv = row('SELECT * FROM movies WHERE bot_id=? AND LOWER(code)=LOWER(?)', [$b['id'], $txt]);
 if ($mv) { sendMovie($b, $chat, $uid, $mv, $pro); return; }
 $res = rows('SELECT * FROM movies WHERE bot_id=? AND title LIKE ? ORDER BY views DESC LIMIT 10', [$b['id'], '%' . $txt . '%']);
 if (!$res) { send($tok, $chat, "😔 Hech narsa topilmadi. Kodni tekshirib qayta yuboring."); return; }
 $k = []; foreach ($res as $r) $k[] = [B('🎬 ' . $r['title'] . ' · ' . $r['code'], 'k:' . $r['id'])];
 send($tok, $chat, "🔎 <b>Natijalar:</b>", IK($k));
}
function O_kino($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 if ($c === '/kinoochir') { q('DELETE FROM movies WHERE bot_id=? AND code=?', [$b['id'], $a]); send($tok, $chat, '🗑 Oʻchirildi.'); return true; }
 if ($c === '/kinolar') { send($tok, $chat, "🎬 Kinolar soni: <b>" . val('SELECT COUNT(*) FROM movies WHERE bot_id=?', [$b['id']]) . "</b>"); return true; }
 if ($c === '/start') return false;
 return false;
}
function O_kinopro($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 if ($c === '/stat') {
  send($tok, $chat, "📊 <b>Kino Pro statistikasi</b>\n👥 Foydalanuvchilar: " . val('SELECT COUNT(*) FROM bu WHERE bot_id=?', [$b['id']]) . "\n🔥 Bugun faol: " . val('SELECT COUNT(*) FROM daily WHERE bot_id=? AND day=?', [$b['id'], today()]) . "\n🎬 Kinolar: " . val('SELECT COUNT(*) FROM movies WHERE bot_id=?', [$b['id']]) . "\n👁 Jami koʻrishlar: " . (int)val('SELECT COALESCE(SUM(views),0) FROM movies WHERE bot_id=?', [$b['id']]) . "\n✅ Obuna tasdiqlaganlar: " . val('SELECT COUNT(*) FROM bu WHERE bot_id=? AND verified=1', [$b['id']]) . "\n⭐ Saqlashlar: " . val('SELECT COUNT(*) FROM favs WHERE bot_id=?', [$b['id']]));
  return true;
 }
 return O_kino($b, $c, $a, $chat, $m);
}

/* ── 7. EMOJI YARATUVCHI BOT ── */
function toPng($src, $mode, $out) {
 $im = @imagecreatefromstring((string)file_get_contents($src));
 if (!$im) return false;
 $w = imagesx($im); $h = imagesy($im);
 if ($mode === 'emoji') { $s = min($w, $h); $nw = $nh = 100; $sx = (int)(($w - $s) / 2); $sy = (int)(($h - $s) / 2); $sw = $sh = $s; }
 else { $k = 512 / max($w, $h); $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k)); $sx = $sy = 0; $sw = $w; $sh = $h; }
 $n = imagecreatetruecolor($nw, $nh);
 imagealphablending($n, false); imagesavealpha($n, true);
 imagefill($n, 0, 0, imagecolorallocatealpha($n, 0, 0, 0, 127));
 imagecopyresampled($n, $im, 0, 0, $sx, $sy, $nw, $nh, $sw, $sh);
 return imagepng($n, $out);
}
function T_emoji($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 $kb = RK([['😀 Premium emoji yaratish', '🖼 Stiker yaratish'], ['📚 Toʻplamlarim']]);
 [$st, $d] = bst($b, $uid);
 if ($cd !== null) return;
 if (str_starts_with($txt, '/start')) { bsave($b, $uid, '', $d); send($tok, $chat, "😎 <b>Emoji va stiker yaratuvchi bot</b>\nRasm yuboring — men uni maxsus emoji yoki stikerga aylantiraman.", $kb); return; }
 if ($txt === '😀 Premium emoji yaratish' || $txt === '🖼 Stiker yaratish') {
  $mode = str_starts_with($txt, '😀') ? 'emoji' : 'sticker';
  bsave($b, $uid, 'photo', array_merge($d, ['mode' => $mode]));
  send($tok, $chat, "📸 Rasm yuboring (kvadrat rasm yaxshiroq). Izohga emoji yozsangiz, shu emojiga bogʻlanadi."); return;
 }
 if ($txt === '📚 Toʻplamlarim') {
  $s = "📚 <b>Toʻplamlaringiz</b>\n";
  if (!empty($d['sets']['emoji'])) $s .= "😀 https://t.me/addemoji/" . $d['sets']['emoji'] . "\n";
  if (!empty($d['sets']['sticker'])) $s .= "🖼 https://t.me/addstickers/" . $d['sets']['sticker'] . "\n";
  send($tok, $chat, empty($d['sets']) ? "Hali toʻplam yoʻq." : $s); return;
 }
 if ($st !== 'photo') { if (!empty($m['photo']) || isset($m['document'])) send($tok, $chat, "Avval tugma orqali turini tanlang 👇", $kb); return; }
 $fid = null;
 if (!empty($m['photo'])) $fid = end($m['photo'])['file_id'];
 elseif (isset($m['document']) && str_starts_with($m['document']['mime_type'] ?? '', 'image/')) $fid = $m['document']['file_id'];
 if (!$fid) { send($tok, $chat, "❌ Iltimos, rasm yuboring."); return; }
 $mode = $d['mode'] ?? 'emoji';
 $emo = preg_match('~[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]~u', $m['caption'] ?? '', $em) ? $em[0] : '😀';
 $tmp = sys_get_temp_dir() . '/em_' . $b['id'] . '_' . $uid . '_' . mt_rand(); $png = $tmp . '.png';
 if (!dl($tok, $fid, $tmp) || !toPng($tmp, $mode, $png)) { @unlink($tmp); send($tok, $chat, "❌ Rasmni qayta ishlab boʻlmadi."); return; }
 $item = ['sticker' => 'attach://st', 'format' => 'static', 'emoji_list' => [$emo]];
 $set = $d['sets'][$mode] ?? null; $ok = false;
 if ($set) { $r = api($tok, 'addStickerToSet', ['user_id' => $uid, 'name' => $set, 'sticker' => $item], ['st' => $png]); $ok = $r['ok'] ?? false; }
 if (!$ok) {
  $name = 'e' . substr(md5($uid . microtime()), 0, 10) . '_by_' . strtolower($b['username']);
  $r = api($tok, 'createNewStickerSet', ['user_id' => $uid, 'name' => $name, 'title' => $mode === 'emoji' ? 'Mening emojilarim' : 'Mening stikerlarim', 'stickers' => [$item], 'sticker_type' => $mode === 'emoji' ? 'custom_emoji' : 'regular'], ['st' => $png]);
  $ok = $r['ok'] ?? false; if ($ok) { $d['sets'][$mode] = $name; $set = $name; }
 }
 @unlink($tmp); @unlink($png);
 if (!$ok) { send($tok, $chat, "❌ Xatolik: " . h($r['description'] ?? 'nomaʼlum')); return; }
 bsave($b, $uid, 'photo', $d);
 send($tok, $chat, "✅ <b>Tayyor!</b>\n🔗 " . ($mode === 'emoji' ? "https://t.me/addemoji/$set" : "https://t.me/addstickers/$set") . "\n\nYana rasm yuboravering — toʻplamga qoʻshiladi.");
}

/* ── 8. ANONIM CHAT BOT ── */
function T_anon($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 $kb = RK([['🔍 Suhbatdosh topish'], ['⏭ Keyingisi', '🛑 Toʻxtatish']]);
 $me = bu($b, $uid);
 if ($cd !== null) return;
 $stop = function ($notify = true) use ($b, $uid, $me, $tok, $kb) {
  $me = row('SELECT * FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
  if ($me['partner']) {
   q('UPDATE bu SET partner=0,waiting=0 WHERE bot_id=? AND uid IN(?,?)', [$b['id'], $uid, $me['partner']]);
   if ($notify) send($tok, $me['partner'], "🚫 Suhbatdosh suhbatni yakunladi.", $kb);
   return true;
  }
  if ($me['waiting']) { q('UPDATE bu SET waiting=0 WHERE bot_id=? AND uid=?', [$b['id'], $uid]); return true; }
  return false;
 };
 $find = function () use ($b, $uid, $chat, $tok, $kb) {
  $pdo = db(); $pdo->exec('BEGIN IMMEDIATE'); $pr = null;
  try {
   $me = row('SELECT * FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
   if ($me['partner']) { $pdo->exec('COMMIT'); send($tok, $chat, "ℹ️ Siz allaqachon suhbatdasiz."); return; }
   $o = row('SELECT uid FROM bu WHERE bot_id=? AND waiting=1 AND uid<>? AND partner=0 ORDER BY rowid LIMIT 1', [$b['id'], $uid]);
   if ($o) { q('UPDATE bu SET partner=?,waiting=0 WHERE bot_id=? AND uid=?', [$o['uid'], $b['id'], $uid]); q('UPDATE bu SET partner=?,waiting=0 WHERE bot_id=? AND uid=?', [$uid, $b['id'], $o['uid']]); $pr = $o['uid']; }
   else q('UPDATE bu SET waiting=1 WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
   $pdo->exec('COMMIT');
  } catch (Throwable $e) { $pdo->exec('ROLLBACK'); throw $e; }
  if ($pr) { send($tok, $chat, "✅ <b>Suhbatdosh topildi!</b> Yozishingiz mumkin 💬\n🛑 Yakunlash: «Toʻxtatish»", $kb); send($tok, $pr, "✅ <b>Suhbatdosh topildi!</b> Yozishingiz mumkin 💬\n🛑 Yakunlash: «Toʻxtatish»", $kb); }
  else send($tok, $chat, "🔍 Suhbatdosh qidirilmoqda... Biroz kuting.", $kb);
 };
 if (str_starts_with($txt, '/start')) { send($tok, $chat, "🕵️ <b>Anonim chat</b>\nTasodifiy suhbatdosh bilan anonim yozishing. Shaxsingiz sir saqlanadi.", $kb); return; }
 switch ($txt) {
  case '🔍 Suhbatdosh topish': $find(); return;
  case '🛑 Toʻxtatish': send($tok, $chat, $stop() ? "🛑 Suhbat toʻxtatildi." : "ℹ️ Siz suhbatda emassiz.", $kb); return;
  case '⏭ Keyingisi': $stop(); $find(); return;
 }
 $me = row('SELECT * FROM bu WHERE bot_id=? AND uid=?', [$b['id'], $uid]);
 if ($me['partner']) {
  $r = api($tok, 'copyMessage', ['chat_id' => $me['partner'], 'from_chat_id' => $chat, 'message_id' => $m['message_id']]);
  if (!($r['ok'] ?? false)) { q('UPDATE bu SET partner=0 WHERE bot_id=? AND uid IN(?,?)', [$b['id'], $uid, $me['partner']]); send($tok, $chat, "⚠️ Suhbatdosh botni tark etgan. Yangisini toping.", $kb); }
 } else send($tok, $chat, "ℹ️ Suhbatdosh topish uchun «🔍 Suhbatdosh topish» tugmasini bosing.", $kb);
}

/* ── 9. AI BOT ── */
function O_ai($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 switch ($c) {
  case '/kalit': bset($b, 'key', $a); api($tok, 'deleteMessage', ['chat_id' => $chat, 'message_id' => $m['message_id']]); send($tok, $chat, '✅ API kalit saqlandi (xabar oʻchirildi).'); return true;
  case '/provayder':
   if (!in_array(strtolower($a), ['claude', 'openai'])) { send($tok, $chat, '❌ /provayder claude yoki openai'); return true; }
   bset($b, 'prov', strtolower($a)); send($tok, $chat, '✅ Provayder: ' . strtolower($a)); return true;
  case '/model': bset($b, 'model', $a); send($tok, $chat, '✅ Model: ' . h($a)); return true;
  case '/prompt': bset($b, 'prompt', $a); send($tok, $chat, '✅ Tizim koʻrsatmasi saqlandi.'); return true;
 }
 return false;
}
function T_ai($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 if ($cd !== null || $txt === '') { if ($cd === null && !str_starts_with($txt, '/')) send($tok, $chat, "✍️ Iltimos, matn yozing."); return; }
 if (str_starts_with($txt, '/start')) { send($tok, $chat, "🤖 <b>Salom!</b> Men sunʼiy intellekt yordamchisiman. Savolingizni yozing.\n\n/yangi — suhbatni tozalash"); return; }
 if ($txt === '/yangi') { q('DELETE FROM aihist WHERE bot_id=? AND uid=?', [$b['id'], $uid]); send($tok, $chat, '🧹 Suhbat tozalandi.'); return; }
 $key = bcfg($b, 'key'); $prov = bcfg($b, 'prov', 'claude');
 if (!$key) { send($tok, $chat, "⚠️ Bot hali sozlanmagan. Egasi /kalit buyrugʻi bilan API kalitni kiritishi kerak."); return; }
 api($tok, 'sendChatAction', ['chat_id' => $chat, 'action' => 'typing']);
 $sys = bcfg($b, 'prompt', "Sen foydali yordamchisan. Foydalanuvchi qaysi tilda yozsa, oʻsha tilda (asosan oʻzbek tilida) aniq va qisqa javob ber.");
 $msgs = [];
 foreach (rows('SELECT role,content FROM aihist WHERE bot_id=? AND uid=? ORDER BY id', [$b['id'], $uid]) as $h) $msgs[] = ['role' => $h['role'], 'content' => $h['content']];
 $msgs[] = ['role' => 'user', 'content' => $txt];
 if ($prov === 'openai') {
  $r = http_json('https://api.openai.com/v1/chat/completions', ['Authorization: Bearer ' . $key, 'Content-Type: application/json'], ['model' => bcfg($b, 'model', 'gpt-4o-mini'), 'messages' => array_merge([['role' => 'system', 'content' => $sys]], $msgs)]);
  $out = $r['choices'][0]['message']['content'] ?? null; $err = $r['error']['message'] ?? 'nomaʼlum xatolik';
 } else {
  $r = http_json('https://api.anthropic.com/v1/messages', ['x-api-key: ' . $key, 'anthropic-version: 2023-06-01', 'content-type: application/json'], ['model' => bcfg($b, 'model', 'claude-sonnet-5-5'), 'max_tokens' => 1500, 'system' => $sys, 'messages' => $msgs]);
  $out = $r['content'][0]['text'] ?? null; $err = $r['error']['message'] ?? 'nomaʼlum xatolik';
 }
 if (!$out) { send($tok, $chat, "⚠️ AI xatosi: " . $err, null, ['plain' => 1]); return; }
 q('INSERT INTO aihist(bot_id,uid,role,content) VALUES(?,?,?,?)', [$b['id'], $uid, 'user', $txt]);
 q('INSERT INTO aihist(bot_id,uid,role,content) VALUES(?,?,?,?)', [$b['id'], $uid, 'assistant', $out]);
 q('DELETE FROM aihist WHERE bot_id=? AND uid=? AND id NOT IN(SELECT id FROM aihist WHERE bot_id=? AND uid=? ORDER BY id DESC LIMIT 12)', [$b['id'], $uid, $b['id'], $uid]);
 foreach (mb_str_split($out, 4000) as $part) send($tok, $chat, $part, null, ['plain' => 1]);
}

/* ── 10. OBUNACHI BOT ── */
function T_obuna($b, $uid, $chat, $m, $txt, $cd, $f) {
 $tok = $b['token'];
 if ($cd !== null && $cd !== 'ob:check') return;
 if ($cd === null && $txt !== '' && !str_starts_with($txt, '/start')) { if (chGate($b, $uid, $chat)) send($tok, $chat, h(bcfg($b, 'xabar', "✅ Obunangiz tasdiqlangan!"))); return; }
 if (!rows('SELECT 1 FROM bch WHERE bot_id=? LIMIT 1', [$b['id']])) { send($tok, $chat, "⚠️ Hali kanal sozlanmagan."); return; }
 if (chGate($b, $uid, $chat)) send($tok, $chat, "✅ <b>Rahmat!</b>\n" . h(bcfg($b, 'xabar', "Obunangiz tasdiqlandi.")));
}
function O_obuna($b, $c, $a, $chat, $m) {
 $tok = $b['token'];
 if ($c === '/xabar') { bset($b, 'xabar', $a); send($tok, $chat, '✅ Saqlandi.'); return true; }
 if ($c === '/stat') {
  $s = "📊 <b>Obuna statistikasi</b>\n👥 Foydalanuvchilar: " . val('SELECT COUNT(*) FROM bu WHERE bot_id=?', [$b['id']]) . "\n✅ Obuna tasdiqlaganlar: " . val('SELECT COUNT(*) FROM bu WHERE bot_id=? AND verified=1', [$b['id']]) . "\n➕ Bugun qoʻshilganlar: " . val("SELECT COUNT(*) FROM sublog WHERE bot_id=? AND ev='qoshildi' AND created LIKE ?", [$b['id'], today() . '%']) . "\n➖ Bugun chiqqanlar: " . val("SELECT COUNT(*) FROM sublog WHERE bot_id=? AND ev='chiqdi' AND created LIKE ?", [$b['id'], today() . '%']) . "\n\n📢 <b>Kanallar:</b>\n";
  foreach (rows('SELECT * FROM bch WHERE bot_id=?', [$b['id']]) as $k) $s .= "• " . h($k['chat']) . "\n";
  send($tok, $chat, $s); return true;
 }
 return false;
}

/* ═════════════════════════ KIRISH NUQTASI ═════════════════════════ */
function run($c) {
 global $CFG; $CFG = $c;
 if (isset($_GET['setup'])) {
  if (!hash_equals((string)cfg('setup_key'), (string)$_GET['setup'])) { http_response_code(403); echo 'Ruxsat yoʻq'; return; }
  header('Content-Type: text/plain; charset=utf-8');
  @mkdir(cfg('upload'), 0755, true); db();
  $r = api(cfg('token'), 'setWebhook', ['url' => cfg('url'), 'secret_token' => sig('main'), 'allowed_updates' => ['message', 'callback_query'], 'drop_pending_updates' => true]);
  echo "Asosiy bot webhook: " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
  foreach (rows('SELECT * FROM bots') as $b) { $h = hook($b); echo "Bot #{$b['id']} @{$b['username']}: " . (($h['ok'] ?? false) ? 'OK' : ($h['description'] ?? 'xato')) . "\n"; }
  return;
 }
 $raw = file_get_contents('php://input');
 if (!$raw) { echo 'HYPER BUILDER ishlayapti ✅'; return; }
 ignore_user_abort(true); set_time_limit(0);
 header('Content-Type: text/plain'); header('Connection: close'); header('Content-Length: 2'); echo 'OK';
 if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); else { @ob_end_flush(); flush(); }
 $u = json_decode($raw, true);
 if (!is_array($u)) return;
 $hdr = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
 try {
  if (isset($_GET['b'])) {
   $b = row('SELECT * FROM bots WHERE id=?', [(int)$_GET['b']]);
   if (!$b || !hash_equals(sig('c' . $b['id']), $hdr)) return;
   C($b, $u);
  } else {
   if (!hash_equals(sig('main'), $hdr)) return;
   P($u);
  }
 } catch (Throwable $e) { error_log('[HYPER BUILDER] ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine()); }
}

/* ═════════════════════════ JOYLASH SOZLAMALARI ═════════════════════════
 Bot Token: 8857545602:AAFhbI1t6u3QjxkF_GNPuBmhVtkuWvr55JU
 Admin ID : 6396404041
 ═════════════════════════════════════════════════════════════════════ */
run([
 'token'     => "8857545602:AAFhbI1t6u3QjxkF_GNPuBmhVtkuWvr55JU",          // Bot Token
 'admin'     => 6396404041,                              // Asosiy admin Telegram ID
 'url'       => 'http://localhost/bot.php',      // https://github.com/kingfc21uzb-max/telegram-bot-hyper_builder
 'setup_key' => 'KING_LEGEND',       // ?setup=... uchun kalit
 'db'        => __DIR__ . '/hyper_builder.sqlite',
 'upload'    => __DIR__ . '/uploads',
]);
