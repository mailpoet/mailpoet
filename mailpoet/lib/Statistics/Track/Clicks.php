<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics\Track;

use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\GATracking;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Util\Cookies;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class Clicks {

  /** The link whose whole purpose is to switch tracking off. Its own click is never recorded. */
  const TRACKING_OPT_OUT_SHORTCODE = '[link:subscription_tracking_opt_out_url]';

  /**
   * Matches the start of a link whose destination is already fixed:
   * - "https://example.com/", "//example.com#", "whatsapp://send?": an optional URI scheme (RFC 3986),
   *   "//", a host, and the "/", "?" or "#" that ends it; whitespace is not a host, browsers drop tabs
   *   and line breaks before they parse a URL;
   * - "mailto:", "tel:", "sms:": schemes handed to an app, never to a web host;
   * - "geo:0,0", "skype:echo": any other scheme except http(s) followed by anything but "/", "\" or
   *   whitespace, so the link has no host part; browsers read "\" as "/" and drop tabs and line
   *   breaks, so a value after one of them could add a host.
   */
  private const FIXED_DESTINATION_PATTERN = '~^(?:(?:[a-z][a-z0-9+.-]*:)?//[^/?#\\\\\s]+[/?#]|(?:mailto|tel|sms):|(?!https?:)[a-z][a-z0-9+.-]*:[^/\\\\\s])~i';

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

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var Request */
  private $request;

  /** @var TrackingConsentController */
  private $trackingConsentController;

  private PersonalizationTagLinkResolver $linkResolver;

  private GATracking $gaTracking;

  public function __construct(
    Cookies $cookies,
    SubscriberCookie $subscriberCookie,
    Shortcodes $shortcodes,
    Opens $opens,
    StatisticsClicksRepository $statisticsClicksRepository,
    LinkShortcodeCategory $linkShortcodeCategory,
    SubscribersRepository $subscribersRepository,
    TrackingConfig $trackingConfig,
    Request $request,
    TrackingConsentController $trackingConsentController,
    PersonalizationTagLinkResolver $linkResolver,
    GATracking $gaTracking
  ) {
    $this->cookies = $cookies;
    $this->subscriberCookie = $subscriberCookie;
    $this->shortcodes = $shortcodes;
    $this->linkShortcodeCategory = $linkShortcodeCategory;
    $this->opens = $opens;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->trackingConfig = $trackingConfig;
    $this->request = $request;
    $this->trackingConsentController = $trackingConsentController;
    $this->linkResolver = $linkResolver;
    $this->gaTracking = $gaTracking;
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
    // The opt-out link exists to stop tracking, so clicking it is never itself
    // recorded, even for a subscriber we are otherwise allowed to track. Only
    // the recording is skipped; the redirect below still runs.
    $isTrackingOptOutLink = $link->getUrl() === self::TRACKING_OPT_OUT_SHORTCODE;
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    // No tracking consent (CNIL/Garante): skip all recording (stats, cookies,
    // engagement) but keep the redirect below.
    if (!$wpUserPreview && $trackingAllowed && !$isTrackingOptOutLink) {
      $statisticsClicks = $this->statisticsClicksRepository->createOrUpdateClickCount(
        $link,
        $subscriber,
        $newsletter,
        $queue,
        !empty($data->userAgent) ? (string)$data->userAgent : null
      );
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
      // A link stored as a personalization tag token; its destination only exists per recipient,
      // so it gets the GA params ordinary links have baked in at send time only now. The tag sets
      // the whole URL, so it may only lead to the site's own host or allowed redirect hosts.
      $resolvedUrl = $this->linkResolver->resolve($url, $newsletter, $subscriber, $queue, $wpUserPreview);
      if ($resolvedUrl === null) {
        $this->abort();
        return $url;
      }
      return $this->appendRequestMethod($this->validateRedirect($this->gaTracking->addParamsToUrl($resolvedUrl, $newsletter)));
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
      $processedUrl = $this->shortcodes->replace($url);
      // Shortcodes first, then embedded personalization tags: the order the sending task uses for a
      // link it cannot track.
      $processedUrl = $this->linkResolver->resolveEmbeddedTokensForRecipient($processedUrl, $newsletter, $subscriber, $queue, $wpUserPreview);
      if ($processedUrl !== $url && !$this->keepsStoredDestination($url, $processedUrl)) {
        return $this->validateRedirect($processedUrl);
      }
      $url = $processedUrl;
    }
    return $url;
  }

  private function validateRedirect(string $url): string {
    $wp = WPFunctions::get();
    return $wp->wpValidateRedirect($url, $wp->homeUrl());
  }

  /**
   * Shortcode and tag values (e.g. subscriber fields) may fill in the path or query of a link, but
   * the destination host may only come from them when validateRedirect() accepts it (the site's
   * own). So the URL the redirect sends has to start with the scheme and host of the stored URL. The
   * stored URL counts as stored and in its decoded form, because the resolver rebuilds a URL from the
   * decoded form when a tag is stored URL-encoded or as an HTML entity. The prefix comes from the
   * stored URL alone and the sent URL is compared as it is, so a value that decodes together with
   * stored text ("&#" plus "64;" to "@") changes the prefix and fails the check.
   */
  private function keepsStoredDestination(string $storedUrl, string $url): bool {
    foreach ([$storedUrl, PersonalizationTagLinkResolver::decodeUrl($storedUrl)] as $storedForm) {
      if (preg_match(self::FIXED_DESTINATION_PATTERN, $storedForm, $destination) && strpos($url, $destination[0]) === 0) {
        return true;
      }
    }
    return false;
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
    return $url . (WPFunctions::get()->wpParseUrl($url, PHP_URL_QUERY) ? '&' : '?') . 'request_method=POST' . $fragment;
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
