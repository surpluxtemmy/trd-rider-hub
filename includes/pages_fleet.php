<?php
declare(strict_types=1);

function page_home(): void
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    $app = latest_application((int)$user['id']);
    if (!$app) {
        layout_app_start(t('nav.home'), ['user' => $user, 'nav' => 'home']);
        echo '<p class="kicker">Get started</p>';
        echo '<h1 class="h">Your fleet file is empty</h1>';
        echo '<p class="sub">Choose rider or driver, then upload passport documents.</p>';
        echo '<div class="vehicle-row">';
        echo '<a class="vcard" href="' . e(url('onboarding')) . '"><img src="' . e(asset('rider.jpg')) . '" alt=""><div class="vcard-meta"><strong>Rider</strong><span>Bike</span></div></a>';
        echo '<a class="vcard" href="' . e(url('onboarding')) . '"><img src="' . e(asset('driver.jpg')) . '" alt=""><div class="vcard-meta"><strong>Driver</strong><span>Car</span></div></a>';
        echo '</div>';
        echo '<a class="btn btn-primary" href="' . e(url('onboarding')) . '">Start application</a>';
        layout_app_end();
        return;
    }
    $events = qall('SELECT * FROM application_events WHERE application_id = ? ORDER BY id DESC LIMIT 12', [(int)$app['id']]);
    $fleet = is_fleet_member($user, $app);

    layout_app_start(t('nav.home'), ['user' => $user, 'app' => $app, 'nav' => 'home']);

    if ($fleet) {
        echo '<p class="kicker">Active credential</p>';
        id_card_html($user, $app);
        echo '<div class="quick">';
        $tiles = [
            ['support', 'life-buoy', 'Support', 'Pay, account, vehicle'],
            ['appointments', 'calendar', 'Appointments', 'Documents, training'],
            ['emergency', 'siren', 'Emergency SOS', 'City supervisor + ops'],
            ['inbox', 'inbox', 'Inbox', 'Status and replies'],
        ];
        foreach ($tiles as $t) {
            echo '<a class="qtile" href="' . e(url($t[0])) . '">' . icon($t[1], 20) . '<strong>' . e($t[2]) . '</strong><span>' . e($t[3]) . '</span></a>';
        }
        echo '</div>';
        $nextApt = qone(
            "SELECT * FROM appointments WHERE user_id = ? AND status IN ('requested','confirmed') AND scheduled_for >= NOW() ORDER BY scheduled_for ASC LIMIT 1",
            [(int)$user['id']]
        );
        if ($nextApt) {
            echo '<a class="card rowlink" href="' . e(url('appointments')) . '">';
            echo '<div><p class="kicker">Next appointment</p><strong>' . e(APPOINTMENT_KINDS[$nextApt['kind']] ?? $nextApt['kind']) . '</strong>';
            echo '<p>' . e(dt_format((string)$nextApt['scheduled_for'])) . '</p></div>' . icon('chevron-right', 18);
            echo '</a>';
        }
        layout_app_end();
        return;
    }

    echo '<p class="kicker">' . e(strtoupper($app['type'])) . ' · ' . e($app['application_no']) . '</p>';
    echo '<h1 class="h">Application status</h1>';
    echo '<div class="status-hero">';
    echo status_pill((string)$app['status']);
    echo '<p>' . e(status_hint((string)$app['status'])) . '</p>';
    echo '</div>';
    if (!empty($_SESSION['last_wa_admin'])) {
        echo '<a class="btn btn-ghost" href="' . e((string)$_SESSION['last_wa_admin']) . '" target="_blank" rel="noopener">Message operations on WhatsApp ' . icon('external', 16) . '</a>';
    }
    timeline_html($app, array_reverse($events));
    if ($app['status'] === 'rejected') {
        echo '<a class="btn btn-primary" href="' . e(url('onboarding')) . '">Start a new application</a>';
    }
    echo '<section class="card muted-card"><p class="tiny">Submitted ' . e(dt_format((string)$app['created_at'], 'd M Y')) . ' · ' . e((string)($app['city_name'] ?? '')) . '</p>';
    echo '<p class="tiny">You will get email, WhatsApp and in-app notices at each step.</p></section>';
    layout_app_end();
}

function require_fleet(): array
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    $app = latest_application((int)$user['id']);
    if (!is_fleet_member($user, $app)) {
        flash_set('error', 'Fleet tools unlock after your application is completed.');
        redirect('home');
    }
    return [$user, $app];
}

