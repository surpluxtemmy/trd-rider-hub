<?php
declare(strict_types=1);

function notify_inapp(int $userId, string $title, string $body, array $meta = []): void
{
    q(
        'INSERT INTO notifications (user_id, channel, title, body, meta) VALUES (?, ?, ?, ?, ?)',
        [$userId, 'inapp', $title, $body, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null]
    );
}

function notify_log(int $userId, string $channel, string $title, string $body, array $meta = []): void
{
    q(
        'INSERT INTO notifications (user_id, channel, title, body, meta) VALUES (?, ?, ?, ?, ?)',
        [$userId, $channel, $title, $body, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null]
    );
}

function admin_users(): array
{
    return qall("SELECT id, name, email, phone FROM users WHERE role = 'admin'");
}

function city_supervisors(int $cityId): array
{
    return qall(
        'SELECT u.id, u.email, u.phone, s.name, s.phone AS sup_phone
         FROM supervisors s JOIN users u ON u.id = s.user_id
         WHERE s.city_id = ?',
        [$cityId]
    );
}

function parse_from_header(string $from): array
{
    if (preg_match('/^(.*)<([^>]+)>$/', $from, $m)) {
        return [trim($m[1], ' "'), trim($m[2])];
    }
    return ['TRD Rider Hub', $from];
}

function send_email(string $to, string $subject, string $body, int $userId = 0): bool
{
    $fromRaw = setting('email_from', 'TRD Rider Hub <noreply@trd.om>');
    [$fromName, $fromEmail] = parse_from_header($fromRaw);
    $ok = false;
    $via = 'none';

    if (setting('smtp_host') !== '') {
        $ok = smtp_send($to, $subject, $body, $fromEmail, $fromName);
        $via = 'smtp';
    }
    if (!$ok) {
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . (setting('admin_email') ?: $fromEmail),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: TRD-Rider-Hub',
        ];
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
        $via = 'mail';
    }

    if ($userId > 0) {
        notify_log($userId, 'email', $subject, $body, ['to' => $to, 'ok' => $ok, 'via' => $via]);
    }
    return (bool)$ok;
}

function smtp_send(string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
{
    $host = setting('smtp_host');
    $port = (int)(setting('smtp_port', '587') ?: 587);
    $user = setting('smtp_user');
    $pass = setting('smtp_pass');
    $secure = strtolower(setting('smtp_secure', 'tls'));
    if ($host === '') {
        return false;
    }

    $target = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($target, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        return false;
    }
    stream_set_timeout($fp, 12);

    $read = static function () use ($fp): string {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 512);
            if ($line === false) {
                break;
            }
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = static function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };

    $banner = $read();
    if ($banner === '') {
        fclose($fp);
        return false;
    }
    $ehlo = $cmd('EHLO trd-rider-hub');
    if ($secure === 'tls' && stripos($ehlo, 'STARTTLS') !== false) {
        $cmd('STARTTLS');
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            fclose($fp);
            return false;
        }
        $cmd('EHLO trd-rider-hub');
    }
    if ($user !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $auth = $cmd(base64_encode($pass));
        if (!str_starts_with($auth, '235')) {
            fclose($fp);
            return false;
        }
    }
    $cmd('MAIL FROM:<' . $fromEmail . '>');
    $rcpt = $cmd('RCPT TO:<' . $to . '>');
    if (!str_starts_with($rcpt, '250') && !str_starts_with($rcpt, '251')) {
        fclose($fp);
        return false;
    }
    $cmd('DATA');
    $payload = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "\r\n" . $body . "\r\n.";
    $data = $cmd($payload);
    $cmd('QUIT');
    fclose($fp);
    return str_starts_with($data, '250');
}

