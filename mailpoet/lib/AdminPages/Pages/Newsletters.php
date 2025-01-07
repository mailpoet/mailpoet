<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\Config\Env;
use MailPoet\Config\Menu;
use MailPoet\EmailEditor\Engine\Dependency_Check;
use MailPoet\EmailEditor\Integrations\MailPoet\DependencyNotice;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\PageLimit;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Segments\WooCommerce;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\AutocompletePostListLoader as WPPostListLoader;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;

class Newsletters {
  private PageRenderer $pageRenderer;

  private PageLimit $listingPageLimit;

  private WPFunctions $wp;

  private SettingsController $settings;

  private NewsletterTemplatesRepository $newsletterTemplatesRepository;

  private AutomaticEmails $automaticEmails;

  private WPPostListLoader $wpPostListLoader;

  private SegmentsSimpleListRepository $segmentsListRepository;

  private NewslettersRepository $newslettersRepository;

  private Bridge $bridge;

  private AuthorizedSenderDomainController $senderDomainController;

  private AuthorizedEmailsController $authorizedEmailsController;

  private UserFlagsController $userFlagsController;

  private WooCommerce $wooCommerceSegment;

  private Dependency_Check $dependencyCheck;

  private DependencyNotice $dependencyNotice;

  private CapabilitiesManager $capabilitiesManager;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    WPFunctions $wp,
    SettingsController $settings,
    NewsletterTemplatesRepository $newsletterTemplatesRepository,
    WPPostListLoader $wpPostListLoader,
    AutomaticEmails $automaticEmails,
    SegmentsSimpleListRepository $segmentsListRepository,
    NewslettersRepository $newslettersRepository,
    Bridge $bridge,
    AuthorizedSenderDomainController $senderDomainController,
    AuthorizedEmailsController $authorizedEmailsController,
    UserFlagsController $userFlagsController,
    WooCommerce $wooCommerceSegment,
    Dependency_Check $dependencyCheck,
    DependencyNotice $dependencyNotice,
    CapabilitiesManager $capabilitiesManager
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->newsletterTemplatesRepository = $newsletterTemplatesRepository;
    $this->automaticEmails = $automaticEmails;
    $this->wpPostListLoader = $wpPostListLoader;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->bridge = $bridge;
    $this->senderDomainController = $senderDomainController;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->userFlagsController = $userFlagsController;
    $this->wooCommerceSegment = $wooCommerceSegment;
    $this->dependencyCheck = $dependencyCheck;
    $this->dependencyNotice = $dependencyNotice;
    $this->capabilitiesManager = $capabilitiesManager;
  }

  public function render() {
    global $wp_roles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $data = [];

    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('newsletters');
    $includedSegmentTypes = $this->wooCommerceSegment->shouldShowWooCommerceSegment() ? [] : SegmentEntity::NON_WOO_RELATED_TYPES;
    $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts($includedSegmentTypes);
    $data['segments'] = $segments;
    $data['settings'] = $this->settings->getAll();
    $data['current_wp_user'] = $this->wp->wpGetCurrentUser()->to_array();
    $data['current_wp_user_firstname'] = $this->wp->wpGetCurrentUser()->user_firstname;
    $data['roles'] = $wp_roles->get_names(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $data['roles']['mailpoet_all'] = __('In any WordPress role', 'mailpoet');

    $dateTime = new DateTime();
    $data['current_date'] = $dateTime->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['tomorrow_date'] = $dateTime->getCurrentDateTime()->modify("+1 day")
      ->format(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $dateTime->getCurrentTime();
    $data['current_date_time'] = $dateTime->getCurrentDateTime()->format(DateTime::DEFAULT_DATE_TIME_FORMAT);
    $data['schedule_time_of_day'] = $dateTime->getTimeInterval(
      '00:00:00',
      '+15 minutes',
      96
    );
    $data['mailpoet_emails_page'] = $this->wp->adminUrl('admin.php?page=' . Menu::EMAILS_PAGE_SLUG);
    $data['show_congratulate_after_first_newsletter'] = isset($data['settings']['show_congratulate_after_first_newsletter']) ? $data['settings']['show_congratulate_after_first_newsletter'] : 'false';

    $data['is_mailpoet_update_available'] = array_key_exists(Env::$pluginPath, $this->wp->getPluginUpdates());
    $data['newsletters_count'] = $this->newslettersRepository->countBy([]);

    $data['automatic_emails'] = $this->automaticEmails->getAutomaticEmails();
    $data['woocommerce_optin_on_checkout'] = $this->settings->get('woocommerce.optin_on_checkout.enabled', false);

    $data['sent_newsletters_count'] = $this->newslettersRepository->countBy(['status' => NewsletterEntity::STATUS_SENT]);
    $data['woocommerce_transactional_email_id'] = $this->settings->get(TransactionalEmails::SETTING_EMAIL_ID);
    $detailedAnalyticsCapability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    $data['display_detailed_stats'] = isset($detailedAnalyticsCapability) && !$detailedAnalyticsCapability->isRestricted;
    $data['newsletters_templates_recently_sent_count'] = $this->newsletterTemplatesRepository->getRecentlySentCount();

    $data['product_categories'] = $this->wpPostListLoader->getWooCommerceCategories();

    $data['products'] = $this->wpPostListLoader->getProducts();

    $data['authorized_emails'] = [];
    $data['verified_sender_domains'] = [];
    $data['partially_verified_sender_domains'] = [];
    $data['all_sender_domains'] = [];
    $data['sender_restrictions'] = [];

    if ($this->bridge->isMailpoetSendingServiceEnabled()) {
      $data['authorized_emails'] = $this->authorizedEmailsController->getAuthorizedEmailAddresses();
      $data['verified_sender_domains'] = $this->senderDomainController->getFullyVerifiedSenderDomains(true);
      $data['partially_verified_sender_domains'] = $this->senderDomainController->getPartiallyVerifiedSenderDomains(true);
      $data['all_sender_domains'] = $this->senderDomainController->getAllSenderDomains();
      $data['sender_restrictions'] = [
        'lowerLimit' => AuthorizedSenderDomainController::LOWER_LIMIT,
        'isAuthorizedDomainRequiredForNewCampaigns' => $this->senderDomainController->isAuthorizedDomainRequiredForNewCampaigns(),
        'campaignTypes' => NewsletterEntity::CAMPAIGN_TYPES,
      ];
    }

    $data['corrupt_newsletters'] = $this->getCorruptNewsletterSubjects();

    $data['legacy_automatic_emails_count'] = $this->newslettersRepository->countBy([
      'type' => [NewsletterEntity::TYPE_WELCOME, NewsletterEntity::TYPE_AUTOMATIC],
    ]);

    $data['legacy_automatic_emails_notice_dismissed'] = (bool)$this->userFlagsController->get('legacy_automatic_emails_notice_dismissed');

    $data['block_email_editor_enabled'] = $this->dependencyCheck->are_dependencies_met(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->dependencyNotice->displayMessageIfNeeded();
    $this->pageRenderer->displayPage('newsletters.html', $data);
  }

  private function getCorruptNewsletterSubjects(): array {
    return array_map(function ($newsletter) {
      return [
        'id' => $newsletter->getId(),
        'subject' => $newsletter->getSubject(),
      ];
    }, $this->newslettersRepository->getCorruptNewsletters());
  }
}
