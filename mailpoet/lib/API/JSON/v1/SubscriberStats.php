<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Subscribers\SubscribersRepository;

class SubscriberStats extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
  ];

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberStatisticsRepository */
  private $subscribersStatisticsRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SubscriberStatisticsRepository $subscribersStatisticsRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersStatisticsRepository = $subscribersStatisticsRepository;
  }

  public function get($data) {
    $subscriber = isset($data['subscriber_id'])
      ? $this->subscribersRepository->findOneById((int)$data['subscriber_id'])
      : null;
    if (!$subscriber instanceof SubscriberEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
    $statistics = $this->subscribersStatisticsRepository->getStatistics($subscriber);
    $response = [
      'email' => $subscriber->getEmail(),
      'total_sent' => $statistics->getTotalSentCount(),
      'open' => $statistics->getOpenCount(),
      'machine_open' => $statistics->getMachineOpenCount(),
      'click' => $statistics->getClickCount(),
      'engagement_score' => $subscriber->getEngagementScore(),
    ];
    $lastEngagement = $subscriber->getLastEngagementAt();
    if ($lastEngagement instanceof \DateTimeInterface) {
      $response['last_engagement'] = $lastEngagement->format('Y-m-d H:i:s');
    }
    $woocommerce = $statistics->getWooCommerceRevenue();
    if ($woocommerce instanceof WooCommerceRevenue) {
      $response['woocommerce'] = $woocommerce->asArray();
    }
    return $this->successResponse($response);
  }
}