function send_whatsapp(string $phone, string $text, int $userId = 0, string $title = 'WhatsApp'): array
{
    $n = normalize_phone($phone);
    $link = wa_link($n, $text);
    $ok = false;
    $via = 'link';
    $token = setting('whatsapp_token');
    $phoneId = setting('whatsapp_phone_id');
    if ($token !== '' && $phoneId !== '' && $n !== '') {
        $res = http_post_json(
            'https://graph.facebook.com/v20.0/' . rawurlencode($phoneId) . '/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $n,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $text],
            ],
            ['Authorization: Bearer ' . $token]
        );
        if ($res !== null) {
            $j = json_decode($res, true);
            $ok = is_array($j) && isset($j['messages']);
            $via = $ok ? 'cloud' : 'cloud_error';
        }
    }
    if ($userId > 0) {
        notify_log($userId, 'whatsapp', $title, $text, ['to' => $n, 'ok' => $ok, 'via' => $via, 'wa' => $link]);
    }
    return ['ok' => $ok, 'link' => $link, 'via' => $via];
}

function status_message(array $app, string $to, string $note = ''): string
{
    $no = $app['application_no'] ?? '';
    $name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $company = setting('company_name', 'TRD Rider Hub');
    $lines = [
        'pending' => $company . ': we received application ' . $no . '. Our team will review your documents.',
        'accepted' => $company . ': your documents for ' . $no . ' were accepted for review.',
        'processing' => $company . ': operations is now processing application ' . $no . '.',
        'awaiting_sponsor' => $company . ': application ' . $no . ' has been sent to the sponsor.',
        'sponsor_approved' => $company . ': the sponsor approved application ' . $no . '.',
        'completed' => $company . ': welcome to the fleet. Your ID is ' . ($app['rider_code'] ?? '') . '.',
        'rejected' => $company . ': application ' . $no . ' was not approved.' . ($note !== '' ? ' ' . $note : ''),
    ];
    $msg = $lines[$to] ?? ($company . ': application ' . $no . ' is now ' . status_label($to) . '.');
    if ($name !== '') {
        $msg = 'Hello ' . $name . '. ' . $msg;
    }
    return $msg;
}

function notify_new_application(array $app, array $user): string
{
    $type = $app['type'] === 'driver' ? 'Driver' : 'Rider';
    $title = 'New ' . strtolower($type) . ' application';
    $body = $type . ' application ' . $app['application_no'] . ' from ' . $user['name']
        . ' (' . format_phone((string)$app['phone']) . ').';
    $text = 'TRD: new ' . strtolower($type) . ' application ' . $app['application_no']
        . "\nName: " . display_name_of($user, $app)
        . "\nPhone: " . format_phone((string)$app['phone'])
        . "\nCity: " . (string)($app['city_name'] ?? '')
        . "\nOpen admin to review.";

    foreach (admin_users() as $admin) {
        notify_inapp((int)$admin['id'], $title, $body, ['application_id' => $app['id']]);
        send_email((string)$admin['email'], $title . ' ' . $app['application_no'], $text, (int)$admin['id']);
    }
    $wa = setting('admin_whatsapp');
    $link = '';
    if ($wa !== '') {
        $adminsList = admin_users();
        $logUser = (int)($adminsList[0]['id'] ?? $user['id']);
        $res = send_whatsapp($wa, $text, $logUser, $title);
        $link = $res['link'];
    }
    notify_inapp((int)$user['id'], 'Application submitted', 'We received ' . $app['application_no'] . '. Track progress on Home.', ['application_id' => $app['id']]);
    send_email((string)$user['email'], 'We received ' . $app['application_no'], status_message($app, 'pending'), (int)$user['id']);
    send_whatsapp((string)$user['phone'], status_message($app, 'pending'), (int)$user['id'], 'Application submitted');
    return $link;
}

