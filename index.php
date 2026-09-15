<?php
declare(strict_types=1);

define('TRD_APP', true);

$root = __DIR__;
if (!is_file($root . '/config.php') || !is_file($root . '/data/install.lock')) {
    header('Location: install.php');
    exit;
}

require $root . '/config.php';
require $root . '/includes/helpers.php';
require $root . '/includes/i18n.php';
require $root . '/includes/db.php';
require $root . '/includes/auth.php';
require $root . '/includes/notify.php';
require $root . '/includes/layout.php';
require $root . '/includes/pages_public.php';
require $root . '/includes/pages_fleet.php';
require $root . '/includes/pages_staff.php';

start_app_session();
lang_boot();

$route = current_route();

try {
    match ($route) {
        '', 'welcome' => page_landing(),
        'login' => page_login(),
        'signup' => page_signup(),
        'logout' => page_logout(),
        'install-app' => page_install_app(),
        'onboarding' => page_onboarding(),
        'register' => page_register(),
        'home' => page_home(),
        'support' => page_support(),
        'support/view' => page_support_view(),
        'appointments' => page_appointments(),
        'emergency' => page_emergency(),
        'inbox' => page_inbox(),
        'me' => page_me(),
        'file' => page_file(),
        'admin' => page_staff_home(),
        'admin/applications' => page_staff_applications(),
        'admin/application' => page_staff_application(),
        'admin/tickets' => page_staff_tickets(),
        'admin/appointments' => page_staff_appointments(),
        'admin/emergencies' => page_staff_emergencies(),
        'admin/cities' => page_admin_cities(),
        'admin/team' => page_admin_team(),
        'admin/settings' => page_admin_settings(),
        'supervisor' => page_staff_home(),
        'supervisor/applications' => page_staff_applications(),
        'supervisor/application' => page_staff_application(),
        'supervisor/tickets' => page_staff_tickets(),
        'supervisor/appointments' => page_staff_appointments(),
        'supervisor/emergencies' => page_staff_emergencies(),
        default => page_404(),
    };
} catch (Throwable $e) {
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#FBF7F2;color:#1C1917;margin:0;padding:2rem}main{max-width:28rem;margin:10vh auto;background:#fff;padding:1.5rem;border-radius:1rem;box-shadow:0 10px 30px rgba(28,25,23,.08)}h1{font-size:1.25rem}p{color:#57534e}</style></head><body><main>';
    echo '<h1>Something went wrong</h1><p>TRD Hub could not complete that request. Try again, or contact operations if it continues.</p>';
    if (defined('TRD_DEBUG') && TRD_DEBUG) {
        echo '<pre style="white-space:pre-wrap;font-size:.8rem">' . e($e->getMessage()) . '</pre>';
    }
    echo '</main></body></html>';
}
