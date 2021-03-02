<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;

class DynamicSegmentsResponseBuilderTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->cleanup();
  }

  public function testItBuildsListingsResponse() {
    $name = 'Response Listings Builder Test';
    $description = 'Testing description';
    $wpUserEmail = 'editor1@example.com';

    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $wpUserSubscriber = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->findOneBy(['email' => $wpUserEmail]);
    assert($wpUserSubscriber instanceof SubscriberEntity);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $segment = $this->createDynamicSegmentEntity($name, $description);
    $this->entityManager->flush();

    /** @var DynamicSegmentsResponseBuilder $responseBuilder */
    $responseBuilder = $this->diContainer->get(DynamicSegmentsResponseBuilder::class);
    $response = $responseBuilder->buildForListing([$segment]);
    expect($response)->array();
    expect($response[0]['name'])->equals($name);
    expect($response[0]['description'])->equals($description);
    expect($response[0]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    expect($response[0]['subscribers_url'])->startsWith('http');
    expect($response[0]['count_all'])->equals(1);
    expect($response[0]['count_subscribed'])->equals(1);

    $this->tester->deleteWordPressUser($wpUserEmail);
  }

  private function createDynamicSegmentEntity(string $name, string $description): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, $description);
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, [
      'wordpressRole' => 'editor',
      'segmentType' => DynamicSegmentFilterEntity::TYPE_USER_ROLE,
    ]);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }

  private function cleanup() {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }
}
