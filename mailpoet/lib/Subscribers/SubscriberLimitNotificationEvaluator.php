<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationEvaluator {
  public const SETTINGS_KEY = 'subscriber_limit_threshold_notifications';
  private const THRESHOLDS = [95, 99];

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var SubscriberLimitNotificationMailer */
  private $mailer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    SubscribersFeature $subscribersFeature,
    SubscriberLimitNotificationMailer $mailer,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->subscribersFeature = $subscribersFeature;
    $this->mailer = $mailer;
    $this->wp = $wp;
  }

  public function evaluate(): void {
    $storedState = $this->settings->fetch(self::SETTINGS_KEY, []);
    $state = $this->normalizeState($storedState);
    $limit = $this->subscribersFeature->getFreeSubscriberLimitForNotifications();

    if ($limit === null) {
      if ($storedState) {
        $this->settings->set(self::SETTINGS_KEY, []);
      }
      return;
    }

    if (($state['limit'] ?? null) !== $limit) {
      $state = [
        'limit' => $limit,
        'thresholds' => [],
      ];
    }

    $count = $this->subscribersFeature->getFreshSubscribersCount();
    $stateChanged = false;

    foreach (self::THRESHOLDS as $threshold) {
      $thresholdKey = (string)$threshold;
      $thresholdCount = (int)ceil($limit * $threshold / 100);

      if ($count < $thresholdCount) {
        if (isset($state['thresholds'][$thresholdKey])) {
          unset($state['thresholds'][$thresholdKey]);
          $stateChanged = true;
        }
        continue;
      }

      if (isset($state['thresholds'][$thresholdKey]['sent_at'])) {
        continue;
      }

      if (!$this->mailer->send($threshold, $count, $limit)) {
        break;
      }

      $state['thresholds'][$thresholdKey] = [
        'sent_at' => $this->wp->currentTime('mysql', true),
        'count_at_send' => $count,
      ];
      $this->settings->set(self::SETTINGS_KEY, $state);
      $stateChanged = false;
    }

    if ($stateChanged || $this->settings->get(self::SETTINGS_KEY, []) !== $state) {
      $this->settings->set(self::SETTINGS_KEY, $state);
    }
  }

  private function normalizeState($state): array {
    if (!is_array($state)) {
      return [];
    }

    $limit = isset($state['limit']) && is_numeric($state['limit']) ? (int)$state['limit'] : null;
    $thresholds = isset($state['thresholds']) && is_array($state['thresholds']) ? $state['thresholds'] : [];

    return [
      'limit' => $limit,
      'thresholds' => $thresholds,
    ];
  }
}
