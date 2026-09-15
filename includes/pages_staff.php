<?php
declare(strict_types=1);

function staff_user(): array
{
    $u = require_role(['admin', 'supervisor']);
    $route = current_route();
    $shared = ['', '/applications', '/application', '/tickets', '/appointments', '/emergencies'];
    $qs = $_GET;
    unset($qs['r']);
    $suffix = $qs ? ('?' . http_build_query($qs)) : '';
    if ($u['role'] === 'supervisor' && ($route === 'admin' || str_starts_with($route, 'admin/'))) {
        $rest = $route === 'admin' ? '' : substr($route, 5);
        if (in_array($rest, $shared, true)) {
            redirect('supervisor' . $rest . $suffix);
        }
    }
    if ($u['role'] === 'admin' && ($route === 'supervisor' || str_starts_with($route, 'supervisor/'))) {
        $rest = $route === 'supervisor' ? '' : substr($route, 10);
        if (in_array($rest, $shared, true)) {
            redirect('admin' . $rest . $suffix);
        }
    }
    return $u;
}

function staff_base(array $user): string
{
    return $user['role'] === 'supervisor' ? 'supervisor' : 'admin';
}

function staff_city_clause(array $user, string $alias = 'a'): array
{
    if ($user['role'] !== 'supervisor') {
        return ['', []];
    }
    $cid = supervisor_city_id($user);
    return [' AND ' . $alias . '.city_id = ? ', [$cid]];
}

function page_staff_home(): void
{
    $user = staff_user();
    $base = staff_base($user);
    $city = $user['role'] === 'supervisor' ? supervisor_city_id($user) : null;

    $qCount = function (string $sql, array $a = []): int {
        $r = qone($sql, $a);
        return (int)($r['n'] ?? 0);
    };

    if ($city) {
        $cPending = $qCount("SELECT COUNT(*) n FROM applications WHERE city_id = ? AND status = 'pending'", [$city]);
        $cOpen = $qCount("SELECT COUNT(*) n FROM applications WHERE city_id = ? AND status NOT IN ('completed','rejected')", [$city]);
        $cDone = $qCount("SELECT COUNT(*) n FROM applications WHERE city_id = ? AND status = 'completed'", [$city]);
        $cEm = $qCount("SELECT COUNT(*) n FROM emergencies e JOIN users u ON u.id = e.user_id WHERE u.city_id = ? AND e.status = 'open'", [$city]);
        $cTk = $qCount("SELECT COUNT(*) n FROM tickets t JOIN users u ON u.id = t.user_id WHERE u.city_id = ? AND t.status IN ('open','pending')", [$city]);
        $cAp = $qCount("SELECT COUNT(*) n FROM appointments a WHERE a.city_id = ? AND a.status = 'requested'", [$city]);
        $recent = qall(
            'SELECT a.*, c.name AS city_name FROM applications a LEFT JOIN cities c ON c.id = a.city_id WHERE a.city_id = ? ORDER BY a.id DESC LIMIT 8',
            [$city]
        );
        $cityRow = qone('SELECT * FROM cities WHERE id = ?', [$city]);
        $subtitle = $cityRow['name'] ?? 'Your city';
    } else {
        $cPending = $qCount("SELECT COUNT(*) n FROM applications WHERE status = 'pending'");
        $cOpen = $qCount("SELECT COUNT(*) n FROM applications WHERE status NOT IN ('completed','rejected')");
        $cDone = $qCount("SELECT COUNT(*) n FROM applications WHERE status = 'completed'");
        $cEm = $qCount("SELECT COUNT(*) n FROM emergencies WHERE status = 'open'");
        $cTk = $qCount("SELECT COUNT(*) n FROM tickets WHERE status IN ('open','pending')");
        $cAp = $qCount("SELECT COUNT(*) n FROM appointments WHERE status = 'requested'");
        $recent = qall('SELECT a.*, c.name AS city_name FROM applications a LEFT JOIN cities c ON c.id = a.city_id ORDER BY a.id DESC LIMIT 8');
        $subtitle = 'Oman operations';
    }

    layout_staff_start(t('admin.overview'), ['user' => $user, 'nav' => $base]);
    echo '<p class="kicker">' . e($subtitle) . '</p>';
    echo '<div class="stats">';
    $stats = [
        [$cPending, 'Pending files', $base . '/applications?status=pending', 'orange'],
        [$cOpen, 'In pipeline', $base . '/applications', 'ink'],
        [$cDone, 'Fleet IDs issued', $base . '/applications?status=completed', 'teal'],
        [$cEm, 'Open SOS', $base . '/emergencies', 'danger'],
        [$cTk, 'Open tickets', $base . '/tickets', 'ink'],
        [$cAp, 'Requested slots', $base . '/appointments', 'ink'],
    ];
    foreach ($stats as $s) {
        echo '<a class="stat stat-' . e($s[3]) . '" href="' . e(url($s[2])) . '"><b>' . (int)$s[0] . '</b><span>' . e($s[1]) . '</span></a>';
    }
    echo '</div>';
    echo '<h2 class="h2">Latest applications</h2>';
    render_app_table($recent, $base);
    layout_staff_end();
}

