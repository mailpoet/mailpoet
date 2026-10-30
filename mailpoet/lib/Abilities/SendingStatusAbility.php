<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class SendingStatusAbility implements Ability {
  public const NAME = 'mailpoet/get-sending-status';

  private const STATUS_ACTIVE = 'active';
  private const STATUS_PAUSED = 'paused';
  private const STATUS_WAITING_FOR_RETRY = 'waiting_for_retry';
  private const STATUS_FREQUENCY_LIMIT_REACHED = 'frequency_limit_reached';

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    SubscribersFeature $subscribersFeature,
    CronHelper $cronHelper,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->subscribersFeature = $subscribersFeature;
    $this->cronHelper = $cronHelper;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Get MailPoet sending status', 'mailpoet'),
      'description' => __('Use this first when MailPoet emails are not sent or arrive late. Returns the sending method, whether sending is paused or waiting for a retry and the last error, the MailPoet Sending Service key state and restrictions, the plan limits for subscribers and email volume, and the state of the cron that sends emails. Reads stored state only, so key states can be a few hours old.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'additionalProperties' => false,
        'default' => [],
      ],
      'output_schema' => $this->getOutputSchema(),
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getReadOnlyMeta(),
    ];
  }

  public function hasPermission(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_SETTINGS);
  }

  public function execute(): array {
    $mssKeyState = $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME);
    $mssKeyState = is_array($mssKeyState) ? $mssKeyState : [];
    $subscribersLimit = $this->subscribersFeature->getSubscribersLimit();
    $emailVolumeLimit = $this->subscribersFeature->getEmailVolumeLimit();

    return [
      'sending_method' => (string)$this->settings->get('mta.method'),
      'mailpoet_sending_service' => [
        'enabled' => $this->settings->get('mta.method') === Mailer::METHOD_MAILPOET,
        'key_state' => $this->getStringOrNull($mssKeyState['state'] ?? null),
        'access_restriction' => $this->getStringOrNull($mssKeyState['access_restriction'] ?? null),
      ],
      'premium_key_state' => $this->getStringOrNull($this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME . '.state')),
      'subscribers' => [
        'count' => $this->subscribersFeature->getSubscribersCount(),
        'limit' => $subscribersLimit ? (int)$subscribersLimit : null,
        'limit_reached' => $this->subscribersFeature->check(),
      ],
      'email_volume' => [
        'sent' => $this->subscribersFeature->getEmailsSent(),
        'limit' => $emailVolumeLimit ?: null,
        'limit_reached' => $this->subscribersFeature->checkEmailVolumeLimitIsReached(),
      ],
      'sending' => $this->getSendingData(),
      'cron' => $this->getCronData(),
    ];
  }

  private function getSendingData(): array {
    $mailerLog = MailerLog::getMailerLog();
    $error = MailerLog::getError($mailerLog);
    $retryAt = $mailerLog['retry_at'] ?? null;

    return [
      'status' => $this->getSendingStatus(),
      'retry_at' => $this->formatTimestamp($retryAt),
      'retry_attempt' => isset($mailerLog['retry_attempt']) ? (int)$mailerLog['retry_attempt'] : null,
      'last_error' => $error ? [
        'operation' => (string)$error['operation'],
        'message' => self::maskPersonalData((string)$error['error_message']),
        'code' => isset($error['error_code']) ? (string)$error['error_code'] : null,
      ] : null,
    ];
  }

  private function getSendingStatus(): string {
    if (MailerLog::isSendingPaused()) {
      return self::STATUS_PAUSED;
    }
    if (MailerLog::isSendingWaitingForRetry()) {
      return self::STATUS_WAITING_FOR_RETRY;
    }
    if (MailerLog::isSendingLimitReached()) {
      return self::STATUS_FREQUENCY_LIMIT_REACHED;
    }
    return self::STATUS_ACTIVE;
  }

  private function getCronData(): array {
    $daemon = $this->cronHelper->getDaemon();
    $daemon = is_array($daemon) ? $daemon : [];
    $lastErrors = is_array($daemon['last_error'] ?? null) ? $daemon['last_error'] : [];

    return [
      'trigger_method' => $this->getStringOrNull($this->settings->get(CronTrigger::SETTING_CURRENT_METHOD)),
      'daemon_status' => $this->getStringOrNull($daemon['status'] ?? null),
      'last_run_started_at' => $this->formatTimestamp($daemon['run_started_at'] ?? null),
      'last_run_completed_at' => $this->formatTimestamp($daemon['run_completed_at'] ?? null),
      'last_errors' => array_values(array_map(function ($error): array {
        return [
          'worker' => is_array($error) && is_string($error['worker'] ?? null) ? $error['worker'] : '',
          'message' => self::maskPersonalData(is_array($error) && is_string($error['message'] ?? null) ? $error['message'] : ''),
        ];
      }, $lastErrors)),
      'last_error_at' => $this->formatTimestamp($daemon['last_error_date'] ?? null),
    ];
  }

  /**
   * Mailer errors can quote subscribers. MailPoet formats them as "First Last <address>"
   * without quoting, so a name can contain ",", ":" or brackets.
   *
   * 1. MailerError lists failed subscribers as "(First Last <address>: reason)". The first
   *    pattern replaces such an item from its "(" up to the ">" after the address. The name
   *    may contain any character except "<" and ">", and "(" only in balanced pairs, so an
   *    earlier "(550)" in the message can't start a match.
   * 2. A name with an unpaired "(" makes the first pattern start at that "(", which leaves
   *    the start of the name before "([subscriber]". The second pattern removes it: one or
   *    more "(" with text up to the masked item, without a ":" that would mean the text
   *    belongs to the message or to the previous item.
   * 3. Outside that list, the third pattern replaces a name that starts after the nearest
   *    "(", ",", ":" or whitespace and runs up to "<...@...>".
   * 4. The fourth pattern replaces any remaining address: a local part, "@" and a domain
   *    (dotless domains included).
   */
  public static function maskPersonalData(string $message): string {
    $message = (string)preg_replace('/\((?:[^(<>]|\([^()<>]*\))*<[^<>\s]+@[^<>\s]+>/', '([subscriber]', $message);
    $message = (string)preg_replace('/(?:\([^(<>:]*)+\(\[subscriber\]/', '([subscriber]', $message);
    $message = (string)preg_replace('/[^(),:\s][^(),:]*<[^<>\s]+@[^<>\s]+>/', '[subscriber]', $message);
    return (string)preg_replace('/[^\s<>"\'(),;:]+@[^\s<>"\'(),;:]+/', '[email]', $message);
  }

  /** @param mixed $timestamp */
  private function formatTimestamp($timestamp): ?string {
    if (!is_numeric($timestamp) || (int)$timestamp <= 0) {
      return null;
    }
    return gmdate('c', (int)$timestamp);
  }

  /** @param mixed $value */
  private function getStringOrNull($value): ?string {
    return is_string($value) && $value !== '' ? $value : null;
  }

  private function getOutputSchema(): array {
    $nullableString = ['type' => ['string', 'null']];
    $nullableDateTime = ['type' => ['string', 'null'], 'format' => 'date-time'];
    return [
      'type' => 'object',
      'properties' => [
        'sending_method' => [
          'type' => 'string',
          'description' => __('How emails are sent: MailPoet (MailPoet Sending Service), SMTP, PHPMail, AmazonSES, or SendGrid.', 'mailpoet'),
        ],
        'mailpoet_sending_service' => [
          'type' => 'object',
          'properties' => [
            'enabled' => ['type' => 'boolean'],
            'key_state' => $nullableString + ['description' => __('valid, invalid, expiring, already_used, valid_underprivileged, or null when no key is set.', 'mailpoet')],
            'access_restriction' => $nullableString + ['description' => __('Why the service refuses to send: email_volume_limit_reached, subscribers_limit_reached, insufficient_privileges, or null.', 'mailpoet')],
          ],
        ],
        'premium_key_state' => $nullableString,
        'subscribers' => [
          'type' => 'object',
          'properties' => [
            'count' => ['type' => 'integer'],
            'limit' => ['type' => ['integer', 'null'], 'description' => __('Plan subscriber limit, null when unlimited.', 'mailpoet')],
            'limit_reached' => ['type' => 'boolean', 'description' => __('When true, MailPoet stops sending until the plan is upgraded or subscribers are removed.', 'mailpoet')],
          ],
        ],
        'email_volume' => [
          'type' => 'object',
          'properties' => [
            'sent' => ['type' => 'integer', 'description' => __('Emails sent in the current billing period, as last reported by the MailPoet Sending Service.', 'mailpoet')],
            'limit' => ['type' => ['integer', 'null']],
            'limit_reached' => ['type' => 'boolean'],
          ],
        ],
        'sending' => [
          'type' => 'object',
          'properties' => [
            'status' => [
              'type' => 'string',
              'enum' => [self::STATUS_ACTIVE, self::STATUS_PAUSED, self::STATUS_WAITING_FOR_RETRY, self::STATUS_FREQUENCY_LIMIT_REACHED],
              'description' => __('paused: sending stopped after an error and needs action in MailPoet settings. waiting_for_retry: a temporary error, MailPoet retries automatically. frequency_limit_reached: the configured sending frequency is used up and sending resumes on its own.', 'mailpoet'),
            ],
            'retry_at' => $nullableDateTime,
            'retry_attempt' => ['type' => ['integer', 'null']],
            'last_error' => [
              'type' => ['object', 'null'],
              'properties' => [
                'operation' => ['type' => 'string', 'description' => __('What failed, e.g. send, connect, authorization, pending_approval, email_limit_reached, subscriber_limit_reached.', 'mailpoet')],
                'message' => ['type' => 'string', 'description' => __('Error message with subscriber names replaced by [subscriber] and email addresses by [email].', 'mailpoet')],
                'code' => $nullableString,
              ],
            ],
          ],
        ],
        'cron' => [
          'type' => 'object',
          'description' => __('The background task runner that sends emails. A last run long ago means emails are queued but not sent.', 'mailpoet'),
          'properties' => [
            'trigger_method' => $nullableString,
            'daemon_status' => $nullableString,
            'last_run_started_at' => $nullableDateTime,
            'last_run_completed_at' => $nullableDateTime,
            'last_errors' => [
              'type' => 'array',
              'items' => [
                'type' => 'object',
                'properties' => [
                  'worker' => ['type' => 'string'],
                  'message' => ['type' => 'string'],
                ],
              ],
            ],
            'last_error_at' => $nullableDateTime,
          ],
        ],
      ],
    ];
  }
}
