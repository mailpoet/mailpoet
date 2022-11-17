<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SegmentsResponseBuilderTest extends \MailPoetTest {
  public function testItBuildsResponse() {
    $name = 'Response Builder Test';
    $description = 'Testing description';

    $di = ContainerWrapper::getInstance();
    $em = $di->get(EntityManager::class);
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, $description);
    $em->persist($segment);
    $em->flush();
    $responseBuilder = $di->get(SegmentsResponseBuilder::class);
    $response = $responseBuilder->build($segment);

    expect($response['name'])->equals($name);
    expect($response['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($response['description'])->equals($description);
    expect($response)->hasKey('id');
    expect($response)->hasKey('created_at');
    expect($response)->hasKey('updated_at');
    expect($response)->hasKey('deleted_at');
    $em->remove($segment);
    $em->flush();
  }

  public function testItBuildsListingsResponse() {
    $name = 'Response Listings Builder Test';
    $description = 'Testing description';

    $di = ContainerWrapper::getInstance();
    $em = $di->get(EntityManager::class);
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, $description);
    $em->persist($segment);
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('a@example.com');
    $em->persist($subscriber);
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $em->persist($subscriberSegment);

    $em->flush();

    $responseBuilder = $di->get(SegmentsResponseBuilder::class);
    $response = $responseBuilder->buildForListing([$segment]);
    expect($response)->array();
    expect($response[0]['name'])->equals($name);
    expect($response[0]['type'])->equals(SegmentEntity::TYPE_DEFAULT);
    expect($response[0]['subscribers_url'])->startsWith('http');
    expect($response[0]['subscribers_count']['subscribed'])->equals('1');
  }
}