function page_support(): void
{
    [$user] = require_fleet();
    $errors = [];
    if (is_post() && post_str('intent') === 'create') {
        csrf_verify();
        $cat = post_str('category');
        $subject = post_str('subject');
        $body = post_str('body');
        if (!isset(TICKET_CATEGORIES[$cat])) {
            $errors[] = 'Choose a category.';
        }
        if (strlen($subject) < 3) {
            $errors[] = 'Enter a short subject.';
        }
        if (strlen($body) < 8) {
            $errors[] = 'Describe the issue.';
        }
        if (!$errors) {
            q(
                'INSERT INTO tickets (user_id, category, subject, body, status) VALUES (?, ?, ?, ?, ?)',
                [(int)$user['id'], $cat, $subject, $body, 'open']
            );
            $tid = (int)db()->lastInsertId();
            foreach (admin_users() as $admin) {
                notify_inapp((int)$admin['id'], 'New ticket', $user['name'] . ': ' . $subject, ['ticket_id' => $tid]);
            }
            $cityId = (int)($user['city_id'] ?? 0);
            if ($cityId) {
                foreach (city_supervisors($cityId) as $s) {
                    notify_inapp((int)$s['id'], 'New ticket', $user['name'] . ': ' . $subject, ['ticket_id' => $tid]);
                }
            }
            flash_set('ok', 'Ticket sent to operations.');
            redirect('support/view?id=' . $tid);
        }
    }
    $tickets = qall('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 50', [(int)$user['id']]);
    layout_app_start(t('support.title'), ['user' => $user, 'nav' => 'support']);
    errors_box($errors);
    echo '<h1 class="h">Support & complaints</h1>';
    echo '<p class="sub">' . e(setting('support_hours', 'Sunday–Thursday, 9:00–18:00 GST')) . '</p>';
    echo '<form method="post" class="form card">';
    echo csrf_field();
    echo '<input type="hidden" name="intent" value="create">';
    echo '<label class="field"><span class="field-label">Category</span><select class="input" name="category" required>';
    echo '<option value="">Select</option>';
    foreach (TICKET_CATEGORIES as $id => $label) {
        echo '<option value="' . e($id) . '">' . e($label) . '</option>';
    }
    echo '</select></label>';
    echo '<label class="field"><span class="field-label">Subject</span><input class="input" name="subject" required maxlength="180"></label>';
    echo '<label class="field"><span class="field-label">Details</span><textarea class="input textarea" name="body" required rows="4"></textarea></label>';
    echo '<button class="btn btn-primary" type="submit">Send ticket</button></form>';
    echo '<h2 class="h2">Your tickets</h2>';
    if (!$tickets) {
        empty_state('No tickets yet', 'Pay, account or vehicle issues land here.', 'life-buoy');
    } else {
        echo '<div class="list">';
        foreach ($tickets as $t) {
            echo '<a class="list-row" href="' . e(url('support/view?id=' . (int)$t['id'])) . '">';
            echo '<div><strong>' . e($t['subject']) . '</strong><span>' . e(TICKET_CATEGORIES[$t['category']] ?? $t['category']) . ' · ' . e(dt_format((string)$t['created_at'], 'd M')) . '</span></div>';
            echo '<span class="pill pill-' . e($t['status']) . '">' . e($t['status']) . '</span></a>';
        }
        echo '</div>';
    }
    layout_app_end();
}

