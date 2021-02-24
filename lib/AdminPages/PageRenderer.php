<?php

namespace MailPoet\AdminPages;

use MailPoet\Config\Renderer;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\SegmentsRepository;
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

  /** @var SegmentsRepository */
  private $segmentRepository;

  public function __construct(
    Renderer $renderer,
    FeaturesController $featuresController,
    SettingsController $settings,
    UserFlagsController $userFlags,
    SegmentsRepository $segmentRepository
  ) {
    $this->renderer = $renderer;
    $this->featuresController = $featuresController;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
    $this->segmentRepository = $segmentRepository;
  }

  /**
   * Set common data for template and display template
   * @param string $template
   * @param array $data
   */
  public function displayPage($template, array $data = []) {
    $lastAnnouncementDate = $this->settings->get('last_announcement_date');
    $lastAnnouncementSeen = $this->userFlags->get('last_announcement_seen');
    $wpSegment = $this->segmentRepository->getWPUsersSegment();
    $wpSegmentState = ($wpSegment instanceof SegmentEntity) && $wpSegment->getDeletedAt() === null ?
      SegmentEntity::SEGMENT_ENABLED : SegmentEntity::SEGMENT_DISABLED;
    $defaults = [
      'feature_flags' => $this->featuresController->getAllFlags(),
      'referral_id' => $this->settings->get(ReferralDetector::REFERRAL_SETTING_NAME),
      'mailpoet_api_key_state' => $this->settings->get('mta.mailpoet_api_key_state'),
      'premium_key_state' => $this->settings->get('premium.premium_key_state'),
      'last_announcement_seen' => $lastAnnouncementSeen,
      'feature_announcement_has_news' => (empty($lastAnnouncementSeen) || $lastAnnouncementSeen < $lastAnnouncementDate),
      'wp_segment_state' => $wpSegmentState,
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
