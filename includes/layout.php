<?php
declare(strict_types=1);

function icon(string $name, int $size = 22): string
{
    $p = [
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'life-buoy' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/>',
        'alert' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'siren' => '<path d="M7 18v-6a5 5 0 0 1 10 0v6"/><path d="M5 21a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2"/><path d="M6 18h12"/><path d="M12 2v3"/><path d="m4.9 5.9 2.1 2.1"/><path d="m19.1 5.9-2.1 2.1"/>',
        'bike' => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h3"/>',
        'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C1.4 11.3 1 12.1 1 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.81.3 1.61.54 2.38a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.77.24 1.57.42 2.38.54A2 2 0 0 1 22 16.92z"/>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'send' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'share' => '<path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'id-card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M14 10h6"/><path d="M14 14h4"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'smartphone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'circle' => '<circle cx="12" cy="12" r="10"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'camera' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'refresh' => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'dot' => '<circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/>',
    ];
    $svg = $p[$name] ?? $p['circle'];
    return '<svg class="ico" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg>';
}

function trd_mark(int $size = 36): string
{
    $s = (int)$size;
    return '<svg class="mark" width="' . $s . '" height="' . $s . '" viewBox="0 0 64 64" aria-hidden="true">'
        . '<rect width="64" height="64" rx="16" fill="#E85D04"/>'
        . '<circle cx="32" cy="32" r="20" fill="#FBF7F2"/>'
        . '<circle cx="32" cy="32" r="16" fill="none" stroke="#0F766E" stroke-width="2.4"/>'
        . '<rect x="18" y="22" width="28" height="6" rx="3" fill="#E85D04"/>'
        . '<rect x="29" y="22" width="6" height="24" rx="3" fill="#E85D04"/>'
        . '<rect x="22" y="48" width="20" height="3" rx="1.5" fill="#0F766E"/>'
        . '</svg>';
}

function html_head(string $title, array $opts = []): void
{
    $company = setting('company_name', 'TRD Rider Hub');
    $full = $title !== '' ? ($title . ' · ' . $company) : $company;
    $base = app_base();
    $lang = current_lang();
    $dir = lang_dir();
    $bodyClass = trim((string)($opts['body'] ?? '') . ($lang === 'ar' ? ' lang-ar' : ''));
    $theme = (string)($opts['theme'] ?? '#E85D04');
    ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>" data-base="<?= e($base) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= e($theme) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TRD Hub">
    <meta name="application-name" content="TRD Hub">
    <meta name="description" content="Talabat Rider Division · Oman — rider and driver operations.">
    <title><?= e($full) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Figtree:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <link rel="manifest" href="<?= e(asset('manifest.webmanifest')) ?>">
    <link rel="icon" type="image/png" href="<?= e(asset('favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('icon-180.png')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php
}

function html_foot(): void
{
    ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
<?php
}

function render_flashes(): void
{
    $items = flash_get();
    if (!$items) {
        return;
    }
    echo '<div class="flashes" role="status">';
    foreach ($items as $f) {
        $type = $f['type'] ?? 'info';
        echo '<div class="flash flash-' . e($type) . '"><span>' . e((string)($f['msg'] ?? '')) . '</span></div>';
    }
    echo '</div>';
}

