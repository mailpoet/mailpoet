<?php

namespace MailPoet\AdminPages;

use MailPoet\Config\Installer;
use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\SubscribersCountCacheRecalculation;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Tracy\DIPanel\DIPanel;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;
use Tracy\Debugger;

class PageRenderer {
  /** @var Bridge */
  private $bridge;

  /** @var Renderer */
  private $renderer;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var FeaturesController */
  private $featuresController;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersCountCacheRecalculation */
  private $subscribersCountCacheRecalculation;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Bridge $bridge,
    Renderer $renderer,
    ServicesChecker $servicesChecker,
    FeaturesController $featuresController,
    SettingsController $settings,
    UserFlagsController $userFlags,
    SegmentsRepository $segmentRepository,
    SubscribersCountCacheRecalculation $subscribersCountCacheRecalculation,
    SubscribersFeature $subscribersFeature,
    WPFunctions $wp
  ) {
    $this->bridge = $bridge;
    $this->renderer = $renderer;
    $this->servicesChecker = $servicesChecker;
    $this->featuresController = $featuresController;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
    $this->segmentRepository = $segmentRepository;
    $this->subscribersCountCacheRecalculation = $subscribersCountCacheRecalculation;
    $this->subscribersFeature = $subscribersFeature;
    $this->wp = $wp;
  }

  /**
   * Set common data for template and display template
   * @param string $template
   * @param array $data
   */
  public function displayPage($template, array $data = []) {
    $installer = new Installer(Installer::PREMIUM_PLUGIN_SLUG);
    $pluginInformation = $installer->retrievePluginInformation();

    $lastAnnouncementDate = $this->settings->get('last_announcement_date');
    $lastAnnouncementSeen = $this->userFlags->get('last_announcement_seen');
    $wpSegment = $this->segmentRepository->getWPUsersSegment();
    $wpSegmentState = ($wpSegment instanceof SegmentEntity) && $wpSegment->getDeletedAt() === null ?
      SegmentEntity::SEGMENT_ENABLED : SegmentEntity::SEGMENT_DISABLED;

    $defaults = [
      'feature_flags' => $this->featuresController->getAllFlags(),
      'referral_id' => $this->settings->get(ReferralDetector::REFERRAL_SETTING_NAME),
      'mailpoet_api_key_state' => $this->settings->get('mta.mailpoet_api_key_state'),
      'mta_method' => $this->settings->get('mta.method'),
      'premium_key_state' => $this->settings->get('premium.premium_key_state'),
      'last_announcement_seen' => $lastAnnouncementSeen,
      'feature_announcement_has_news' => (empty($lastAnnouncementSeen) || $lastAnnouncementSeen < $lastAnnouncementDate),
      'wp_segment_state' => $wpSegmentState,

      // Premium & plan upgrade info
      'current_wp_user_email' => $this->wp->wpGetCurrentUser()->user_email,
      'link_premium' => $this->wp->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-upgrade'),
      'premium_plugin_installed' => Installer::isPluginInstalled(Installer::PREMIUM_PLUGIN_SLUG),
      'premium_plugin_active' => $this->servicesChecker->isPremiumPluginActive(),
      'premium_plugin_download_url' => $pluginInformation->download_link ?? null, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'premium_plugin_activation_url' => $installer->generatePluginActivationUrl(Installer::PREMIUM_PLUGIN_PATH),
      'has_valid_api_key' => $this->subscribersFeature->hasValidApiKey(),
      'has_valid_premium_key' => $this->subscribersFeature->hasValidPremiumKey(),
      'has_premium_support' => $this->subscribersFeature->hasPremiumSupport(),
      'has_mss_key_specified' => Bridge::isMSSKeySpecified(),
      'mss_key_invalid' => $this->servicesChecker->isMailPoetAPIKeyValid() === false,
      'mss_key_pending_approval' => $this->servicesChecker->isMailPoetAPIKeyPendingApproval(),
      'mss_active' => $this->bridge->isMailpoetSendingServiceEnabled(),
      'plugin_partial_key' => $this->servicesChecker->generatePartialApiKey(),
      'subscribers_limit' => $this->subscribersFeature->getSubscribersLimit(),
      'subscribers_limit_reached' => $this->subscribersFeature->check(),
      'email_volume_limit' => $this->subscribersFeature->getEmailVolumeLimit(),
      'email_volume_limit_reached' => $this->subscribersFeature->checkEmailVolumeLimitIsReached(),
    ];

    if (!$defaults['premium_plugin_active']) {
      $defaults['free_premium_subscribers_limit'] = License::FREE_PREMIUM_SUBSCRIBERS_LIMIT;
    }

    try {
      if (
        class_exists(Debugger::class)
        && class_exists(DIPanel::class)
      ) {
        DIPanel::init();
      }
      if (is_admin() && $this->subscribersCountCacheRecalculation->shouldBeScheduled()) {
        $this->subscribersCountCacheRecalculation->schedule();
      }

      // We are in control of the template and the data can be considered safe at this point
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter
      echo $this->renderer->render($template, $data + $defaults);
    } catch (\Exception $e) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $e->getMessage());
      $notice->displayWPNotice();
    }
  }
}