function page_support_view(): void
{
    [$user] = require_fleet();
    $id = request_int('id');
    $ticket = qone('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$id, (int)$user['id']]);
    if (!$ticket) {
        flash_set('error', 'Ticket not found.');
        redirect('support');
    }
    if (is_post()) {
        csrf_verify();
        $body = post_str('body');
        if (strlen($body) < 2) {
            flash_set('error', 'Write a reply.');
        } else {
            q('INSERT INTO ticket_replies (ticket_id, user_id, body) VALUES (?, ?, ?)', [$id, (int)$user['id'], $body]);
            q("UPDATE tickets SET status = 'open' WHERE id = ? AND status = 'closed'", [$id]);
            foreach (admin_users() as $admin) {
                notify_inapp((int)$admin['id'], 'Ticket reply', $user['name'] . ' replied on #' . $id, ['ticket_id' => $id]);
            }
            flash_set('ok', 'Reply sent.');
            redirect('support/view?id=' . $id);
        }
    }
    $replies = qall(
        'SELECT r.*, u.name, u.role FROM ticket_replies r JOIN users u ON u.id = r.user_id WHERE r.ticket_id = ? ORDER BY r.id ASC',
        [$id]
    );
    layout_app_start($ticket['subject'], ['user' => $user, 'nav' => 'support', 'back' => 'support', 'back_label' => 'Tickets']);
    echo '<p class="kicker">' . e(TICKET_CATEGORIES[$ticket['category']] ?? '') . ' · ' . status_pill((string)$ticket['status']) . '</p>';
    echo '<h1 class="h">' . e($ticket['subject']) . '</h1>';
    echo '<article class="bubble you"><p>' . nl2br(e($ticket['body'])) . '</p><time>' . e(dt_format((string)$ticket['created_at'])) . '</time></article>';
    foreach ($replies as $r) {
        $cls = (int)$r['user_id'] === (int)$user['id'] ? 'you' : 'them';
        echo '<article class="bubble ' . $cls . '"><p>' . nl2br(e($r['body'])) . '</p>';
        echo '<time>' . e($r['name']) . ' · ' . e(dt_format((string)$r['created_at'])) . '</time></article>';
    }
    echo '<form method="post" class="form composer">' . csrf_field();
    echo '<textarea class="input textarea" name="body" required rows="3" placeholder="Write a reply"></textarea>';
    echo '<button class="btn btn-primary" type="submit">Reply</button></form>';
    layout_app_end();
}

function page_appointments(): void
{
    [$user, $app] = require_fleet();
    $errors = [];
    if (is_post() && post_str('intent') === 'book') {
        csrf_verify();
        $kind = post_str('kind');
        $when = post_str('scheduled_for');
        $notes = post_str('notes');
        if (!isset(APPOINTMENT_KINDS[$kind])) {
            $errors[] = 'Choose appointment type.';
        }
        $ts = $when !== '' ? strtotime($when) : false;
        if ($ts === false || $ts < time() - 60) {
            $errors[] = 'Pick a future date and time.';
        }
        if (!$errors) {
            q(
                'INSERT INTO appointments (user_id, city_id, kind, scheduled_for, notes, status) VALUES (?, ?, ?, ?, ?, ?)',
                [(int)$user['id'], (int)($app['city_id'] ?? $user['city_id']), $kind, date('Y-m-d H:i:s', $ts), $notes, 'requested']
            );
            $aid = (int)db()->lastInsertId();
            $apt = qone('SELECT * FROM appointments WHERE id = ?', [$aid]);
            foreach (admin_users() as $admin) {
                notify_inapp((int)$admin['id'], 'Appointment requested', $user['name'] . ' · ' . (APPOINTMENT_KINDS[$kind] ?? $kind), ['appointment_id' => $aid]);
            }
            notify_inapp((int)$user['id'], 'Appointment requested', APPOINTMENT_KINDS[$kind] . ' · waiting for confirmation.', ['appointment_id' => $aid]);
            flash_set('ok', 'Appointment requested. Operations will confirm.');
            redirect('appointments');
        }
    }
    if (is_post() && post_str('intent') === 'cancel') {
        csrf_verify();
        $id = request_int('id');
        q("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('requested','confirmed')", [$id, (int)$user['id']]);
        flash_set('ok', 'Appointment cancelled.');
        redirect('appointments');
    }
    $rows = qall('SELECT * FROM appointments WHERE user_id = ? ORDER BY scheduled_for DESC LIMIT 40', [(int)$user['id']]);
    layout_app_start(t('appt.title'), ['user' => $user, 'nav' => 'home']);
    errors_box($errors);
    echo '<h1 class="h">Book an appointment</h1>';
    echo '<p class="sub">Document collection, inspection, uniform, training.</p>';
    echo '<form method="post" class="form card">' . csrf_field();
    echo '<input type="hidden" name="intent" value="book">';
    echo '<label class="field"><span class="field-label">Type</span><select class="input" name="kind" required>';
    echo '<option value="">Select</option>';
    foreach (APPOINTMENT_KINDS as $id => $label) {
        echo '<option value="' . e($id) . '">' . e($label) . '</option>';
    }
    echo '</select></label>';
    echo '<label class="field"><span class="field-label">When</span><input class="input" type="datetime-local" name="scheduled_for" required></label>';
    echo '<label class="field"><span class="field-label">Notes</span><textarea class="input textarea" name="notes" rows="3"></textarea></label>';
    echo '<button class="btn btn-primary" type="submit">Request slot</button></form>';
    echo '<h2 class="h2">Your bookings</h2>';
    if (!$rows) {
        empty_state('No appointments', 'Request a slot when operations asks you to come in.', 'calendar');
    } else {
        echo '<div class="list">';
        foreach ($rows as $r) {
            echo '<div class="list-row static">';
            echo '<div><strong>' . e(APPOINTMENT_KINDS[$r['kind']] ?? $r['kind']) . '</strong>';
            echo '<span>' . e(dt_format((string)$r['scheduled_for'])) . ($r['location'] ? ' · ' . e($r['location']) : '') . '</span></div>';
            echo '<div class="row-actions">' . status_pill((string)$r['status']);
            if (in_array($r['status'], ['requested', 'confirmed'], true)) {
                echo '<form method="post">' . csrf_field() . '<input type="hidden" name="intent" value="cancel"><input type="hidden" name="id" value="' . (int)$r['id'] . '">';
                echo '<button class="text-btn" type="submit">Cancel</button></form>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }
    layout_app_end();
}

function page_emergency(): void
{
    [$user] = require_fleet();
    $errors = [];
    if (is_post()) {
        csrf_verify();
        $msg = post_str('message');
        $lat = post_str('lat');
        $lng = post_str('lng');
        if (strlen($msg) < 3) {
            $errors[] = 'Describe what is happening.';
        }
        if ($lat !== '' && !preg_match('/^-?\d{1,3}(\.\d+)?$/', $lat)) {
            $lat = '';
        }
        if ($lng !== '' && !preg_match('/^-?\d{1,3}(\.\d+)?$/', $lng)) {
            $lng = '';
        }
        if (!$errors) {
            q(
                'INSERT INTO emergencies (user_id, message, lat, lng, status) VALUES (?, ?, ?, ?, ?)',
                [(int)$user['id'], $msg, $lat !== '' ? $lat : null, $lng !== '' ? $lng : null, 'open']
            );
            $id = (int)db()->lastInsertId();
            $em = qone('SELECT * FROM emergencies WHERE id = ?', [$id]);
            $fresh = qone('SELECT u.*, c.name AS city_name FROM users u LEFT JOIN cities c ON c.id = u.city_id WHERE u.id = ?', [(int)$user['id']]);
            notify_emergency($em, $fresh ?: $user);
            flash_set('ok', 'SOS sent to your city supervisor and operations.');
            redirect('emergency');
        }
    }
    $recent = qall('SELECT * FROM emergencies WHERE user_id = ? ORDER BY id DESC LIMIT 8', [(int)$user['id']]);
    $adminWa = setting('admin_whatsapp');
    $emPhone = setting('emergency_phone') ?: $adminWa;
    layout_app_start(t('sos.title'), ['user' => $user, 'nav' => 'emergency', 'body' => 'sos-page']);
    errors_box($errors);
    echo '<div class="sos-hero">';
    echo '<div class="sos-ring">' . icon('siren', 36) . '</div>';
    echo '<h1 class="h">Emergency SOS</h1>';
    echo '<p class="sub">Alerts the supervisor for your city and TRD operations. Use only when you need help now.</p>';
    echo '</div>';
    echo '<form method="post" class="form" id="sos-form">' . csrf_field();
    echo '<input type="hidden" name="lat" value="">';
    echo '<input type="hidden" name="lng" value="">';
    echo '<label class="field"><span class="field-label">What is happening?</span>';
    echo '<textarea class="input textarea" name="message" required rows="4" placeholder="Accident, harassment, medical, vehicle breakdown…"></textarea></label>';
    echo '<p class="tiny" id="geo-status">Location is added if you allow it.</p>';
    echo '<button class="btn btn-danger" type="submit">Send SOS</button></form>';
    if ($emPhone) {
        $n = normalize_phone($emPhone);
        echo '<a class="btn btn-ghost" href="tel:+' . e($n) . '">' . icon('phone', 16) . ' Call operations</a>';
        echo '<a class="btn btn-ghost" href="' . e(wa_link($n, 'TRD SOS. I need help. Fleet ID ' . (latest_application((int)$user['id'])['rider_code'] ?? ''))) . '" target="_blank" rel="noopener">' . icon('message', 16) . ' WhatsApp operations</a>';
    }
    if ($recent) {
        echo '<h2 class="h2">Recent SOS</h2><div class="list">';
        foreach ($recent as $r) {
            echo '<div class="list-row static"><div><strong>' . e(clip((string)$r['message'], 80)) . '</strong><span>' . e(dt_format((string)$r['created_at'])) . '</span></div>' . status_pill((string)$r['status']) . '</div>';
        }
        echo '</div>';
    }
    layout_app_end();
}

function page_inbox(): void
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    if (is_post() && post_str('intent') === 'read') {
        csrf_verify();
        q("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND channel = 'inapp' AND read_at IS NULL", [(int)$user['id']]);
        redirect('inbox');
    }
    $rows = qall(
        "SELECT * FROM notifications WHERE user_id = ? AND channel = 'inapp' ORDER BY id DESC LIMIT 80",
        [(int)$user['id']]
    );
    layout_app_start(t('inbox.title'), ['user' => $user, 'nav' => 'inbox']);
    echo '<div class="head-row"><h1 class="h">Inbox</h1>';
    if ($rows) {
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="intent" value="read"><button class="text-btn" type="submit">Mark all read</button></form>';
    }
    echo '</div>';
    if (!$rows) {
        empty_state('No messages', 'Status changes, ticket replies and SOS receipts appear here.', 'inbox');
    } else {
        echo '<div class="list">';
        foreach ($rows as $r) {
            $unread = empty($r['read_at']);
            echo '<article class="list-row static' . ($unread ? ' is-unread' : '') . '"><div>';
            echo '<strong>' . e($r['title']) . '</strong><span>' . e($r['body']) . '</span>';
            echo '<time>' . e(dt_format((string)$r['created_at'])) . '</time></div></article>';
        }
        echo '</div>';
        q("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND channel = 'inapp' AND read_at IS NULL", [(int)$user['id']]);
    }
    layout_app_end();
}

function page_me(): void
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    $app = latest_application((int)$user['id']);
    layout_app_start(t('me.title'), ['user' => $user, 'app' => $app, 'nav' => 'me']);
    echo '<div class="profile-head">';
    echo '<div class="avatar">' . e(initial((string)$user['name'])) . '</div>';
    echo '<div><h1 class="h">' . e($user['name']) . '</h1><p class="sub">' . e($user['email']) . '</p></div></div>';
    echo '<div class="card kv">';
    echo '<div><span>' . e(t('signup.phone')) . '</span><strong>' . e(format_phone((string)$user['phone'])) . '</strong></div>';
    echo '<div><span>' . e(t('admin.city')) . '</span><strong>' . e(city_label($user['city_name'] ?? null)) . '</strong></div>';
    echo '<div><span>' . e(t('role.applicant')) . '</span><strong>' . e(t('role.' . $user['role'], [], $user['role'])) . '</strong></div>';
    if ($app) {
        echo '<div><span>' . e(t('home.application')) . '</span><strong>' . e($app['application_no']) . '</strong></div>';
        if (!empty($app['rider_code'])) {
            echo '<div><span>ID</span><strong>' . e($app['rider_code']) . '</strong></div>';
        }
    }
    echo '</div>';
    echo '<div class="list-row"><div><strong>' . e(t('me.language')) . '</strong><span>' . e(t('lang.switch')) . '</span></div>' . lang_switch_html(false) . '</div>';
    echo '<a class="list-row" href="' . e(url('install-app')) . '"><div><strong>' . e(t('me.install')) . '</strong><span>' . e(t('install.sub')) . '</span></div>' . icon('chevron-right', 18) . '</a>';
    $wa = setting('admin_whatsapp');
    if ($wa) {
        echo '<a class="list-row" href="' . e(wa_link($wa, 'Hello TRD, I need help with my account ' . $user['email'])) . '" target="_blank" rel="noopener"><div><strong>' . e(t('sos.wa')) . '</strong><span>' . e(format_phone($wa)) . '</span></div>' . icon('external', 18) . '</a>';
    }
    echo '<form method="post" action="' . e(url('logout')) . '">' . csrf_field();
    echo '<button class="btn btn-ghost" type="submit">' . icon('log-out', 18) . ' ' . e(t('nav.signOut')) . '</button></form>';
    layout_app_end();
}