function status_pill(string $status): string
{
    return '<span class="pill pill-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

function back_link(string $href, string $label = ''): string
{
    $label = $label !== '' ? $label : t('common.back');
    return '<a class="back" href="' . e(url($href)) . '">' . icon('chevron-left', 18) . ' ' . e($label) . '</a>';
}

function layout_guest_start(string $title, array $opts = []): void
{
    html_head($title, ['body' => 'guest ' . ($opts['body'] ?? '')]);
    echo '<div class="phone">';
    echo '<header class="top top-guest">';
    echo '<a class="brand" href="' . e(url('')) . '">' . trd_mark(32) . '<span>TRD <em>Hub</em></span></a>';
    echo '<div class="top-actions">';
    echo lang_switch_html();
    if (empty($opts['hide_install'])) {
        echo '<a class="ghost-link" href="' . e(url('install-app')) . '">' . icon('download', 18) . ' ' . e(t('nav.install')) . '</a>';
    }
    echo '</div></header>';
    echo '<main class="main">';
    render_flashes();
}

function layout_guest_end(): void
{
    echo '</main><footer class="fineprint">' . e(setting('company_legal', 'Talabat Rider Division · Oman')) . '</footer></div>';
    html_foot();
}

function layout_app_start(string $title, array $opts = []): void
{
    $user = $opts['user'] ?? current_user();
    $app = $opts['app'] ?? ($user ? latest_application((int)$user['id']) : null);
    $fleet = $user ? is_fleet_member($user, $app) : false;
    $unread = $user ? unread_count((int)$user['id']) : 0;
    html_head($title, ['body' => 'app-body ' . ($opts['body'] ?? '')]);
    echo '<div class="phone' . (!empty($opts['flush']) ? ' phone-flush' : '') . '">';
    echo '<header class="top">';
    if (!empty($opts['back'])) {
        echo back_link((string)$opts['back'], (string)($opts['back_label'] ?? t('common.back')));
    } else {
        echo '<a class="brand" href="' . e(url('home')) . '">' . trd_mark(30) . '<span>TRD <em>Hub</em></span></a>';
    }
    echo '<div class="top-actions">';
    echo lang_switch_html();
    echo '<a class="icon-btn" href="' . e(url('inbox')) . '" aria-label="' . e(t('nav.inbox')) . '">';
    echo icon('bell', 20);
    if ($unread > 0) {
        echo '<i class="badge">' . ($unread > 9 ? '9+' : (int)$unread) . '</i>';
    }
    echo '</a></div></header>';
    echo '<main class="main' . ($fleet ? ' main-tabs' : ' main-tabs-lite') . '">';
    render_flashes();
    $GLOBALS['_trd_nav'] = ['fleet' => $fleet, 'unread' => $unread, 'active' => $opts['nav'] ?? ''];
}

function layout_app_end(): void
{
    $nav = $GLOBALS['_trd_nav'] ?? ['fleet' => false, 'unread' => 0, 'active' => ''];
    echo '</main>';
    $active = $nav['active'] ?? '';
    $unread = (int)($nav['unread'] ?? 0);
    echo '<nav class="tabbar" aria-label="Primary">';
    $items = [
        ['home', 'home', t('nav.home')],
        ['support', 'life-buoy', t('nav.support')],
        ['emergency', 'siren', t('nav.sos'), true],
        ['inbox', 'inbox', t('nav.inbox')],
        ['me', 'user', t('nav.me')],
    ];
    if (empty($nav['fleet'])) {
        $items = [
            ['home', 'home', t('nav.home')],
            ['inbox', 'inbox', t('nav.inbox')],
            ['me', 'user', t('nav.me')],
        ];
    }
    foreach ($items as $it) {
        $isSos = !empty($it[3]);
        $on = $active === $it[0] ? ' is-on' : '';
        $cls = 'tab' . $on . ($isSos ? ' tab-sos' : '');
        echo '<a class="' . $cls . '" href="' . e(url($it[0])) . '">';
        echo icon($it[1], $isSos ? 22 : 20);
        echo '<span>' . e($it[2]) . '</span>';
        if ($it[0] === 'inbox' && $unread > 0) {
            echo '<i class="badge">' . ($unread > 9 ? '9+' : $unread) . '</i>';
        }
        echo '</a>';
    }
    echo '</nav></div>';
    html_foot();
}

function staff_nav_items(array $user): array
{
    $base = $user['role'] === 'supervisor' ? 'supervisor' : 'admin';
    $items = [
        [$base, 'grid', t('nav.overview')],
        [$base . '/applications', 'clipboard', t('nav.applications')],
        [$base . '/tickets', 'life-buoy', t('admin.tickets')],
        [$base . '/appointments', 'calendar', t('nav.appointments')],
        [$base . '/emergencies', 'siren', t('admin.emergencies')],
    ];
    if ($user['role'] === 'admin') {
        $items[] = ['admin/cities', 'map-pin', t('nav.cities')];
        $items[] = ['admin/team', 'users', t('nav.supervisors')];
        $items[] = ['admin/settings', 'settings', t('nav.settings')];
    }
    return $items;
}

function layout_staff_start(string $title, array $opts = []): void
{
    $user = $opts['user'] ?? require_login();
    $active = $opts['nav'] ?? '';
    html_head($title, ['body' => 'staff-body', 'theme' => '#1C1917']);
    echo '<div class="staff">';
    echo '<aside class="side">';
    echo '<a class="brand brand-side" href="' . e(url($user['role'] === 'admin' ? 'admin' : 'supervisor')) . '">' . trd_mark(34) . '<span>TRD <em>Ops</em></span></a>';
    echo '<p class="side-role">' . e($user['role'] === 'admin' ? t('role.admin') : t('role.supervisor')) . '</p>';
    echo '<nav class="sidenav">';
    foreach (staff_nav_items($user) as $it) {
        $on = $active === $it[0] || $active === $it[2] ? ' is-on' : '';
        echo '<a class="sidenav-a' . $on . '" href="' . e(url($it[0])) . '">' . icon($it[1], 18) . '<span>' . e($it[2]) . '</span></a>';
    }
    echo '</nav>';
    echo '<form method="post" action="' . e(url('logout')) . '" class="side-foot-form">' . csrf_field();
    echo '<button class="sidenav-a side-foot" type="submit">' . icon('log-out', 18) . '<span>' . e(t('nav.signOut')) . '</span></button></form>';
    echo '</aside>';
    echo '<div class="staff-main">';
    echo '<header class="staff-top">';
    echo '<button class="icon-btn menu-toggle" type="button" data-menu aria-label="' . e(t('nav.menu')) . '">' . icon('menu', 20) . '</button>';
    echo '<div class="staff-title"><h1>' . e($title) . '</h1></div>';
    echo lang_switch_html();
    echo '<div class="staff-who"><strong>' . e($user['name']) . '</strong><span>' . e($user['email']) . '</span></div>';
    echo '</header>';
    echo '<div class="staff-body-inner">';
    render_flashes();
}

function layout_staff_end(): void
{
    echo '</div></div></div>';
    html_foot();
}

function empty_state(string $title, string $body, string $iconName = 'inbox'): void
{
    echo '<div class="empty"><div class="empty-ico">' . icon($iconName, 28) . '</div><h3>' . e($title) . '</h3><p>' . e($body) . '</p></div>';
}

function field_start(string $label, string $for = '', string $hint = ''): void
{
    echo '<label class="field">';
    echo '<span class="field-label">' . e($label) . '</span>';
    if ($hint !== '') {
        echo '<span class="field-hint">' . e($hint) . '</span>';
    }
}

function field_end(): void
{
    echo '</label>';
}

function errors_box(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="flash flash-error" role="alert"><ul class="err-list">';
    foreach ($errors as $err) {
        echo '<li>' . e($err) . '</li>';
    }
    echo '</ul></div>';
}

function timeline_html(array $app, array $events = []): void
{
    $current = $app['status'] ?? 'pending';
    $rejected = $current === 'rejected';
    echo '<ol class="rail">';
    foreach (STATUS_FLOW as $i => $id) {
        $done = array_search($current, STATUS_FLOW, true);
        $state = 'wait';
        if ($rejected) {
            $state = 'muted';
        } elseif ($done !== false) {
            if ($i < $done) {
                $state = 'done';
            } elseif ($i === $done) {
                $state = 'now';
            }
        }
        echo '<li class="rail-i is-' . $state . '">';
        echo '<i class="rail-dot">' . ($state === 'done' ? icon('check', 12) : '') . '</i>';
        echo '<div><strong>' . e(status_label($id)) . '</strong><span>' . e(status_hint($id)) . '</span></div>';
        echo '</li>';
    }
    if ($rejected) {
        echo '<li class="rail-i is-now is-rejected"><i class="rail-dot"></i><div><strong>Rejected</strong><span>This application was not approved.</span></div></li>';
    }
    echo '</ol>';
    if ($events) {
        echo '<div class="event-log">';
        foreach ($events as $ev) {
            echo '<p><time>' . e(dt_format((string)$ev['created_at'], 'd M Y H:i')) . '</time> '
                . e(status_label((string)$ev['to_status']))
                . (!empty($ev['note']) ? ' — ' . e((string)$ev['note']) : '')
                . '</p>';
        }
        echo '</div>';
    }
}

function id_card_html(array $user, array $app): void
{
    $name = display_name_of($user, $app);
    $code = (string)$app['rider_code'];
    $type = $app['type'] === 'driver' ? 'Driver' : 'Rider';
    $city = (string)($app['city_name'] ?? $user['city_name'] ?? '');
    echo '<article class="idcard">';
    echo '<div class="idcard-top"><span>TRD · Fleet credential</span><span>' . e($type) . '</span></div>';
    echo '<div class="idcard-body">';
    echo '<div class="idcard-mark">' . trd_mark(48) . '</div>';
    echo '<div class="idcard-who"><h2>' . e($name) . '</h2><p>' . e($city !== '' ? $city . ' · Sultanate of Oman' : 'Sultanate of Oman') . '</p></div>';
    echo '</div>';
    echo '<div class="idcard-code">' . e($code) . '</div>';
    echo '<div class="idcard-foot"><span>Talabat Rider Division</span><span>Valid while active</span></div>';
    echo '</article>';
}
