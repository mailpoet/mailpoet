<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\AssetsController;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\Pages as SettingsPages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Statistics\UnsubscribeReasonTracker;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Headers;
use MailPoet\Util\Helpers;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Pages {
  const DEMO_EMAIL = 'demo@mailpoet.com';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_CONFIRM_UNSUBSCRIBE = 'confirm_unsubscribe';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  const ACTION_RE_ENGAGEMENT = 're_engagement';
  const ACTION_TRACKING_OPT_OUT = 'tracking_opt_out';

  private $action;
  private $data;
  private $subscriber;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationSender;

  /** @var WPFunctions */
  private $wp;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var AssetsController */
  private $assetsController;

  /** @var TemplateRenderer */
  private $templateRenderer;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  /** @var ManageSubscriptionFormRenderer */
  private $manageSubscriptionFormRenderer;

  /** @var SubscriberHandler */
  private $subscriberHandler;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var EntityManager */
  private $entityManager;

  /** @var SubscriberSaveController */
  private $subscriberSaveController;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /*** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /*** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /*** @var SettingsController */
  private $settings;

  /*** @var UnsubscribeReasonTracker */
  private $unsubscribeReasonTracker;

  /*** @var Request */
  private $request;

  public function __construct(
    NewSubscriberNotificationMailer $newSubscriberNotificationSender,
    WPFunctions $wp,
    WelcomeScheduler $welcomeScheduler,
    LinkTokens $linkTokens,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    AssetsController $assetsController,
    TemplateRenderer $templateRenderer,
    Unsubscribes $unsubscribesTracker,
    ManageSubscriptionFormRenderer $manageSubscriptionFormRenderer,
    SubscriberHandler $subscriberHandler,
    SubscribersRepository $subscribersRepository,
    TrackingConfig $trackingConfig,
    EntityManager $entityManager,
    SubscriberSaveController $subscriberSaveController,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    NewsletterLinkRepository $newsletterLinkRepository,
    StatisticsClicksRepository $statisticsClicksRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    SettingsController $settings,
    UnsubscribeReasonTracker $unsubscribeReasonTracker,
    Request $request
  ) {
    $this->wp = $wp;
    $this->newSubscriberNotificationSender = $newSubscriberNotificationSender;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->linkTokens = $linkTokens;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->assetsController = $assetsController;
    $this->templateRenderer = $templateRenderer;
    $this->unsubscribesTracker = $unsubscribesTracker;
    $this->manageSubscriptionFormRenderer = $manageSubscriptionFormRenderer;
    $this->subscriberHandler = $subscriberHandler;
    $this->subscribersRepository = $subscribersRepository;
    $this->trackingConfig = $trackingConfig;
    $this->entityManager = $entityManager;
    $this->subscriberSaveController = $subscriberSaveController;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->settings = $settings;
    $this->unsubscribeReasonTracker = $unsubscribeReasonTracker;
    $this->request = $request;
  }

  public function init($action = false, $data = [], $initShortcodes = false, $initPageFilters = false) {
    $this->action = $action;
    $this->data = $data;
    $this->subscriber = $this->getSubscriber();
    if ($initPageFilters) $this->initPageFilters();
    if ($initShortcodes) $this->initShortcodes();
    return $this;
  }

  public function isInitialized(): bool {
    return $this->data !== null;
  }

  private function isPreview() {
    return (array_key_exists('preview', $_GET) || array_key_exists('preview', $this->data));
  }

  public function initPageFilters() {
    $this->wp->addFilter('wp_title', [$this, 'setWindowTitle'], 10, 3);
    $this->wp->addFilter('document_title_parts', [$this, 'setWindowTitleParts'], 10, 1);
    $this->wp->addFilter('the_title', [$this, 'setPageTitle'], 10, 1);
    $this->wp->addFilter('the_content', [$this, 'setPageContent'], 10, 1);
    $this->wp->removeAction('wp_head', 'noindex', 1);
    $this->wp->addAction('wp_head', [$this, 'setMetaRobots'], 1);
    $this->setSubscriptionPageHeaders();
  }

  private function setSubscriptionPageHeaders(): void {
    if ($this->wp->headersSent()) {
      return;
    }
    // Prevent the rendered subscriber email and other personal data on
    // subscription pages from leaking via outbound link referrers, and stop
    // archivers that honor HTTP headers but not <meta name="robots">.
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
  }

  public function initShortcodes() {
    $this->wp->addShortcode('mailpoet_manage', [$this, 'getManageLink']);
    $this->wp->addShortcode('mailpoet_manage_subscription', [$this, 'getManageContent']);
  }

  /**
   * @return SubscriberEntity|null
   */
  private function getSubscriber() {
    if (!is_null($this->subscriber)) {
      return $this->subscriber;
    }

    $token = (isset($this->data['token'])) ? $this->data['token'] : null;
    $email = (isset($this->data['email'])) ? $this->data['email'] : null;

    if (!$email) {
      return null;
    }

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    return ($subscriber instanceof SubscriberEntity && $this->linkTokens->verifyToken($subscriber, $token)) ? $subscriber : null;
  }

  public function confirm() {
    $this->subscriber = $this->getSubscriber();
    if ($this->subscriber === null) {
      return false;
    }

    $subscriberData = json_decode((string)$this->subscriber->getUnconfirmedData(), true);
    $originalStatus = $this->subscriber->getStatus();
    $confirmationCompleted = $originalStatus !== SubscriberEntity::STATUS_SUBSCRIBED || $subscriberData !== null;

    $this->subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscriber->setConfirmedIp(Helpers::getIP());
    $this->subscriber->setConfirmedAt(Carbon::now()->millisecond(0));
    $this->subscriber->setLastSubscribedAt(Carbon::now()->millisecond(0));
    $this->subscriber->setUnconfirmedData(null);

    try {
      $this->entityManager->persist($this->subscriber);
      $this->entityManager->flush();

      // start subscriber tracking
      $this->subscriberHandler->identifyByEmail($this->subscriber->getEmail());
    } catch (\Exception $e) {
      return false;
    }

    // Schedule welcome emails
    $subscriberSegments = $this->subscriber->getSegments()->toArray();
    if ($subscriberSegments) {
      $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
        $this->subscriber->getId(),
        array_map(function (SegmentEntity $segment) {
          return $segment->getId();
        }, $subscriberSegments)
      );
    }

    // when global status changes to subscribed, fire subscribed hook for all subscribed segments
    $segments = $this->subscriber->getSubscriberSegments();
    if ($originalStatus !== SubscriberEntity::STATUS_SUBSCRIBED) {
      foreach ($segments as $subscriberSegment) {
        if ($subscriberSegment->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
          $this->wp->doAction('mailpoet_segment_subscribed', $subscriberSegment);
        }
      }
    }

    // Send new subscriber notification only when status changes to subscribed or there are unconfirmed data to avoid spamming
    if ($confirmationCompleted) {
      $this->newSubscriberNotificationSender->send($this->subscriber, $subscriberSegments);
    }

    // Update subscriber from stored data after confirmation
    if (!empty($subscriberData)) {
      $this->subscriberSaveController->createOrUpdate((array)$subscriberData, $this->subscriber);
      $this->subscriberSaveController->updateCustomFields((array)$subscriberData, $this->subscriber);
    }

    if ($confirmationCompleted) {
      $this->wp->doAction('mailpoet_subscription_confirmed', $this->subscriber);
    }
  }

  public function unsubscribe(string $method): void {
    if (
      !$this->isPreview()
      && (!is_null($this->subscriber))
      && ($this->subscriber->getStatus() !== SubscriberEntity::STATUS_UNSUBSCRIBED)
    ) {
      $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
      if ($queueId !== null) {
        if ($this->trackingConfig->isEmailTrackingEnabled() && $method === StatisticsUnsubscribeEntity::METHOD_ONE_CLICK) {
          /**
           * With 1-click method, redirect shouldn't happen that's why the click state should be directly recorded
           */
          $this->updateClickStatistics($queueId);
        }
      }

      $this->unsubscribesTracker->track(
        (int)$this->subscriber->getId(),
        StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
        $queueId,
        null,
        $method
      );
      $this->subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
      $this->subscribersRepository->persist($this->subscriber);
      $this->subscribersRepository->flush();

      $this->subscriberSegmentRepository->unsubscribeFromSegments($this->subscriber);
    }
  }

  public function trackingOptOut(string $method, string $copy): void {
    if (
      !$this->isPreview()
      && (!is_null($this->subscriber))
      && ($this->subscriber->getTrackingConsent() !== SubscriberEntity::TRACKING_CONSENT_DENIED)
    ) {
      $this->subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, $method, $copy);
      $this->subscribersRepository->persist($this->subscriber);
      $this->subscribersRepository->flush();
    }
  }

  /**
   * The wording the subscriber is shown before choosing. Kept in one place so
   * the copy we store as proof is exactly the copy we rendered.
   */
  public static function getTrackingOptOutConsentCopy(): string {
    return __('If you confirm, tracking of email opens and link clicks will stop. This is separate from unsubscribing: you will keep receiving emails.', 'mailpoet');
  }

  public function isSubscriberUnsubscribed(): bool {
    return $this->subscriber instanceof SubscriberEntity
      && $this->subscriber->getStatus() === SubscriberEntity::STATUS_UNSUBSCRIBED;
  }

  public function setMetaRobots() {
    echo '<meta name="robots" content="noindex,nofollow">';
  }

  public function setPageTitle($pageTitle = '') {
    global $post;

    if (
      (!isset($post))
      ||
      ($post->post_title !== SettingsPages::PAGE_TITLE && $post->post_title !== __('MailPoet Page', 'mailpoet')) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ||
      ($pageTitle !== $this->wp->singlePostTitle('', false))
    ) {
      // when it's a custom page, just return the original page title
      return $pageTitle;
    } elseif ($this->isPreview() === false && $this->subscriber === null) {
      return __("Hmmm... we don't have a record of you.", 'mailpoet');
    } else {
      // when it's our own page, generate page title based on requested action
      switch ($this->action) {
        case self::ACTION_CONFIRM:
          return $this->getConfirmTitle();

        case self::ACTION_CONFIRM_UNSUBSCRIBE:
          return $this->getConfirmUnsubscribeTitle();

        case self::ACTION_MANAGE:
          return $this->getManageTitle();

        case self::ACTION_UNSUBSCRIBE:
          return $this->getUnsubscribeTitle();

        case self::ACTION_RE_ENGAGEMENT:
          return $this->getReEngagementTitle();

        case self::ACTION_TRACKING_OPT_OUT:
          return $this->getTrackingOptOutTitle();
      }
    }
  }

  public function setPageContent($pageContent = '[mailpoet_page]') {
    if ($this->isPreview() === false && $this->subscriber === null) {
      return __("Your email address doesn't appear in our lists anymore. Sign up again or contact us if this appears to be a mistake.", 'mailpoet');
    }

    $this->assetsController->setupFrontEndDependencies();

    if (strpos($pageContent, '[mailpoet_page]') !== false) {
      $content = '';

      switch ($this->action) {
        case self::ACTION_CONFIRM:
          $content = $this->getConfirmContent();
          break;
        case self::ACTION_CONFIRM_UNSUBSCRIBE:
          $content = $this->getConfirmUnsubscribeContent();
          break;
        case self::ACTION_MANAGE:
          $content = $this->getManageContent();
          break;
        case self::ACTION_UNSUBSCRIBE:
          $content = $this->getUnsubscribeContent();
          break;
        case self::ACTION_RE_ENGAGEMENT:
          $content = $this->getReEngagementContent();
          break;
        case self::ACTION_TRACKING_OPT_OUT:
          $content = $this->getTrackingOptOutContent();
          break;
      }
      return str_replace('[mailpoet_page]', trim($content), $pageContent);
    } else {
      return $pageContent;
    }
  }

  public function setWindowTitle($title, $separator = '', $separatorLocation = 'right') {
    // If no separator is provided, just modify the entire title
    if (empty($separator)) {
      return $this->setPageTitle($title);
    }
    $titleParts = explode(" $separator ", $title);
    if (!is_array($titleParts)) {
      return $title;
    }
    if ($separatorLocation === 'right') {
      // first part
      $titleParts[0] = $this->setPageTitle($titleParts[0]);
    } else {
      // last part
      $lastIndex = count($titleParts) - 1;
      $titleParts[$lastIndex] = $this->setPageTitle($titleParts[$lastIndex]);
    }
    return implode(" $separator ", $titleParts);
  }

  public function setWindowTitleParts($meta = []) {
    $meta['title'] = $this->setPageTitle($meta['title']);
    return $meta;
  }

  private function getConfirmTitle(): string {
    $wpSiteTitle = $this->wp->getBloginfo('name');

    if (empty($wpSiteTitle)) {
      $title = __("You are now subscribed!", 'mailpoet');
    } else {
      $title = sprintf(
        // translators: %s is the website title or website name.
        __("You have subscribed to %s", 'mailpoet'),
        $wpSiteTitle
      );
    }

    return $title;
  }

  private function getManageTitle() {
    if ($this->isPreview() || $this->subscriber !== null) {
      return __("Manage your subscription", 'mailpoet');
    }
  }

  private function getUnsubscribeTitle() {
    if ($this->isPreview() || $this->subscriber !== null) {
      return __("You are now unsubscribed.", 'mailpoet');
    }
  }

  private function getReEngagementTitle() {
    if ($this->isPreview() || $this->subscriber !== null) {
      return __('Thank you for letting us know!', 'mailpoet');
    }
  }

  private function getTrackingOptOutTitle() {
    if ($this->isPreview() || $this->subscriber !== null) {
      if ($this->subscriber !== null && $this->subscriber->getTrackingConsent() === SubscriberEntity::TRACKING_CONSENT_DENIED) {
        return __('You have opted out of email activity tracking.', 'mailpoet');
      }
      return __('Opt out of email activity tracking', 'mailpoet');
    }
  }

  private function getConfirmUnsubscribeTitle() {
    if ($this->isPreview() || $this->subscriber !== null) {
      return __('Confirm you want to unsubscribe', 'mailpoet');
    }
  }

  private function getConfirmContent() {
    if ($this->isPreview() || $this->subscriber !== null) {
      return __("Yup, we've added you to our email list. You'll hear from us shortly.", 'mailpoet');
    }
  }

  public function getManageContent() {
    if ($this->isPreview()) {
      $subscriber = new SubscriberEntity();
      $previewEmail = $this->wp->applyFilters('mailpoet_manage_subscription_preview_subscriber_email', self::DEMO_EMAIL);
      $subscriber->setEmail(is_string($previewEmail) && $previewEmail !== '' ? $previewEmail : self::DEMO_EMAIL);
      $subscriber->setFirstName('John');
      $subscriber->setLastName('Doe');
      $subscriber->setLinkToken('bfd0889dbc7f081e171fa0cee7401df2');
    } else if ($this->subscriber !== null) {
      $subscriber = $this->subscriber;
    } else if ($this->wp->getCurrentUserId() && $this->subscribersRepository->findOneBy(['wpUserId' => $this->wp->getCurrentUserId()])) {
      $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $this->wp->getCurrentUserId()]);
    } else {
      return __('Subscription management form is only available to mailing lists subscribers.', 'mailpoet');
    }

    // Read+absint sanitizes the values; phpcs can't see that across the conditional.
    $errorParam = isset($_GET['error']) && is_scalar($_GET['error']) ? wp_unslash($_GET['error']) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $successParam = isset($_GET['success']) && is_scalar($_GET['success']) ? wp_unslash($_GET['success']) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if (is_scalar($errorParam) && absint($errorParam)) {
      $formStatus = ManageSubscriptionFormRenderer::FORM_STATE_ERROR;
    } elseif (is_scalar($successParam) && absint($successParam)) {
      $formStatus = ManageSubscriptionFormRenderer::FORM_STATE_SUCCESS;
    } else {
      $formStatus = ManageSubscriptionFormRenderer::FORM_STATE_NOT_SUBMITTED;
    }

    // The manage form embeds the subscriber's email and link token in the page
    // body. On an ordinary post/page (block or [mailpoet_manage_subscription]
    // shortcode) that markup is otherwise cacheable, so a full-page cache could
    // serve one subscriber's token to another visitor. Preview renders only
    // demo data, so they don't need this.
    if (!$this->isPreview()) {
      Headers::preventPageCaching();
    }

    return $this->wp->applyFilters(
      'mailpoet_manage_subscription_page',
      $this->manageSubscriptionFormRenderer->renderForm($subscriber, $formStatus)
    );
  }

  private function getUnsubscribeContent() {
    $content = '';
    if ($this->isPreview() || $this->subscriber !== null) {
      $content .= '<p class="mailpoet_unsubscribed_content">' . __('Accidentally unsubscribed?', 'mailpoet') . ' <strong>';
      $content .= '[mailpoet_manage]';
      $content .= '</strong></p>';
    }
    if ($this->shouldRenderUnsubscribeReasonSurvey()) {
      if ($this->isUnsubscribeReasonSaved()) {
        $content .= '<p class="mailpoet_unsubscribe_reason_success">' . __('Thank you for letting us know why you unsubscribed.', 'mailpoet') . '</p>';
      } else {
        $content .= $this->renderUnsubscribeReasonSurvey();
      }
    }
    return $content;
  }

  public function saveUnsubscribeReason(string $reason, ?string $reasonText): bool {
    if (
      $this->isPreview()
      || !$this->settings->isSettingEnabled('subscription.unsubscribe_survey.enabled')
      || !($this->subscriber instanceof SubscriberEntity)
      || $this->subscriber->getStatus() !== SubscriberEntity::STATUS_UNSUBSCRIBED
    ) {
      return false;
    }

    $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
    $result = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $queueId,
      $reason,
      $reasonText,
      $this->settings->isSettingEnabled('subscription.unsubscribe_survey.allow_other_text')
    );

    return $result instanceof StatisticsUnsubscribeEntity;
  }

  public function getUnsubscribeReasonRedirectUrl(bool $saved): string {
    $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
    $url = $this->subscriber instanceof SubscriberEntity
      ? $this->subscriptionUrlFactory->getUnsubscribeUrl($this->subscriber, $queueId)
      : $this->wp->homeUrl();

    if ($saved) {
      $url = $this->wp->addQueryArg('unsubscribe_reason_saved', 1, $url);
    }
    return $url;
  }

  private function shouldRenderUnsubscribeReasonSurvey(): bool {
    if (
      $this->isPreview()
      || !$this->settings->isSettingEnabled('subscription.unsubscribe_survey.enabled')
      || !($this->subscriber instanceof SubscriberEntity)
      || $this->subscriber->getStatus() !== SubscriberEntity::STATUS_UNSUBSCRIBED
    ) {
      return false;
    }

    $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
    return $this->unsubscribeReasonTracker->findTargetUnsubscribe($this->subscriber, $queueId) instanceof StatisticsUnsubscribeEntity;
  }

  private function renderUnsubscribeReasonSurvey(): string {
    $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
    $allowOtherText = $this->settings->isSettingEnabled('subscription.unsubscribe_survey.allow_other_text');
    $reasons = $this->unsubscribeReasonTracker->getReasonLabels();

    return $this->templateRenderer->render('subscription/unsubscribe_reason.html', [
      'actionUrl' => $this->subscriptionUrlFactory->getUnsubscribeReasonUrl($this->subscriber, $queueId),
      'allowOtherText' => $allowOtherText,
      'reasons' => $reasons,
      'otherReason' => StatisticsUnsubscribeEntity::REASON_OTHER,
      'nonce' => $this->wp->wpCreateNonce('mailpoet_unsubscribe_reason'),
    ]);
  }

  private function isUnsubscribeReasonSaved(): bool {
    $value = $this->request->getStringParam('unsubscribe_reason_saved');
    return $value !== null && $this->wp->absint($value) === 1;
  }

  private function getReEngagementContent() {
    $content = '';
    if ($this->isPreview() || $this->subscriber !== null) {
      $content .= '<p>' . __('We appreciate your continued interest in our updates. Expect to hear from us again soon!', 'mailpoet') . '</p>';
    }
    return $content;
  }

  private function getTrackingOptOutContent() {
    if (!$this->isPreview() && $this->subscriber === null) {
      return '';
    }
    if ($this->subscriber !== null && $this->subscriber->getTrackingConsent() === SubscriberEntity::TRACKING_CONSENT_DENIED) {
      return '<p class="mailpoet_tracking_opt_out_content">'
        . __('Tracking of email opens and link clicks is now off. You will keep receiving emails as usual.', 'mailpoet')
        . ' <strong>[mailpoet_manage]</strong></p>';
    }
    $optOutUrl = $this->subscriptionUrlFactory->getTrackingOptOutUrl($this->subscriber);
    return '<p>' . self::getTrackingOptOutConsentCopy() . '</p>'
      . '<form method="post" action="' . esc_attr((string)$optOutUrl) . '" class="mailpoet_tracking_opt_out_form">'
      . '<input type="hidden" name="_wpnonce" value="' . esc_attr($this->wp->wpCreateNonce('mailpoet_tracking_opt_out')) . '" />'
      . '<input type="submit" value="' . esc_attr__('Stop tracking my activity', 'mailpoet') . '" />'
      . '</form>';
  }

  private function getConfirmUnsubscribeContent() {
    if (!$this->isPreview() && $this->subscriber === null) {
      return '';
    }
    $queueId = isset($this->data['queueId']) ? (int)$this->data['queueId'] : null;
    $unsubscribeUrl = $this->subscriptionUrlFactory->getUnsubscribeUrl($this->subscriber, $queueId);
    $unsubscribeUrl = $unsubscribeUrl . (parse_url($unsubscribeUrl, PHP_URL_QUERY) ? '&' : '?') . 'request_method=POST';
    $templateData = [
      'unsubscribeUrl' => $unsubscribeUrl,
      'subscriberEmail' => $this->subscriber instanceof SubscriberEntity ? $this->subscriber->getEmail() : null,
    ];
    return $this->wp->applyFilters(
      'mailpoet_unsubscribe_confirmation_page',
      $this->templateRenderer->render('subscription/confirm_unsubscribe.html', $templateData),
      $this->addTypeParamToUnsubscribeUrl($unsubscribeUrl)
    );
  }

  public function getManageLink($params) {
    $subscriber = $this->subscriber;
    if (!$subscriber && $this->subscribersRepository->findOneBy(['wpUserId' => $this->wp->getCurrentUserId()])) {
      $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $this->wp->getCurrentUserId()]);
    }
    if (!$subscriber instanceof SubscriberEntity) return __('Link to subscription management page is only available to mailing lists subscribers.', 'mailpoet');

    // get label or display default label
    $text = isset($params['text'])
      ? htmlspecialchars($params['text'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401)
      : __('Manage your subscription', 'mailpoet');

    return '<a href="' . $this->subscriptionUrlFactory->getManageUrl($subscriber) . '">' . $text . '</a>';
  }

  private function updateClickStatistics(int $queueId): void {
    $queue = $this->sendingQueuesRepository->findOneById($queueId);
    if ($queue) {
      $newsletter = $queue->getNewsletter();
      $link = $this->newsletterLinkRepository->findOneBy([
        'url' => NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
        'queue' => $queueId,
      ]);
    }


    if ($queue && isset($link, $newsletter)) {
      $this->statisticsClicksRepository->createOrUpdateClickCount(
        $link,
        $this->subscriber,
        $newsletter,
        $queue,
        null
      );
      $this->statisticsClicksRepository->flush();
    }
  }

  private function addTypeParamToUnsubscribeUrl(string $unsubscribeUrl): string {
    if (empty($unsubscribeUrl)) {
        return $unsubscribeUrl;
    }
    // using the same value as mailpoet/views/subscription/confirm_unsubscribe.html#4
    return $this->wp->addQueryArg('type', 'confirmation', $unsubscribeUrl);
  }
}
