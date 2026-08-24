<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics\Track;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\UserAgentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Util\Cookies;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class Clicks {

  const REVENUE_TRACKING_COOKIE_NAME = 'mailpoet_revenue_tracking';
  const REVENUE_TRACKING_COOKIE_EXPIRY = 60 * 60 * 24 * 14;

  /** @var Cookies */
  private $cookies;

  /** @var SubscriberCookie */
  private $subscriberCookie;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var LinkShortcodeCategory */
  private $linkShortcodeCategory;

  /** @var Opens */
  private $opens;

  /** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /** @var UserAgentsRepository */
  private $userAgentsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var Request */
  private $request;

  /** @var TrackingConsentController */
  private $trackingConsentController;

  private PersonalizationTagLinkResolver $linkResolver;

  public function __construct(
    Cookies $cookies,
    SubscriberCookie $subscriberCookie,
    Shortcodes $shortcodes,
    Opens $opens,
    StatisticsClicksRepository $statisticsClicksRepository,
    UserAgentsRepository $userAgentsRepository,
    LinkShortcodeCategory $linkShortcodeCategory,
    SubscribersRepository $subscribersRepository,
    TrackingConfig $trackingConfig,
    Request $request,
    TrackingConsentController $trackingConsentController,
    PersonalizationTagLinkResolver $linkResolver
  ) {
    $this->cookies = $cookies;
    $this->subscriberCookie = $subscriberCookie;
    $this->shortcodes = $shortcodes;
    $this->linkShortcodeCategory = $linkShortcodeCategory;
    $this->opens = $opens;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->userAgentsRepository = $userAgentsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->trackingConfig = $trackingConfig;
    $this->request = $request;
    $this->trackingConsentController = $trackingConsentController;
    $this->linkResolver = $linkResolver;
  }

  /**
   * @param \stdClass|null $data
   */
  public function track($data) {
    if (!$data || empty($data->link)) {
      return $this->abort();
    }
    /** @var SubscriberEntity $subscriber */
    $subscriber = $data->subscriber;
    /** @var SendingQueueEntity $queue */
    $queue = $data->queue;
    /** @var NewsletterEntity $newsletter */
    $newsletter = $data->newsletter;
    /** @var NewsletterLinkEntity $link */
    $link = $data->link;
    $wpUserPreview = ($data->preview && ($subscriber->isWPUser()));
    $trackingAllowed = $this->trackingConsentController->isTrackingAllowed($subscriber);
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    // No tracking consent (CNIL/Garante): skip all recording (stats, cookies,
    // engagement) but keep the redirect below.
    if (!$wpUserPreview && $trackingAllowed) {
      $userAgent = !empty($data->userAgent) ? $this->userAgentsRepository->findOrCreate($data->userAgent) : null;
      $statisticsClicks = $this->statisticsClicksRepository->createOrUpdateClickCount(
        $link,
        $subscriber,
        $newsletter,
        $queue,
        $userAgent
      );
      if (
        $userAgent instanceof UserAgentEntity &&
        ($userAgent->getUserAgentType() === UserAgentEntity::USER_AGENT_TYPE_HUMAN
        || $statisticsClicks->getUserAgentType() === UserAgentEntity::USER_AGENT_TYPE_MACHINE)
      ) {
        $statisticsClicks->setUserAgent($userAgent);
        $statisticsClicks->setUserAgentType($userAgent->getUserAgentType());
      }
      $this->statisticsClicksRepository->flush();
      $this->sendRevenueCookie($statisticsClicks);

      $subscriberId = $subscriber->getId();
      if ($subscriberId) {
        $this->subscriberCookie->setSubscriberId($subscriberId);
      }

      // track open event
      $this->opens->track($data, $displayImage = false);
      // Update engagement date
      $this->subscribersRepository->maybeUpdateLastClickAt($subscriber);
    }
    $url = $this->processUrl($link->getUrl(), $newsletter, $subscriber, $queue, $wpUserPreview);
    if ($trackingAllowed) {
      // Consumers of this hook (e.g. automation "clicked link" triggers) use
      // clicks for follow-up personalization — exactly what consent covers.
      do_action('mailpoet_link_clicked', $link, $subscriber, $wpUserPreview);
    }
    $this->redirectToUrl($url);
  }

  private function sendRevenueCookie(StatisticsClickEntity $clicks) {
    if ($this->trackingConfig->isCookieTrackingEnabled()) {
      $this->cookies->set(
        self::REVENUE_TRACKING_COOKIE_NAME,
        [
          'statistics_clicks' => $clicks->getId(),
          'created_at' => time(),
        ],
        [
          'expires' => time() + self::REVENUE_TRACKING_COOKIE_EXPIRY,
          'path' => '/',
        ]
      );
    }
  }

  public function processUrl(
    string $url,
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    bool $wpUserPreview
  ) {
    if ($this->linkResolver->isTokenUrl($url)) {
      // A link stored as a personalization tag token; its destination only exists per recipient.
      $resolvedUrl = $this->linkResolver->resolve($url, $newsletter, $subscriber, $queue, $wpUserPreview);
      if ($resolvedUrl === null) {
        $this->abort();
        return $url;
      }
      return $this->appendRequestMethod($resolvedUrl);
    }
    if (preg_match('/\[link:(?P<action>.*?)\]/', $url, $shortcode)) {
      if (empty($shortcode['action'])) $this->abort();
      $processedUrl = $this->linkShortcodeCategory->processShortcodeAction(
        $shortcode[0],
        $newsletter,
        $subscriber,
        $queue,
        $wpUserPreview
      );
      // If shortcode was not processed, return original shortcode unchanged
      if ($processedUrl === null) {
        return $shortcode[0];
      }
      $url = $this->appendRequestMethod($processedUrl);
    } else {
      $this->shortcodes->setQueue($queue);
      $this->shortcodes->setNewsletter($newsletter);
      $this->shortcodes->setSubscriber($subscriber);
      $this->shortcodes->setWpUserPreview($wpUserPreview);
      $url = $this->shortcodes->replace($url);
    }
    return $url;
  }

  /**
   * The unsubscribe actions need to know the original request method.
   */
  private function appendRequestMethod(string $url): string {
    if (!$this->request->isPost() || !$url) {
      return $url;
    }
    $fragment = '';
    $hashPosition = strpos($url, '#');
    if ($hashPosition !== false) {
      $fragment = substr($url, $hashPosition);
      $url = substr($url, 0, $hashPosition);
    }
    return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'request_method=POST' . $fragment;
  }

  public function abort() {
    global $wp_query;// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    WPFunctions::get()->statusHeader(404);
    $wp_query->set_404();// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    WPFunctions::get()->getTemplatePart((string)404);
    exit;
  }

  public function redirectToUrl($url) {
    header('Location: ' . $url, true, 302);
    exit;
  }
}
