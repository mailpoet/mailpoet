<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use Codeception\Util\Stub;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
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
        'revenue' => null,
      ],
    ];
    $repository = Stub::make(NewsletterStatisticsRepository::class, [
      'getTotalSentCount' => $stats['total_sent'],
      'getChildrenCount' => $stats['children_count'],
      'getStatistics' => new NewsletterStatistics(4, 6, 2, 10, null),
    ]);
    $responseBuilder = new NewslettersResponseBuilder($em, $repository);
    $response = $responseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_CHILDREN_COUNT,
      NewslettersResponseBuilder::RELATION_TOTAL_SENT,
      NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    expect($response['total_sent'])->equals($stats['total_sent']);
    expect($response['children_count'])->equals($stats['children_count']);
    expect($response['statistics'])->equals($stats['statistics']);
    $em->remove($newsletter);
    $em->flush();
  }
}
