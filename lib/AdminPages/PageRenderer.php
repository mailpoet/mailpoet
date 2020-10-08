<?php

namespace MailPoet\AdminPages;

use MailPoet\Config\Renderer;
use MailPoet\Features\FeaturesController;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Tracy\DIPanel\DIPanel;
use MailPoet\WP\Notice as WPNotice;
use Tracy\Debugger;

class PageRenderer {
  /** @var Renderer */
  private $renderer;

  /** @var FeaturesController */
  private $featuresController;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $userFlags;

  public function __construct(
    Renderer $renderer,
    FeaturesController $featuresController,
    SettingsController $settings,
    UserFlagsController $userFlags
  ) {
    $this->renderer = $renderer;
    $this->featuresController = $featuresController;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
  }

  /**
   * Set common data for template and display template
   * @param string $template
   * @param array $data
   */
  public function displayPage($template, array $data = []) {
    $lastAnnouncementDate = $this->settings->get('last_announcement_date');
    $lastAnnouncementSeen = $this->userFlags->get('last_announcement_seen');
    $defaults = [
      'feature_flags' => $this->featuresController->getAllFlags(),
      'referral_id' => $this->settings->get(ReferralDetector::REFERRAL_SETTING_NAME),
      'mailpoet_api_key_state' => $this->settings->get('mta.mailpoet_api_key_state'),
      'last_announcement_seen' => $lastAnnouncementSeen,
      'feature_announcement_has_news' => (empty($lastAnnouncementSeen) || $lastAnnouncementSeen < $lastAnnouncementDate),
    ];
    try {
      if (class_exists(Debugger::class)) {
        DIPanel::init();
      }
      echo $this->renderer->render($template, $data + $defaults);
    } catch (\Exception $e) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $e->getMessage());
      $notice->displayWPNotice();
    }
  }
}
