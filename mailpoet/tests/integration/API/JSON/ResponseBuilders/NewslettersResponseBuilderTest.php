<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use Codeception\Util\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewslettersResponseBuilderTest extends \MailPoetTest {
  public function testItBuildsStats() {
    $di = ContainerWrapper::getInstance();
    $em = $di->get(EntityManager::class);
    $em->persist($newsletter = new NewsletterEntity);
    $newsletter->setSubject('Response Builder Test');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $em->flush();
    $stats = [
      'total_sent' => 10,
      'children_count' => 3,
      'statistics' => [
        'opened' => 6,
        'clicked' => 4,
        'unsubscribed' => 2,
        'bounced' => 1,
        'machineOpened' => 9,
        'revenue' => null,
      ],
    ];
    $statistics = new NewsletterStatistics(4, 6, 2, 1, 10, null);
    $statistics->setMachineOpenCount(9);
    $newsletterStatsRepository = Stub::make(NewsletterStatisticsRepository::class, [
      'getTotalSentCount' => $stats['total_sent'],
      'getChildrenCount' => $stats['children_count'],
      'getStatistics' => $statistics,
    ]);
    $newsletterRepository = Stub::make(NewslettersRepository::class);
    $newsletterUrl = $this->diContainer->get(Url::class);
    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $logRepository = $this->diContainer->get(LogRepository::class);
    $responseBuilder = new NewslettersResponseBuilder($em, $newsletterRepository, $newsletterStatsRepository, $newsletterUrl, $sendingQueuesRepository, $logRepository);
    $response = $responseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_CHILDREN_COUNT,
      NewslettersResponseBuilder::RELATION_TOTAL_SENT,
      NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    verify($response['total_sent'])->equals($stats['total_sent']);
    verify($response['children_count'])->equals($stats['children_count']);
    verify($response['statistics'])->equals($stats['statistics']);
    $em->remove($newsletter);
    $em->flush();
  }
}
