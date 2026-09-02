<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\Notices;

use MailPoet\Config\AccessControl;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class PermanentNotices {

  /** @var WPFunctions */
  private $wp;

  /** @var PHPVersionWarnings */
  private $phpVersionWarnings;

  /** @var AfterMigrationNotice */
  private $afterMigrationNotice;

  /** @var UnauthorizedEmailNotice */
  private $unauthorizedEmailsNotice;

  /** @var UnauthorizedEmailInNewslettersNotice */
  private $unauthorizedEmailsInNewslettersNotice;

  /** @var InactiveSubscribersNotice */
  private $inactiveSubscribersNotice;

  /** @var BlackFridayNotice */
  private $blackFridayNotice;

  /** @var HeadersAlreadySentNotice */
  private $headersAlreadySentNotice;

  /** @var EmailWithInvalidSegmentNotice */
  private $emailWithInvalidListNotice;

  /** @var ChangedTrackingNotice */
  private $changedTrackingNotice;

  /** @var DeprecatedFilterNotice */
  private $deprecatedFilterNotice;

  /** @var DisabledMailFunctionNotice */
  private $disabledMailFunctionNotice;

  /** @var DisabledWPCronNotice */
  private $disabledWPCronNotice;

  /** @var PendingApprovalNotice */
  private $pendingApprovalNotice;

  /** @var WooCommerceVersionWarning */
  private $woocommerceVersionWarning;

  /** @var PremiumFeaturesAvailableNotice */
  private $premiumFeaturesAvailableNotice;

  /** @var SenderDomainAuthenticationNotices */
  private $senderDomainAuthenticationNotices;

  /** @var WordPressPlaygroundNotice */
  private $wordPressPlaygroundNotice;

  /** @var DatabaseEngineNotice */
  private $databaseEngineNotice;

  /** @var SendingQueueBodyCleanupNotice */
  private $sendingQueueBodyCleanupNotice;

  /** @var StuckPostNotificationNotice */
  private $stuckPostNotificationNotice;

  public function __construct(
    WPFunctions $wp,
    CronHelper $cronHelper,
    EntityManager $entityManager,
    TrackingConfig $trackingConfig,
    SubscribersRepository $subscribersRepository,
    SettingsController $settings,
    SubscribersFeature $subscribersFeature,
    ServicesChecker $serviceChecker,
    MailerFactory $mailerFactory,
    SenderDomainAuthenticationNotices $senderDomainAuthenticationNotices,
    AuthorizedSenderDomainController $senderDomainController,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->phpVersionWarnings = new PHPVersionWarnings();
    $this->afterMigrationNotice = new AfterMigrationNotice();
    $this->unauthorizedEmailsNotice = new UnauthorizedEmailNotice($wp, $settings, $senderDomainController);
    $this->unauthorizedEmailsInNewslettersNotice = new UnauthorizedEmailInNewslettersNotice($settings, $wp, $senderDomainController);
    $this->inactiveSubscribersNotice = new InactiveSubscribersNotice($settings, $subscribersRepository, $wp);
    $this->blackFridayNotice = new BlackFridayNotice($serviceChecker, $subscribersFeature);
    $this->headersAlreadySentNotice = new HeadersAlreadySentNotice($settings, $trackingConfig, $wp);
    $this->emailWithInvalidListNotice = new EmailWithInvalidSegmentNotice($wp);
    $this->changedTrackingNotice = new ChangedTrackingNotice($wp);
    $this->deprecatedFilterNotice = new DeprecatedFilterNotice($wp);
    $this->disabledMailFunctionNotice = new DisabledMailFunctionNotice($wp, $settings, $subscribersFeature, $mailerFactory);
    $this->disabledWPCronNotice = new DisabledWPCronNotice($wp, $cronHelper, $settings);
    $this->pendingApprovalNotice = new PendingApprovalNotice($settings);
    $this->woocommerceVersionWarning = new WooCommerceVersionWarning($wp);
    $this->premiumFeaturesAvailableNotice = new PremiumFeaturesAvailableNotice($subscribersFeature, $serviceChecker, $wp);
    $this->databaseEngineNotice = new DatabaseEngineNotice($wp, $entityManager);
    $this->wordPressPlaygroundNotice = new WordPressPlaygroundNotice();
    $this->sendingQueueBodyCleanupNotice = new SendingQueueBodyCleanupNotice($settings, $wp);
    $this->stuckPostNotificationNotice = new StuckPostNotificationNotice($wp, $newslettersRepository);
    $this->senderDomainAuthenticationNotices = $senderDomainAuthenticationNotices;
  }

  public function init() {
    $excludeSetupWizard = [
      'mailpoet-welcome-wizard',
      'mailpoet-woocommerce-setup',
      'mailpoet-landingpage',
    ];
    $this->wp->addAction('wp_ajax_dismissed_notice_handler', [
      $this,
      'ajaxDismissNoticeHandler',
    ]);

    $this->phpVersionWarnings->init(
      phpversion(),
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->afterMigrationNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->unauthorizedEmailsNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->unauthorizedEmailsInNewslettersNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->inactiveSubscribersNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->blackFridayNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->headersAlreadySentNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->emailWithInvalidListNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->changedTrackingNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->deprecatedFilterNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->disabledMailFunctionNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->disabledWPCronNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->pendingApprovalNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->woocommerceVersionWarning->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->premiumFeaturesAvailableNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->databaseEngineNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->wordPressPlaygroundNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->sendingQueueBodyCleanupNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $this->stuckPostNotificationNotice->init(
      Menu::isOnMailPoetAdminPage($excludeSetupWizard)
    );
    $excludeDomainAuthenticationNotices = [
      'mailpoet-settings',
      'mailpoet-newsletter-editor',
      ...$excludeSetupWizard,
    ];
    $this->senderDomainAuthenticationNotices->init(
      Menu::isOnMailPoetAdminPage($excludeDomainAuthenticationNotices)
    );
  }

  public function ajaxDismissNoticeHandler() {
    if (!$this->wp->currentUserCan(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN)) {
      $this->wp->wpDie(
        esc_html__('You do not have permission to perform this action.', 'mailpoet'),
        esc_html__('Unauthorized', 'mailpoet'),
        ['response' => 403]
      );
      return;
    }

    $nonce = isset($_POST['nonce']) && is_string($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$this->wp->wpVerifyNonce($nonce, Notice::DISMISS_NONCE_ACTION)) {
      $this->wp->wpDie(
        esc_html__('Security check failed.', 'mailpoet'),
        esc_html__('Error', 'mailpoet'),
        ['response' => 403]
      );
      return;
    }

    if (!isset($_POST['type']) || !is_string($_POST['type'])) return;
    switch (sanitize_text_field(wp_unslash($_POST['type']))) {
      case (PHPVersionWarnings::OPTION_NAME):
        $this->phpVersionWarnings->disable();
        break;
      case (AfterMigrationNotice::OPTION_NAME):
        $this->afterMigrationNotice->disable();
        break;
      case (BlackFridayNotice::OPTION_NAME):
        $this->blackFridayNotice->disable();
        break;
      case (HeadersAlreadySentNotice::OPTION_NAME):
        $this->headersAlreadySentNotice->disable();
        break;
      case (InactiveSubscribersNotice::OPTION_NAME):
        $this->inactiveSubscribersNotice->disable();
        break;
      case (EmailWithInvalidSegmentNotice::OPTION_NAME):
        $this->emailWithInvalidListNotice->disable();
        break;
      case (ChangedTrackingNotice::OPTION_NAME):
        $this->changedTrackingNotice->disable();
        break;
      case (DisabledWPCronNotice::OPTION_NAME):
        $this->disabledWPCronNotice->disable();
        break;
      case (DeprecatedFilterNotice::OPTION_NAME):
        $this->deprecatedFilterNotice->disable();
        break;
      case (WooCommerceVersionWarning::OPTION_NAME):
        $this->woocommerceVersionWarning->disable();
        break;
      case (DatabaseEngineNotice::OPTION_NAME):
        $this->databaseEngineNotice->disable();
        break;
      case (PremiumFeaturesAvailableNotice::OPTION_NAME):
        $this->premiumFeaturesAvailableNotice->disable();
        break;
      case (SendingQueueBodyCleanupNotice::OPTION_NAME):
        $this->sendingQueueBodyCleanupNotice->disable();
        break;
      case (StuckPostNotificationNotice::OPTION_NAME):
        $this->stuckPostNotificationNotice->disable();
        break;
    }
  }
}
