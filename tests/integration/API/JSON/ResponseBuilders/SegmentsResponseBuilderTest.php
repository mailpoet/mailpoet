<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
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
}
