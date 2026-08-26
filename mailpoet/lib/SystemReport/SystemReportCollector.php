<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\SystemReport;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\DataInconsistency\DataInconsistencyController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class SystemReportCollector {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  /** @var DataInconsistencyController */
  private $dataInconsistencyController;

  /** @var CronHelper */
  private $cronHelper;

  /** @var string|null */
  private $cachedCronPingResponse = null;

  /** @var array|\WP_Error|null */
  private $cachedBridgePingResponse = null;

  /** @var Bridge */
  private $bridge;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    SubscribersFeature $subscribersFeature,
    WooCommerceHelper $wooCommerceHelper,
    DataInconsistencyController $dataInconsistencyController,
    Bridge $bridge,
    CronHelper $cronHelper
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->subscribersFeature = $subscribersFeature;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->dataInconsistencyController = $dataInconsistencyController;
    $this->bridge = $bridge;
    $this->cronHelper = $cronHelper;
  }

  public function getData($maskApiKey = false) {
    return array_merge($this->getUserData(), $this->getSiteData($maskApiKey));
  }

  public function getUserData() {
    $currentUser = WPFunctions::get()->wpGetCurrentUser();
    $sender = $this->settings->get('sender', ['address' => null]);

    return [
      'name' => $currentUser->display_name, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'email' => $sender['address'],
    ];
  }

  public function getSiteData($maskApiKey = false) {
    global $wpdb;

    $dbVersion = $wpdb->get_var('SELECT @@VERSION');
    $mta = $this->settings->get('mta');
    $currentTheme = WPFunctions::get()->wpGetTheme();
    $premiumKey = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME) ?: $this->settings->get(Bridge::API_KEY_SETTING_NAME);

    if ($maskApiKey) {
      $premiumKey = $this->maskApiKey($premiumKey);
    }

    $cronDaemonStatus = $this->cronHelper->getDaemon() ?? [];
    try {
      $cronPingUrl = $this->cronHelper->getCronUrl(CronDaemon::ACTION_PING);
      $cronPingResponse = $this->getCronPingResponse();
    } catch (\Exception $e) {
      $cronPingUrl = __('Can‘t generate cron URL.', 'mailpoet') . ' (' . $e->getMessage() . ')';
      $cronPingResponse = $cronPingUrl;
    }

    $mailerLog = MailerLog::getMailerLog();
    // Read the status before overwriting "sent", which replaces the per-day map with a total.
    $isSendingPaused = MailerLog::isSendingPaused($mailerLog);
    $mailerLog['sent'] = MailerLog::sentSince();

    $inconsistencyStatus = $this->dataInconsistencyController->getInconsistentDataStatus();
    unset($inconsistencyStatus['total']);
    $inconsistencyStatus += $this->dataInconsistencyController->getUnfixableDataStatus();

    $pingBridgeResponse = $this->getBridgePingResponse();
    $pingResponse = $this->wp->isWpError($pingBridgeResponse)
      ? $pingBridgeResponse->get_error_message() // @phpstan-ignore-line
      : $this->wp->wpRemoteRetrieveResponseCode($pingBridgeResponse) . ' HTTP status code';

    $ApiKeyState = $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME . '.state');
    $premiumKeyState = $this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME . '.state');

    $activePluginFiles = $this->wp->getOption('active_plugins', []);
    $activePluginFiles = is_array($activePluginFiles)
      ? array_map(static fn($v): string => is_scalar($v) ? (string)$v : '', $activePluginFiles)
      : [];

    // the HelpScout Beacon API has a limit of 20 attribute-value pairs (https://developer.helpscout.com/beacon-2/web/javascript-api/#beacon-session-data)
    return [
      'PHP version' => PHP_VERSION,
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'MailPoet Premium/MSS key' => $premiumKey,
      'WordPress version' => $this->wp->getBloginfo('version'),
      'Database version' => $dbVersion,
      'Web server' => is_string($_SERVER['SERVER_SOFTWARE'] ?? null) && $_SERVER['SERVER_SOFTWARE'] !== '' ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A',
      'Server OS' => (function_exists('php_uname')) ? php_uname() : 'N/A',
      'WP info' => $this->formatCompositeField([
        'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
        'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
        'WP_DEBUG' => WP_DEBUG,
        'WordPress language' => $this->wp->getLocale(),
        'WordPress timezone' => $this->wp->wpTimezoneString(),
      ]),
      'PHP info' => $this->formatCompositeField([
        'PHP max_execution_time' => ini_get('max_execution_time'),
        'PHP memory_limit' => ini_get('memory_limit'),
        'PHP upload_max_filesize' => ini_get('upload_max_filesize'),
        'PHP post_max_size' => ini_get('post_max_size'),
      ]),
      'Multisite environment?' => (is_multisite() ? 'Yes' : 'No'),
      'Current Theme' => $currentTheme->get('Name') .
        ' (version ' . $currentTheme->get('Version') . ')',
      'Active Plugin names' => join(", ", $activePluginFiles),
      'Sending Method' => $mta['method'],
      'MailPoet Sending Service' => $this->formatCompositeField([
        'Is reachable' => $this->bridge->validateBridgePingResponse($pingBridgeResponse) ? 'Yes' : 'No',
        'Ping response' => $pingResponse,
        'API key state' => $ApiKeyState ?? 'Unset',
        'Premium key state' => $premiumKeyState ?? 'Unset',
      ]),
      'Sending Frequency' => ($mta['method'] === Mailer::METHOD_MAILPOET)
        ? __('Managed by MailPoet Sending Service', 'mailpoet')
        : sprintf(
          '%d emails every %d minutes',
          $mta['frequency']['emails'],
          $mta['frequency']['interval']
        ),
      'MailPoet sending info' => $this->formatCompositeField([
        "Send all site's emails with" => ($this->settings->get('send_transactional_emails') ? 'current sending method' : 'default WordPress sending method'),
        'Task Scheduler method' => $this->settings->get('cron_trigger.method'),
        'Default FROM address' => $this->settings->get('sender.address'),
        'Default Reply-To address' => $this->settings->get('reply_to.address'),
        'Bounce Email Address' => $this->settings->get('bounce.address'),
      ]),
      'MailPoet Cron / Action Scheduler' => $this->formatCompositeField([
        'Status' => $this->formatCronDaemonStatus($cronDaemonStatus['status'] ?? null),
        'Is reachable' => $this->cronHelper->validatePingResponse($cronPingResponse) ? 'Yes' : 'No',
        'Ping URL' => $cronPingUrl,
        'Ping response' => $cronPingResponse,
        'Last updated' => $this->formatOptionalTimestamp($cronDaemonStatus['updated_at'] ?? null, 'Never'),
        'Last run start' => $this->formatOptionalTimestamp($cronDaemonStatus['run_started_at'] ?? null, 'Never'),
        'Last run end' => $this->formatOptionalTimestamp($cronDaemonStatus['run_completed_at'] ?? null, 'Never'),
        'Last seen error' => $cronDaemonStatus['last_error'] ?? 'None',
        'Last seen error date' => $this->formatOptionalTimestamp($cronDaemonStatus['last_error_date'] ?? null, 'None'),
      ]),
      'Total number of subscribers' => $this->subscribersFeature->getSubscribersCount(),
      'Plugin installed at' => $this->settings->get('installed_at'),
      'Installed via WooCommerce onboarding wizard' => $this->wooCommerceHelper->wasMailPoetInstalledViaWooCommerceOnboardingWizard(),
      'Sending queue status' => $this->formatCompositeField([
        'Status' => $isSendingPaused ? 'Paused' : 'Running',
        'Started at' => $this->formatOptionalTimestamp($mailerLog['started'] ?? null, 'Unknown'),
        'Emails sent' => $mailerLog['sent'],
        'Retry attempts' => $mailerLog['retry_attempt'] ?? 0,
        'Retry at' => $this->formatOptionalTimestamp($mailerLog['retry_at'] ?? null, 'None'),
        'Last seen error' => isset($mailerLog['error'])
          ? $mailerLog['error']['error_message'] . ' (' . $mailerLog['error']['operation'] . ')'
          : 'None',
      ]),
      'Data inconsistency status' => $this->formatCompositeField($this->convertKeysToTitleCase($inconsistencyStatus)),
    ];
  }

  /**
   * @return array<int, array{
   *   plugin: string,
   *   name: string,
   *   author: string,
   *   version: string,
   *   versionLatest: ?string
   * }>
   */
  public function getActivePluginsData(): array {
    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $activePluginFiles = $this->wp->getOption('active_plugins', []);
    $activePluginFiles = is_array($activePluginFiles)
      ? array_map(static fn($v): string => is_scalar($v) ? (string)$v : '', $activePluginFiles)
      : [];
    $allPlugins = $this->wp->getPlugins();
    $updateTransient = $this->wp->getSiteTransient('update_plugins');
    $updateResponses = (
      is_object($updateTransient) &&
      isset($updateTransient->response) &&
      is_array($updateTransient->response)
    ) ? $updateTransient->response : [];

    $activePlugins = [];
    foreach ($activePluginFiles as $pluginFile) {
      $plugin = $allPlugins[$pluginFile] ?? [];
      $authorName = (string)($plugin['AuthorName'] ?? '');
      if ($authorName === '') {
        $authorName = $this->wp->wpStripAllTags((string)($plugin['Author'] ?? ''));
      }
      if ($authorName === '') {
        $authorName = __('Unknown', 'mailpoet');
      }

      $latestVersion = null;
      $pluginUpdate = $updateResponses[$pluginFile] ?? null;
      if (is_object($pluginUpdate) && !empty($pluginUpdate->new_version)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $latestVersion = (string)$pluginUpdate->new_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      } elseif (is_array($pluginUpdate) && isset($pluginUpdate['new_version']) && (is_string($pluginUpdate['new_version']) || is_int($pluginUpdate['new_version']) || is_float($pluginUpdate['new_version'])) && $pluginUpdate['new_version'] !== '') {
        $latestVersion = (string)$pluginUpdate['new_version'];
      }

      $activePlugins[] = [
        'plugin' => (string)$pluginFile,
        'name' => (string)($plugin['Name'] ?? $pluginFile),
        'author' => $authorName,
        'version' => (string)($plugin['Version'] ?? __('Unknown', 'mailpoet')),
        'versionLatest' => $latestVersion,
      ];
    }

    return $activePlugins;
  }

  public function getCronPingResponse(): string {
    if ($this->cachedCronPingResponse !== null) {
      return $this->cachedCronPingResponse;
    }

    $this->cachedCronPingResponse = $this->cronHelper->pingDaemon();
    return $this->cachedCronPingResponse;
  }

  /**
   * @return array|\WP_Error
   */
  public function getBridgePingResponse() {
    if ($this->cachedBridgePingResponse !== null) {
      return $this->cachedBridgePingResponse;
    }

    $this->cachedBridgePingResponse = $this->bridge->pingBridge();
    return $this->cachedBridgePingResponse;
  }

  /**
   * @param $fields array of key-value pairs
   * @return string in the format "key1: value1 - key2: value2 - ..."
   */
  private function formatCompositeField(array $fields) {
    if (empty($fields)) {
      return '';
    }

    return implode(' - ', array_map(function ($key, $value) {
      return $key . ': ' . $this->formatCompositeValue($value);
    }, array_keys($fields), array_values($fields)));
  }

  /**
   * @param mixed $value
   */
  private function formatCompositeValue($value): string {
    if ($value instanceof \WP_Error) {
      return $value->get_error_message();
    }
    if (is_object($value) && method_exists($value, '__toString')) {
      return (string)$value;
    }
    if (is_array($value) || is_object($value)) {
      $encodedValue = $this->wp->wpJsonEncode($value);
      return is_string($encodedValue) ? $encodedValue : '';
    }
    if (is_scalar($value)) {
      return (string)$value;
    }
    if (is_resource($value)) {
      return get_resource_type($value);
    }
    return '';
  }

  private function convertKeysToTitleCase(array $array): array {
    $result = [];
    foreach ($array as $key => $value) {
      $titleCaseKey = ucfirst(str_replace('_', ' ', $key));
      $result[$titleCaseKey] = $value;
    }

    return $result;
  }

  private function formatTimestamp(int $timestamp): string {
    return (new \DateTimeImmutable('@' . $timestamp))
      ->setTimezone($this->wp->wpTimezone())
      ->format('Y-m-d H:i:s');
  }

  /**
   * @param mixed $timestamp
   */
  private function formatOptionalTimestamp($timestamp, string $fallback): string {
    if (!is_numeric($timestamp)) {
      return $fallback;
    }

    return $this->formatTimestamp((int)$timestamp);
  }

  /**
   * The wording here must match the Sending Queue and Cron tables on the System Status
   * tab, so that a copied report and the screen a user is looking at never disagree.
   */
  private function formatCronDaemonStatus(?string $status): string {
    if ($status === CronHelper::DAEMON_STATUS_ACTIVE) {
      return 'Running';
    }

    if ($status === CronHelper::DAEMON_STATUS_INACTIVE) {
      return 'Waiting for the next run';
    }

    return 'Never started';
  }

  protected function maskApiKey($key) {
    // the length of this particular key is an even number.
    // for odd lengths this method will change the total number of characters (which shouldn't be a problem in this context).
    $halfKeyLength = (int)(strlen($key ?? '') / 2);

    return substr($key ?? '', 0, $halfKeyLength) . str_repeat('*', $halfKeyLength);
  }
}
