<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;

class DynamicSegmentsResponseBuilderTest extends \MailPoetTest {
  public function testItBuildsGetResponse() {
    $name = 'Response Listings Builder Test';
    $description = 'Testing description';
    $segment = $this->createDynamicSegmentEntity($name, $description);
    $this->addDynamicFilter($segment, 'editor');
    $this->entityManager->flush();

    /** @var DynamicSegmentsResponseBuilder $responseBuilder */
    $responseBuilder = $this->diContainer->get(DynamicSegmentsResponseBuilder::class);
    $response = $responseBuilder->build($segment);
    verify($response)->isArray();
    verify($response['id'])->equals($segment->getId());
    verify($response['name'])->equals($name);
    verify($response['description'])->equals($description);
    verify($response['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    verify($response)->arrayHasKey('created_at');
    verify($response)->arrayHasKey('updated_at');
    verify($response)->arrayHasKey('deleted_at');
    verify($response['filters_connect'])->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);
    verify($response['filters'])->isArray();
    verify($response['filters'])->arrayCount(1);
    verify($response['filters'][0]['segmentType'])->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($response['filters'][0]['wordpressRole'])->equals(['editor']);
    verify($response['filters'][0]['action'])->equals(UserRole::TYPE);
  }

  public function testItBuildsGetResponseWithTwoFilters() {
    $name = 'Response Listings Builder Test';
    $description = 'Testing description';
    $segment = $this->createDynamicSegmentEntity($name, $description);
    $this->addDynamicFilter($segment, 'editor');
    $this->addDynamicFilter($segment, 'administrator');
    $this->entityManager->flush();

    /** @var DynamicSegmentsResponseBuilder $responseBuilder */
    $responseBuilder = $this->diContainer->get(DynamicSegmentsResponseBuilder::class);
    $response = $responseBuilder->build($segment);
    verify($response)->isArray();
    verify($response['id'])->equals($segment->getId());
    verify($response['name'])->equals($name);
    verify($response['description'])->equals($description);
    verify($response['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    verify($response)->arrayHasKey('created_at');
    verify($response)->arrayHasKey('updated_at');
    verify($response)->arrayHasKey('deleted_at');
    verify($response['filters_connect'])->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);
    verify($response['filters'])->isArray();
    verify($response['filters'])->arrayCount(2);
    verify($response['filters'][0]['segmentType'])->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($response['filters'][0]['wordpressRole'])->equals(['editor']);
    verify($response['filters'][0]['action'])->equals(UserRole::TYPE);
    verify($response['filters'][0]['connect'])->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);
    verify($response['filters'][1]['segmentType'])->equals(DynamicSegmentFilterData::TYPE_USER_ROLE);
    verify($response['filters'][1]['wordpressRole'])->equals(['administrator']);
    verify($response['filters'][1]['action'])->equals(UserRole::TYPE);
    verify($response['filters'][1]['connect'])->equals(DynamicSegmentFilterData::CONNECT_TYPE_AND);
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
    $this->assertInstanceOf(SubscriberEntity::class, $wpUserSubscriber);
    $wpUserSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $segment = $this->createDynamicSegmentEntity($name, $description);
    $this->addDynamicFilter($segment, 'editor');
    $this->entityManager->flush();

    /** @var DynamicSegmentsResponseBuilder $responseBuilder */
    $responseBuilder = $this->diContainer->get(DynamicSegmentsResponseBuilder::class);
    $response = $responseBuilder->buildForListing([$segment]);
    verify($response)->isArray();
    verify($response[0]['name'])->equals($name);
    verify($response[0]['description'])->equals($description);
    verify($response[0]['type'])->equals(SegmentEntity::TYPE_DYNAMIC);
    verify($response[0]['subscribers_url'])->stringStartsWith('http');
    verify($response[0]['count_all'])->equals(1);
    verify($response[0]['count_subscribed'])->equals(1);

    $this->tester->deleteWordPressUser($wpUserEmail);
  }

  private function createDynamicSegmentEntity(string $name, string $description): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, $description);
    $this->entityManager->persist($segment);
    return $segment;
  }

  private function addDynamicFilter(SegmentEntity $segment, string $wordpressRole): SegmentEntity {
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE, [
      'wordpressRole' => $wordpressRole,
      'connect' => DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ]));
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($dynamicFilter);
    return $segment;
  }
}
