<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper;
use WC_Order;

class WooCommercePurchases {
  const USE_CLICKS_SINCE_DAYS_AGO = 14;

  /** @var Helper */
  private $woocommerceHelper;

  /** @var Cookies */
  private $cookies;

  /** @var StatisticsWooCommercePurchasesRepository */
  private $statisticsWooCommercePurchasesRepository;

  /** @var StatisticsClicksRepository */
  private $statisticsClicksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberHandler */
  private $subscriberHandler;

  public function __construct(
    Helper $woocommerceHelper,
    StatisticsWooCommercePurchasesRepository $statisticsWooCommercePurchasesRepository,
    StatisticsClicksRepository $statisticsClicksRepository,
    SubscribersRepository $subscribersRepository,
    Cookies $cookies,
    SubscriberHandler $subscriberHandler
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
    $this->cookies = $cookies;
    $this->statisticsWooCommercePurchasesRepository = $statisticsWooCommercePurchasesRepository;
    $this->statisticsClicksRepository = $statisticsClicksRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberHandler = $subscriberHandler;
  }

  public function trackPurchase($id, $useCookies = true) {
    $order = $this->woocommerceHelper->wcGetOrder($id);
    if (!$order instanceof WC_Order) {
      return;
    }

    // limit clicks to 'USE_CLICKS_SINCE_DAYS_AGO' range before order has been created
    $fromDate = $order->get_date_created();
    if (is_null($fromDate)) {
      return;
    }
    $from = clone $fromDate;
    $from->modify(-self::USE_CLICKS_SINCE_DAYS_AGO . ' days');
    $to = $order->get_date_created();
    if (is_null($to)) {
      return;
    }

    // track purchases from all clicks matched by order email
    $processedNewsletterIdsMap = [];
    $orderEmailClicks = $this->getClicks($order->get_billing_email(), $from, $to);
    foreach ($orderEmailClicks as $click) {
      $this->statisticsWooCommercePurchasesRepository->createOrUpdateByClickDataAndOrder($click, $order);
      $newsletter = $click->getNewsletter();
      if (!$newsletter instanceof NewsletterEntity) continue;
      $processedNewsletterIdsMap[$newsletter->getId()] = true;
    }

    // try to find a subscriber by order email and start tracking
    $this->subscriberHandler->identifyByEmail($order->get_billing_email());

    if (!$useCookies) {
      return;
    }

    // track purchases from clicks matched by cookie email (only for newsletters not tracked by order)
    $cookieEmailClicks = $this->getClicks($this->getSubscriberEmailFromCookie(), $from, $to);
    foreach ($cookieEmailClicks as $click) {
      $newsletter = $click->getNewsletter();
      if (!$newsletter instanceof NewsletterEntity) continue;
      if (isset($processedNewsletterIdsMap[$newsletter->getId()])) {
        continue; // do not track click for newsletters that were already tracked by order email
      }
      $this->statisticsWooCommercePurchasesRepository->createOrUpdateByClickDataAndOrder($click, $order);
    }
  }

  /**
   * @param ?string $email
   * @param \DateTimeInterface $from
   * @param \DateTimeInterface $to
   * @return StatisticsClickEntity[]
   */
  private function getClicks(?string $email, \DateTimeInterface $from, \DateTimeInterface $to): array {
    if (!$email) return [];
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    if (!$subscriber instanceof SubscriberEntity) {
      return [];
    }
    return $this->statisticsClicksRepository->findLatestPerNewsletterBySubscriber($subscriber, $from, $to);
  }

  private function getSubscriberEmailFromCookie(): ?string {
    $cookieData = $this->cookies->get(Clicks::REVENUE_TRACKING_COOKIE_NAME);
    if (!$cookieData) {
      return null;
    }
    try {
      $click = $this->statisticsClicksRepository->findOneById($cookieData['statistics_clicks']);
    } catch (\Exception $e) {
      return null;
    }
    if (!$click instanceof StatisticsClickEntity) {
      return null;
    }

    $subscriber = $click->getSubscriber();
    if ($subscriber instanceof SubscriberEntity) {
      return $subscriber->getEmail();
    }
    return null;
  }
}