function render_app_table(array $rows, string $base): void
{
    if (!$rows) {
        empty_state('No applications', 'New rider and driver files will land here.', 'clipboard');
        return;
    }
    echo '<div class="table-wrap"><table class="table"><thead><tr>';
    echo '<th>File</th><th>Name</th><th>Type</th><th>City</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        echo '<tr>';
        echo '<td><a href="' . e(url($base . '/application?id=' . (int)$r['id'])) . '">' . e($r['application_no']) . '</a>';
        if (!empty($r['rider_code'])) {
            echo '<div class="tiny">' . e($r['rider_code']) . '</div>';
        }
        echo '</td>';
        echo '<td>' . e($name) . '</td>';
        echo '<td>' . e($r['type']) . '</td>';
        echo '<td>' . e((string)($r['city_name'] ?? '')) . '</td>';
        echo '<td>' . status_pill((string)$r['status']) . '</td>';
        echo '<td><a class="text-btn" href="' . e(url($base . '/application?id=' . (int)$r['id'])) . '">Open</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function page_staff_applications(): void
{
    $user = staff_user();
    $base = staff_base($user);
    $status = query_str('status');
    $qstr = query_str('q');
    $type = query_str('type');
    $sql = 'SELECT a.*, c.name AS city_name FROM applications a LEFT JOIN cities c ON c.id = a.city_id WHERE 1=1';
    $args = [];
    if ($user['role'] === 'supervisor') {
        $sql .= ' AND a.city_id = ?';
        $args[] = supervisor_city_id($user);
    }
    if ($status !== '' && isset(STATUSES[$status])) {
        $sql .= ' AND a.status = ?';
        $args[] = $status;
    }
    if (in_array($type, ['rider', 'driver'], true)) {
        $sql .= ' AND a.type = ?';
        $args[] = $type;
    }
    if ($qstr !== '') {
        $sql .= ' AND (a.application_no LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR a.phone LIKE ? OR a.rider_code LIKE ?)';
        $like = '%' . $qstr . '%';
        array_push($args, $like, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY a.id DESC LIMIT 200';
    $rows = qall($sql, $args);

    layout_staff_start(t('nav.applications'), ['user' => $user, 'nav' => $base . '/applications']);
    echo '<form class="filters" method="get" action="' . e(url($base . '/applications')) . '">';
    echo '<input class="input" type="search" name="q" value="' . e($qstr) . '" placeholder="Name, file no, phone, ID">';
    echo '<select class="input" name="status"><option value="">All statuses</option>';
    foreach (STATUSES as $id => $meta) {
        echo '<option value="' . e($id) . '"' . ($status === $id ? ' selected' : '') . '>' . e($meta['label']) . '</option>';
    }
    echo '</select><select class="input" name="type"><option value="">All types</option>';
    echo '<option value="rider"' . ($type === 'rider' ? ' selected' : '') . '>Rider</option>';
    echo '<option value="driver"' . ($type === 'driver' ? ' selected' : '') . '>Driver</option></select>';
    echo '<button class="btn btn-primary" type="submit">Filter</button></form>';
    render_app_table($rows, $base);
    layout_staff_end();
}

function page_staff_application(): void
{
    $user = staff_user();
    $base = staff_base($user);
    $id = request_int('id');
    $app = qone(
        'SELECT a.*, c.name AS city_name, u.email, u.name AS user_name, u.phone AS user_phone
         FROM applications a
         JOIN users u ON u.id = a.user_id
         LEFT JOIN cities c ON c.id = a.city_id
         WHERE a.id = ?',
        [$id]
    );
    if (!$app) {
        flash_set('error', 'Application not found.');
        redirect($base . '/applications');
    }
    if ($user['role'] === 'supervisor' && (int)$app['city_id'] !== (int)supervisor_city_id($user)) {
        flash_set('error', 'That file is outside your city.');
        redirect($base . '/applications');
    }

    if (is_post()) {
        csrf_verify();
        $intent = post_str('intent');
        $note = post_str('note');
        $from = (string)$app['status'];
        $to = $from;
        if ($intent === 'advance') {
            $n = next_status($from);
            if (!$n) {
                flash_set('error', 'No further status.');
                redirect($base . '/application?id=' . $id);
            }
            $to = $n;
        } elseif ($intent === 'reject') {
            if ($from === 'completed') {
                flash_set('error', 'Completed files cannot be rejected.');
                redirect($base . '/application?id=' . $id);
            }
            $to = 'rejected';
        } elseif ($intent === 'set') {
            $want = post_str('status');
            if (!isset(STATUSES[$want])) {
                flash_set('error', 'Unknown status.');
                redirect($base . '/application?id=' . $id);
            }
            $to = $want;
        }
        if ($to !== $from) {
            $code = $app['rider_code'];
            if ($to === 'completed' && empty($code)) {
                $code = make_fleet_id((string)$app['type']);
            }
            q(
                'UPDATE applications SET status = ?, rider_code = ? WHERE id = ?',
                [$to, $code, $id]
            );
            q(
                'INSERT INTO application_events (application_id, from_status, to_status, note, actor_id) VALUES (?, ?, ?, ?, ?)',
                [$id, $from, $to, $note !== '' ? $note : null, (int)$user['id']]
            );
            if ($to === 'completed') {
                $role = $app['type'] === 'driver' ? 'driver' : 'rider';
                q('UPDATE users SET role = ?, city_id = ? WHERE id = ? AND role IN (\'applicant\',\'rider\',\'driver\')', [
                    $role,
                    $app['city_id'],
                    $app['user_id'],
                ]);
            }
            $app = qone(
                'SELECT a.*, c.name AS city_name, u.email, u.name AS user_name, u.phone AS user_phone
                 FROM applications a JOIN users u ON u.id = a.user_id LEFT JOIN cities c ON c.id = a.city_id WHERE a.id = ?',
                [$id]
            );
            $applicant = qone('SELECT * FROM users WHERE id = ?', [(int)$app['user_id']]);
            notify_status_change($app, $applicant, $from, $to, $note);
            flash_set('ok', 'Status updated to ' . status_label($to) . '.' . ($code && $to === 'completed' ? ' ID ' . $code : ''));
        }
        redirect($base . '/application?id=' . $id);
    }

    $docs = qall('SELECT * FROM documents WHERE application_id = ? ORDER BY id', [$id]);
    $events = qall('SELECT e.*, u.name AS actor FROM application_events e LEFT JOIN users u ON u.id = e.actor_id WHERE e.application_id = ? ORDER BY e.id DESC', [$id]);
    $wa = wa_link((string)$app['phone'], 'TRD: regarding application ' . $app['application_no']);

    layout_staff_start($app['application_no'], ['user' => $user, 'nav' => $base . '/applications']);
    echo '<p>' . back_link($base . '/applications', 'All applications') . '</p>';
    echo '<div class="split">';
    echo '<div>';
    echo '<p class="kicker">' . e(ucfirst((string)$app['type'])) . ' · ' . e((string)$app['city_name']) . '</p>';
    echo '<h2 class="display-sm">' . e(display_name_of(['name' => (string)$app['user_name']], $app)) . '</h2>';
    echo status_pill((string)$app['status']);
    if (!empty($app['rider_code'])) {
        echo '<p class="fleet-id">' . e($app['rider_code']) . '</p>';
    }
    echo '<div class="card kv">';
    echo '<div><span>File no</span><strong>' . e($app['application_no']) . '</strong></div>';
    echo '<div><span>Nationality</span><strong>' . e((string)$app['nationality']) . '</strong></div>';
    echo '<div><span>Date of birth</span><strong>' . e((string)$app['dob']) . '</strong></div>';
    echo '<div><span>Phone</span><strong>' . e(format_phone((string)$app['phone'])) . '</strong></div>';
    echo '<div><span>Email</span><strong>' . e((string)$app['email']) . '</strong></div>';
    echo '<div><span>Submitted</span><strong>' . e(dt_format((string)$app['created_at'])) . '</strong></div>';
    echo '</div>';
    echo '<div class="card"><h3 class="h2">Reason to join</h3><p>' . nl2br(e((string)$app['reason_to_join'])) . '</p></div>';
    echo '<div class="row-actions wrap">';
    echo '<a class="btn btn-ghost" href="' . e($wa) . '" target="_blank" rel="noopener">WhatsApp applicant</a>';
    echo '<a class="btn btn-ghost" href="mailto:' . e((string)$app['email']) . '">Email applicant</a>';
    echo '</div>';
    echo '<h3 class="h2">Documents</h3><div class="docs-grid">';
    $labels = docs_for((string)$app['type']);
    foreach ($docs as $d) {
        $label = $labels[$d['kind']]['label'] ?? $d['kind'];
        echo '<a class="doc-tile" href="' . e(url('file?id=' . (int)$d['id'])) . '" target="_blank" rel="noopener">';
        if (str_starts_with((string)$d['mime'], 'image/')) {
            echo '<img src="' . e(url('file?id=' . (int)$d['id'])) . '" alt="">';
        } else {
            echo '<div class="doc-file">' . icon('file', 28) . '</div>';
        }
        echo '<span>' . e($label) . '</span></a>';
    }
    echo '</div></div><div>';
    echo '<div class="card sticky-card">';
    echo '<h3 class="h2">Update status</h3>';
    $next = next_status((string)$app['status']);
    echo '<form method="post" class="form">' . csrf_field();
    echo '<label class="field"><span class="field-label">Note to applicant (optional)</span><textarea class="input textarea" name="note" rows="3"></textarea></label>';
    if ($next && $app['status'] !== 'rejected') {
        echo '<button class="btn btn-primary" name="intent" value="advance" type="submit">Mark as ' . e(status_label($next)) . '</button>';
    }
    if ($app['status'] !== 'completed' && $app['status'] !== 'rejected') {
        echo '<button class="btn btn-danger" name="intent" value="reject" type="submit">Reject application</button>';
    }
    echo '</form>';
    echo '<form method="post" class="form tight">' . csrf_field() . '<input type="hidden" name="intent" value="set">';
    echo '<label class="field"><span class="field-label">Jump to status</span><select class="input" name="status">';
    foreach (STATUSES as $sid => $meta) {
        echo '<option value="' . e($sid) . '"' . ($app['status'] === $sid ? ' selected' : '') . '>' . e($meta['label']) . '</option>';
    }
    echo '</select></label><button class="btn btn-ghost" type="submit">Save status</button></form>';
    echo '<p class="tiny">Each change emails and WhatsApps the applicant and writes an in-app notice.</p>';
    echo '</div>';
    echo '<h3 class="h2">Timeline</h3>';
    timeline_html($app, array_reverse($events));
    echo '</div></div>';
    layout_staff_end();
}

function page_staff_tickets(): void
{
    $user = staff_user();
    $base = staff_base($user);
    $sql = 'SELECT t.*, u.name, u.email, c.name AS city_name
            FROM tickets t
            JOIN users u ON u.id = t.user_id
            LEFT JOIN cities c ON c.id = u.city_id
            WHERE 1=1';
    $args = [];
    if ($user['role'] === 'supervisor') {
        $sql .= ' AND u.city_id = ?';
        $args[] = supervisor_city_id($user);
    }
    $st = query_str('status');
    if ($st !== '') {
        $sql .= ' AND t.status = ?';
        $args[] = $st;
    }
    $sql .= ' ORDER BY t.id DESC LIMIT 200';
    $rows = qall($sql, $args);

    $viewId = request_int('id');
    if ($viewId) {
        page_staff_ticket_view($user, $base, $viewId);
        return;
    }

    layout_staff_start(t('admin.tickets'), ['user' => $user, 'nav' => $base . '/tickets']);
    echo '<form class="filters" method="get"><select class="input" name="status" onchange="this.form.submit()">';
    echo '<option value="">All</option>';
    foreach (['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $k => $v) {
        echo '<option value="' . e($k) . '"' . ($st === $k ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    echo '</select></form>';
    if (!$rows) {
        empty_state('No tickets', 'Rider complaints will appear here.', 'life-buoy');
    } else {
        echo '<div class="table-wrap"><table class="table"><thead><tr><th>Subject</th><th>Rider</th><th>City</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . e($r['subject']) . '<div class="tiny">' . e(TICKET_CATEGORIES[$r['category']] ?? '') . '</div></td>';
            echo '<td>' . e($r['name']) . '</td><td>' . e((string)$r['city_name']) . '</td><td>' . status_pill((string)$r['status']) . '</td>';
            echo '<td><a class="text-btn" href="' . e(url($base . '/tickets?id=' . (int)$r['id'])) . '">Open</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
    layout_staff_end();
}

function page_staff_ticket_view(array $user, string $base, int $id): void
{
    $ticket = qone(
        'SELECT t.*, u.name, u.email, u.phone, u.city_id, c.name AS city_name
         FROM tickets t JOIN users u ON u.id = t.user_id LEFT JOIN cities c ON c.id = u.city_id WHERE t.id = ?',
        [$id]
    );
    if (!$ticket) {
        flash_set('error', 'Ticket not found.');
        redirect($base . '/tickets');
    }
    if ($user['role'] === 'supervisor' && (int)$ticket['city_id'] !== (int)supervisor_city_id($user)) {
        redirect($base . '/tickets');
    }
    if (is_post()) {
        csrf_verify();
        $intent = post_str('intent');
        if ($intent === 'reply') {
            $body = post_str('body');
            if (strlen($body) < 2) {
                flash_set('error', 'Write a reply.');
            } else {
                q('INSERT INTO ticket_replies (ticket_id, user_id, body) VALUES (?, ?, ?)', [$id, (int)$user['id'], $body]);
                q("UPDATE tickets SET status = 'pending' WHERE id = ?", [$id]);
                notify_ticket_reply($ticket, $user, $body);
                flash_set('ok', 'Reply sent to the rider.');
            }
        } elseif ($intent === 'close') {
            q("UPDATE tickets SET status = 'closed' WHERE id = ?", [$id]);
            flash_set('ok', 'Ticket closed.');
        } elseif ($intent === 'open') {
            q("UPDATE tickets SET status = 'open' WHERE id = ?", [$id]);
        }
        redirect($base . '/tickets?id=' . $id);
    }
    $replies = qall(
        'SELECT r.*, u.name, u.role FROM ticket_replies r JOIN users u ON u.id = r.user_id WHERE r.ticket_id = ? ORDER BY r.id',
        [$id]
    );
    layout_staff_start($ticket['subject'], ['user' => $user, 'nav' => $base . '/tickets']);
    echo '<p>' . back_link($base . '/tickets', 'All tickets') . '</p>';
    echo '<p class="kicker">' . e($ticket['name']) . ' · ' . e((string)$ticket['city_name']) . ' · ' . status_pill((string)$ticket['status']) . '</p>';
    echo '<article class="bubble them"><p>' . nl2br(e($ticket['body'])) . '</p><time>' . e(dt_format((string)$ticket['created_at'])) . '</time></article>';
    foreach ($replies as $r) {
        $cls = $r['role'] === 'applicant' || $r['role'] === 'rider' || $r['role'] === 'driver' ? 'them' : 'you';
        echo '<article class="bubble ' . $cls . '"><p>' . nl2br(e($r['body'])) . '</p><time>' . e($r['name']) . ' · ' . e(dt_format((string)$r['created_at'])) . '</time></article>';
    }
    echo '<form method="post" class="form">' . csrf_field() . '<input type="hidden" name="intent" value="reply">';
    echo '<textarea class="input textarea" name="body" required rows="4"></textarea>';
    echo '<button class="btn btn-primary" type="submit">Reply</button></form>';
    echo '<form method="post">' . csrf_field();
    if ($ticket['status'] !== 'closed') {
        echo '<button class="btn btn-ghost" name="intent" value="close">Close ticket</button>';
    } else {
        echo '<button class="btn btn-ghost" name="intent" value="open">Reopen</button>';
    }
    echo '</form>';
    layout_staff_end();
}

function page_staff_appointments(): void
{
    $user = staff_user();
    $base = staff_base($user);
    if (is_post()) {
        csrf_verify();
        $id = request_int('id');
        $intent = post_str('intent');
        $apt = qone('SELECT a.*, u.email, u.phone, u.name FROM appointments a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [$id]);
        if ($apt) {
            if ($user['role'] === 'supervisor' && (int)$apt['city_id'] !== (int)supervisor_city_id($user)) {
                redirect($base . '/appointments');
            }
            if ($intent === 'confirm') {
                $loc = post_str('location');
                q('UPDATE appointments SET status = ?, location = ? WHERE id = ?', ['confirmed', $loc !== '' ? $loc : $apt['location'], $id]);
                $apt['status'] = 'confirmed';
                $apt['location'] = $loc !== '' ? $loc : $apt['location'];
                notify_appointment($apt, $apt, 'confirmed');
                flash_set('ok', 'Appointment confirmed.');
            } elseif ($intent === 'complete') {
                q("UPDATE appointments SET status = 'completed' WHERE id = ?", [$id]);
                flash_set('ok', 'Marked completed.');
            } elseif ($intent === 'cancel') {
                q("UPDATE appointments SET status = 'cancelled' WHERE id = ?", [$id]);
                $apt['status'] = 'cancelled';
                notify_appointment($apt, $apt, 'cancelled');
                flash_set('ok', 'Cancelled.');
            }
        }
        redirect($base . '/appointments');
    }
    $sql = 'SELECT a.*, u.name, u.phone, c.name AS city_name FROM appointments a JOIN users u ON u.id = a.user_id LEFT JOIN cities c ON c.id = a.city_id WHERE 1=1';
    $args = [];
    if ($user['role'] === 'supervisor') {
        $sql .= ' AND a.city_id = ?';
        $args[] = supervisor_city_id($user);
    }
    $sql .= ' ORDER BY a.scheduled_for DESC LIMIT 200';
    $rows = qall($sql, $args);
    layout_staff_start(t('appt.title'), ['user' => $user, 'nav' => $base . '/appointments']);
    if (!$rows) {
        empty_state('No appointments', 'Riders book slots after they receive a fleet ID.', 'calendar');
    } else {
        echo '<div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>Rider</th><th>Type</th><th>City</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . e(dt_format((string)$r['scheduled_for'])) . '</td>';
            echo '<td>' . e($r['name']) . '</td>';
            echo '<td>' . e(APPOINTMENT_KINDS[$r['kind']] ?? $r['kind']) . '</td>';
            echo '<td>' . e((string)$r['city_name']) . '</td>';
            echo '<td>' . status_pill((string)$r['status']) . '</td><td>';
            if (in_array($r['status'], ['requested', 'confirmed'], true)) {
                echo '<form method="post" class="inline-form">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$r['id'] . '">';
                if ($r['status'] === 'requested') {
                    echo '<input class="input input-sm" name="location" placeholder="Location">';
                    echo '<button class="text-btn" name="intent" value="confirm">Confirm</button>';
                } else {
                    echo '<button class="text-btn" name="intent" value="complete">Complete</button>';
                }
                echo '<button class="text-btn" name="intent" value="cancel">Cancel</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    layout_staff_end();
}

function page_staff_emergencies(): void
{
    $user = staff_user();
    $base = staff_base($user);
    if (is_post()) {
        csrf_verify();
        $id = request_int('id');
        $em = qone('SELECT e.*, u.city_id FROM emergencies e JOIN users u ON u.id = e.user_id WHERE e.id = ?', [$id]);
        if ($em) {
            if ($user['role'] === 'supervisor' && (int)$em['city_id'] !== (int)supervisor_city_id($user)) {
                redirect($base . '/emergencies');
            }
            q("UPDATE emergencies SET status = 'resolved' WHERE id = ?", [$id]);
            flash_set('ok', 'SOS marked resolved.');
        }
        redirect($base . '/emergencies');
    }
    $sql = 'SELECT e.*, u.name, u.phone, c.name AS city_name
            FROM emergencies e
            JOIN users u ON u.id = e.user_id
            LEFT JOIN cities c ON c.id = u.city_id
            WHERE 1=1';
    $args = [];
    if ($user['role'] === 'supervisor') {
        $sql .= ' AND u.city_id = ?';
        $args[] = supervisor_city_id($user);
    }
    $sql .= ' ORDER BY e.id DESC LIMIT 200';
    $rows = qall($sql, $args);
    layout_staff_start(t('admin.emergencies'), ['user' => $user, 'nav' => $base . '/emergencies']);
    if (!$rows) {
        empty_state('No SOS alerts', 'Rider emergency pings from the SOS tab appear here.', 'siren');
    } else {
        echo '<div class="list staff-list">';
        foreach ($rows as $r) {
            echo '<article class="card sos-card' . ($r['status'] === 'open' ? ' is-open' : '') . '">';
            echo '<div class="head-row"><strong>' . e($r['name']) . '</strong>' . status_pill((string)$r['status']) . '</div>';
            echo '<p>' . nl2br(e($r['message'])) . '</p>';
            echo '<p class="tiny">' . e($r['city_name'] ?? '') . ' · ' . e(format_phone((string)$r['phone'])) . ' · ' . e(dt_format((string)$r['created_at']));
            if (!empty($r['lat']) && !empty($r['lng'])) {
                echo ' · <a href="https://maps.google.com/?q=' . e($r['lat'] . ',' . $r['lng']) . '" target="_blank" rel="noopener">Map</a>';
            }
            echo '</p>';
            echo '<div class="row-actions">';
            echo '<a class="btn btn-ghost" href="' . e(wa_link((string)$r['phone'], 'TRD operations here. We received your SOS.')) . '" target="_blank" rel="noopener">WhatsApp rider</a>';
            echo '<a class="btn btn-ghost" href="tel:+' . e(normalize_phone((string)$r['phone'])) . '">Call</a>';
            if ($r['status'] === 'open') {
                echo '<form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$r['id'] . '"><button class="btn btn-primary" type="submit">Mark resolved</button></form>';
            }
            echo '</div></article>';
        }
        echo '</div>';
    }
    layout_staff_end();
}

function page_admin_cities(): void
{
    $user = require_role(['admin']);
    if (is_post()) {
        csrf_verify();
        $intent = post_str('intent');
        if ($intent === 'add') {
            $name = post_str('name');
            $gov = post_str('governorate');
            if ($name === '') {
                flash_set('error', 'City name is required.');
            } else {
                try {
                    q('INSERT INTO cities (name, governorate, active) VALUES (?, ?, 1)', [$name, $gov]);
                    flash_set('ok', 'City added.');
                } catch (Throwable $e) {
                    flash_set('error', 'That city already exists.');
                }
            }
        } elseif ($intent === 'toggle') {
            $id = request_int('id');
            q('UPDATE cities SET active = 1 - active WHERE id = ?', [$id]);
        } elseif ($intent === 'save') {
            $id = request_int('id');
            q('UPDATE cities SET name = ?, governorate = ? WHERE id = ?', [post_str('name'), post_str('governorate'), $id]);
            flash_set('ok', 'City updated.');
        }
        redirect('admin/cities');
    }
    $rows = qall('SELECT * FROM cities ORDER BY governorate, name');
    layout_staff_start(t('admin.citiesTitle'), ['user' => $user, 'nav' => 'admin/cities']);
    echo '<form method="post" class="filters">' . csrf_field() . '<input type="hidden" name="intent" value="add">';
    echo '<input class="input" name="name" required placeholder="City name">';
    echo '<input class="input" name="governorate" placeholder="Governorate">';
    echo '<button class="btn btn-primary" type="submit">Add city</button></form>';
    echo '<div class="table-wrap"><table class="table"><thead><tr><th>City</th><th>Governorate</th><th>Active</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr><td><form method="post" class="inline-form">' . csrf_field() . '<input type="hidden" name="intent" value="save"><input type="hidden" name="id" value="' . (int)$r['id'] . '">';
        echo '<input class="input input-sm" name="name" value="' . e($r['name']) . '"></td>';
        echo '<td><input class="input input-sm" name="governorate" value="' . e($r['governorate']) . '"></td>';
        echo '<td>' . ((int)$r['active'] ? 'Yes' : 'No') . '</td><td>';
        echo '<button class="text-btn" type="submit">Save</button></form>';
        echo '<form method="post" class="inline-form">' . csrf_field() . '<input type="hidden" name="intent" value="toggle"><input type="hidden" name="id" value="' . (int)$r['id'] . '">';
        echo '<button class="text-btn" type="submit">' . ((int)$r['active'] ? 'Disable' : 'Enable') . '</button></form>';
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
    layout_staff_end();
}

function page_admin_team(): void
{
    $user = require_role(['admin']);
    $cities = qall('SELECT id, name FROM cities WHERE active = 1 ORDER BY name');
    $errors = [];
    if (is_post()) {
        csrf_verify();
        $intent = post_str('intent');
        if ($intent === 'create') {
            $name = post_str('name');
            $email = strtolower(post_str('email'));
            $phone = normalize_phone(post_str('phone'));
            $password = (string)($_POST['password'] ?? '');
            $cityId = request_int('city_id');
            if (strlen($name) < 2) {
                $errors[] = 'Enter supervisor name.';
            }
            if (!valid_email($email)) {
                $errors[] = 'Valid email required.';
            }
            if (!valid_phone($phone)) {
                $errors[] = 'Valid phone required.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            $city = qone('SELECT id FROM cities WHERE id = ?', [$cityId]);
            if (!$city) {
                $errors[] = 'Select a city.';
            }
            if (!$errors && qone('SELECT id FROM users WHERE email = ?', [$email])) {
                $errors[] = 'Email already in use.';
            }
            if (!$errors) {
                q(
                    'INSERT INTO users (role, name, email, phone, password_hash, city_id) VALUES (?, ?, ?, ?, ?, ?)',
                    ['supervisor', $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $cityId]
                );
                $uid = (int)db()->lastInsertId();
                q('INSERT INTO supervisors (user_id, city_id, name, phone) VALUES (?, ?, ?, ?)', [$uid, $cityId, $name, $phone]);
                notify_inapp($uid, 'Supervisor access', 'Your TRD Hub supervisor account is ready for your city.', []);
                send_email($email, 'TRD Hub supervisor account', "You have been added as a city supervisor on TRD Rider Hub.\nSign in: use this email and the password provided by operations.", $uid);
                flash_set('ok', 'Supervisor created.');
                redirect('admin/team');
            }
        } elseif ($intent === 'remove') {
            $uid = request_int('user_id');
            q('DELETE FROM supervisors WHERE user_id = ?', [$uid]);
            q("UPDATE users SET role = 'applicant' WHERE id = ? AND role = 'supervisor'", [$uid]);
            flash_set('ok', 'Supervisor removed.');
            redirect('admin/team');
        }
    }
    $team = qall(
        'SELECT s.*, u.email, u.created_at, c.name AS city_name
         FROM supervisors s
         JOIN users u ON u.id = s.user_id
         JOIN cities c ON c.id = s.city_id
         ORDER BY c.name, s.name'
    );
    layout_staff_start(t('admin.teamTitle'), ['user' => $user, 'nav' => 'admin/team']);
    errors_box($errors);
    echo '<div class="split">';
    echo '<div><h2 class="h2">City supervisors</h2>';
    if (!$team) {
        empty_state('No supervisors', 'Create one account per city.', 'users');
    } else {
        echo '<div class="list">';
        foreach ($team as $t) {
            echo '<div class="list-row static"><div><strong>' . e($t['name']) . '</strong><span>' . e($t['city_name']) . ' · ' . e($t['email']) . ' · ' . e(format_phone((string)$t['phone'])) . '</span></div>';
            echo '<form method="post">' . csrf_field() . '<input type="hidden" name="intent" value="remove"><input type="hidden" name="user_id" value="' . (int)$t['user_id'] . '"><button class="text-btn" type="submit">Remove</button></form></div>';
        }
        echo '</div>';
    }
    echo '</div><form method="post" class="form card">' . csrf_field();
    echo '<input type="hidden" name="intent" value="create">';
    echo '<h2 class="h2">Add supervisor</h2>';
    echo '<label class="field"><span class="field-label">Name</span><input class="input" name="name" required></label>';
    echo '<label class="field"><span class="field-label">Email</span><input class="input" type="email" name="email" required></label>';
    echo '<label class="field"><span class="field-label">Phone</span><input class="input" name="phone" required></label>';
    echo '<label class="field"><span class="field-label">City</span><select class="input" name="city_id" required><option value="">Select</option>';
    foreach ($cities as $c) {
        echo '<option value="' . (int)$c['id'] . '">' . e($c['name']) . '</option>';
    }
    echo '</select></label>';
    echo '<label class="field"><span class="field-label">Temporary password</span><input class="input" type="text" name="password" required minlength="8"></label>';
    echo '<button class="btn btn-primary" type="submit">Create account</button></form></div>';
    layout_staff_end();
}

function page_admin_settings(): void
{
    $user = require_role(['admin']);
    if (is_post()) {
        csrf_verify();
        $keys = [
            'company_name', 'company_legal', 'admin_whatsapp', 'admin_email', 'emergency_phone',
            'support_hours', 'whatsapp_token', 'whatsapp_phone_id', 'email_from',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure',
        ];
        foreach ($keys as $k) {
            if ($k === 'smtp_pass' && post_str('smtp_pass') === '') {
                continue;
            }
            $val = post_str($k);
            if (in_array($k, ['admin_whatsapp', 'emergency_phone'], true) && $val !== '') {
                $val = normalize_phone($val);
            }
            setting_set($k, $val);
        }
        flash_set('ok', 'Settings saved.');
        redirect('admin/settings');
    }
    layout_staff_start(t('admin.settingsTitle'), ['user' => $user, 'nav' => 'admin/settings']);
    echo '<form method="post" class="form settings-form">' . csrf_field();
    echo '<div class="card"><h2 class="h2">Company</h2>';
    echo '<label class="field"><span class="field-label">Company name</span><input class="input" name="company_name" value="' . e(setting('company_name')) . '"></label>';
    echo '<label class="field"><span class="field-label">Legal line</span><input class="input" name="company_legal" value="' . e(setting('company_legal')) . '"></label>';
    echo '<label class="field"><span class="field-label">Support hours</span><input class="input" name="support_hours" value="' . e(setting('support_hours')) . '"></label>';
    echo '<label class="field"><span class="field-label">Admin email</span><input class="input" type="email" name="admin_email" value="' . e(setting('admin_email')) . '"></label>';
    echo '<label class="field"><span class="field-label">From header</span><input class="input" name="email_from" value="' . e(setting('email_from')) . '"></label></div>';

    echo '<div class="card"><h2 class="h2">WhatsApp</h2>';
    echo '<label class="field"><span class="field-label">Operations WhatsApp</span><span class="field-hint">International digits, e.g. 9689XXXXXXX</span>';
    echo '<input class="input" name="admin_whatsapp" value="' . e(setting('admin_whatsapp')) . '"></label>';
    echo '<label class="field"><span class="field-label">Emergency number</span><input class="input" name="emergency_phone" value="' . e(setting('emergency_phone')) . '"></label>';
    echo '<label class="field"><span class="field-label">Cloud API token</span><input class="input" name="whatsapp_token" value="' . e(setting('whatsapp_token')) . '" autocomplete="off"></label>';
    echo '<label class="field"><span class="field-label">Phone number ID</span><input class="input" name="whatsapp_phone_id" value="' . e(setting('whatsapp_phone_id')) . '"></label>';
    echo '<p class="tiny">Without Cloud API, the app still logs messages and provides wa.me links.</p></div>';

    echo '<div class="card"><h2 class="h2">SMTP (optional)</h2>';
    echo '<p class="tiny">Leave host empty to use PHP mail().</p>';
    echo '<label class="field"><span class="field-label">Host</span><input class="input" name="smtp_host" value="' . e(setting('smtp_host')) . '"></label>';
    echo '<div class="grid-2"><label class="field"><span class="field-label">Port</span><input class="input" name="smtp_port" value="' . e(setting('smtp_port', '587')) . '"></label>';
    echo '<label class="field"><span class="field-label">Encryption</span><select class="input" name="smtp_secure">';
    $sec = setting('smtp_secure', 'tls');
    foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $k => $v) {
        echo '<option value="' . e($k) . '"' . ($sec === $k ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    echo '</select></label></div>';
    echo '<label class="field"><span class="field-label">Username</span><input class="input" name="smtp_user" value="' . e(setting('smtp_user')) . '"></label>';
    echo '<label class="field"><span class="field-label">Password</span><input class="input" type="password" name="smtp_pass" value="" placeholder="Unchanged if left blank"></label></div>';
    echo '<button class="btn btn-primary" type="submit">Save settings</button></form>';
    layout_staff_end();
}
