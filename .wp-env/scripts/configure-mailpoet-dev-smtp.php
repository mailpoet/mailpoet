<?php
/**
 * Configures MailPoet's sending method to SMTP pointing at the local Mailpit
 * catcher. Invoked by .wp-env.json's lifecycleScripts.afterStart via
 * `wp eval-file`. Idempotent — does nothing if MailPoet is already set up
 * for SMTP. Safe to run on every env:start.
 *
 * Required settings:
 *   - version                 set to MAILPOET_VERSION so the welcome wizard
 *                             doesn't trigger on first admin visit and
 *                             overwrite our config
 *   - mta_group = 'smtp'      The UI group (`mailpoet` / `website` / `smtp`)
 *                             that matches mta.method.
 *   - smtp_provider = 'manual' Drives the "Send with…" dropdown. 'manual'
 *                             renders the "Your own SMTP server" option;
 *                             'server' renders "Your host's SMTP service"
 *                             (PHPMail / web server).
 *   - mta.method = 'SMTP'     Use MailPoet's SMTP mailer.
 *   - mta.host/port           Target Mailpit on host.docker.internal:1026.
 *   - sender.address          MailerFactory refuses to run without one.
 */

// Refuse to run over HTTP. The script lives under wp-env's WordPress
// docroot (mapped there so `wp eval-file` can reach it), but it's only
// meant to run via wp-cli inside the container. Guarding against direct
// request keeps the settings-mutation code out of reach even if a
// future rewrite rule accidentally routes this path through WordPress.
if (PHP_SAPI !== 'cli' && !defined('WP_CLI')) {
    http_response_code(404);
    exit;
}

if (!class_exists(\MailPoet\DI\ContainerWrapper::class)) {
    echo "MailPoet not yet loaded — skipping SMTP auto-config.\n";
    return;
}

try {
    $container = \MailPoet\DI\ContainerWrapper::getInstance();
    $settings = $container->get(\MailPoet\Settings\SettingsController::class);
} catch (\Throwable $e) {
    echo "MailPoet container not ready: " . $e->getMessage() . "\n";
    return;
}

$currentMta = $settings->get('mta', []);
$desired = [
    'method' => 'SMTP',
    'host' => defined('MAILPOET_DEV_SMTP_HOST') ? MAILPOET_DEV_SMTP_HOST : 'host.docker.internal',
    'port' => defined('MAILPOET_DEV_SMTP_PORT') ? (int) MAILPOET_DEV_SMTP_PORT : 1026,
    // UI radio values: '1' = Yes, '-1' = No. Use '-1' so the No radio is
    // pre-selected. MailerFactory casts to int; only 1 enables auth.
    'authentication' => '-1',
    'encryption' => '',
    'login' => '',
    'password' => '',
    'frequency' => $currentMta['frequency'] ?? [
        'emails' => '25',
        'interval' => '5',
    ],
];

$alreadyConfigured = ($currentMta['method'] ?? null) === $desired['method']
    && ($currentMta['host'] ?? null) === $desired['host']
    && (int) ($currentMta['port'] ?? 0) === $desired['port']
    && $settings->get('mta_group') === 'smtp'
    && $settings->get('smtp_provider') === 'manual';

if (!$alreadyConfigured) {
    $settings->set('mta_group', 'smtp');
    $settings->set('smtp_provider', 'manual');
    $settings->set('mta', $desired);
    echo sprintf(
        "MailPoet mailer configured: SMTP (manual) -> %s:%d\n",
        $desired['host'],
        $desired['port']
    );
}

$sender = $settings->get('sender', []);
if (empty($sender['address'])) {
    $settings->set('sender', [
        'name' => 'MailPoet Dev',
        'address' => 'dev@mailpoet.local',
    ]);
    echo "MailPoet sender seeded (dev@mailpoet.local).\n";
}

// Suppress the welcome wizard on a fresh install. Without this, the first
// admin visit triggers the wizard which overwrites mta back to PHPMail
// (`web server` mode).
if ($settings->get('version') === null && defined('MAILPOET_VERSION')) {
    $settings->set('version', MAILPOET_VERSION);
    echo "MailPoet welcome wizard marked complete (version=" . MAILPOET_VERSION . ").\n";
}

if ($alreadyConfigured && !empty($sender['address']) && $settings->get('version') !== null) {
    echo "MailPoet SMTP already configured; nothing to do.\n";
}