function notify_status_change(array $app, array $applicant, string $from, string $to, string $note = ''): void
{
    $title = 'Application ' . status_label($to);
    $msg = status_message($app, $to, $note);
    notify_inapp((int)$applicant['id'], $title, $msg, ['application_id' => $app['id'], 'status' => $to]);
    send_email((string)$applicant['email'], $title . ' · ' . $app['application_no'], $msg . ($note !== '' && $to !== 'rejected' ? "\n\nNote: " . $note : ''), (int)$applicant['id']);
    send_whatsapp((string)($app['phone'] ?: $applicant['phone']), $msg, (int)$applicant['id'], $title);

    foreach (admin_users() as $admin) {
        if ((int)$admin['id'] === (int)$applicant['id']) {
            continue;
        }
        notify_inapp((int)$admin['id'], 'Status updated', $app['application_no'] . ' is now ' . status_label($to) . '.', ['application_id' => $app['id']]);
    }
}

function notify_emergency(array $em, array $user): void
{
    $title = 'Emergency SOS';
    $maps = '';
    if (!empty($em['lat']) && !empty($em['lng'])) {
        $maps = 'https://maps.google.com/?q=' . rawurlencode($em['lat'] . ',' . $em['lng']);
    }
    $body = ($user['name'] ?? 'Rider') . ' sent an SOS'
        . (!empty($user['city_name']) ? ' · ' . $user['city_name'] : '')
        . "\n" . $em['message']
        . ($maps !== '' ? "\nLocation: " . $maps : '')
        . "\nPhone: " . format_phone((string)$user['phone']);

    $targets = admin_users();
    $cityId = (int)($user['city_id'] ?? 0);
    if ($cityId) {
        foreach (city_supervisors($cityId) as $s) {
            $targets[] = $s;
        }
    }
    $seen = [];
    foreach ($targets as $t) {
        $id = (int)$t['id'];
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        notify_inapp($id, $title, $body, ['emergency_id' => $em['id']]);
        if (!empty($t['email'])) {
            send_email((string)$t['email'], 'SOS · ' . $user['name'], $body, $id);
        }
        $ph = (string)($t['sup_phone'] ?? $t['phone'] ?? '');
        if ($ph !== '') {
            send_whatsapp($ph, "TRD SOS\n" . $body, $id, $title);
        }
    }
    $adminWa = setting('admin_whatsapp');
    if ($adminWa !== '') {
        send_whatsapp($adminWa, "TRD SOS\n" . $body, (int)$user['id'], $title);
    }
    $emPhone = setting('emergency_phone');
    if ($emPhone !== '' && $emPhone !== $adminWa) {
        send_whatsapp($emPhone, "TRD SOS\n" . $body, (int)$user['id'], $title);
    }
    notify_inapp((int)$user['id'], 'SOS sent', 'Your city supervisor and operations were notified.', ['emergency_id' => $em['id']]);
}

function notify_ticket_reply(array $ticket, array $replyUser, string $body): void
{
    $owner = qone('SELECT * FROM users WHERE id = ?', [(int)$ticket['user_id']]);
    if (!$owner) {
        return;
    }
    $title = 'Reply: ' . $ticket['subject'];
    notify_inapp((int)$owner['id'], $title, $body, ['ticket_id' => $ticket['id']]);
    send_email((string)$owner['email'], $title, $replyUser['name'] . " replied to your support ticket:\n\n" . $body, (int)$owner['id']);
}

function notify_appointment(array $apt, array $user, string $what): void
{
    $kind = APPOINTMENT_KINDS[$apt['kind']] ?? $apt['kind'];
    $when = dt_format((string)$apt['scheduled_for']);
    $title = 'Appointment ' . $what;
    $body = $kind . ' · ' . $when . ($apt['location'] ? ' · ' . $apt['location'] : '');
    notify_inapp((int)$user['id'], $title, $body, ['appointment_id' => $apt['id']]);
    send_email((string)$user['email'], $title, $body . "\n\n" . (string)($apt['notes'] ?? ''), (int)$user['id']);
    send_whatsapp((string)$user['phone'], 'TRD: ' . $title . ' — ' . $body, (int)$user['id'], $title);
}
