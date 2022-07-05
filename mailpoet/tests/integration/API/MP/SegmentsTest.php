<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\CustomFields;
use MailPoet\API\MP\v1\Segments;
use MailPoet\API\MP\v1\Subscribers;
use MailPoet\Config\Changelog;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;

class SegmentsTest extends \MailPoetTest {
  /** @var SegmentFactory */
  private $segmentFactory;

  public function _before() {
    parent::_before();
    $this->segmentFactory = new SegmentFactory();
  }

  public function testItGetsAllDefaultSegments(): void {
    $segments = [
      $this->createOrUpdateSegment('Segment 1'),
      $this->createOrUpdateSegment('Segment 2'),
    ];

    $result = $this->getApi()->getLists();

    $this->assertCount(2, $result);
    foreach ($result as $key => $item) {
      $this->validateResponseItem($segments[$key], $item);
    }
  }

  public function testItExcludesWPUsersAndWooCommerceCustomersSegmentsWhenGettingSegments(): void {
    $this->createOrUpdateSegment('WordPress', SegmentEntity::TYPE_WP_USERS);
    $this->createOrUpdateSegment('WooCommerce', SegmentEntity::TYPE_WC_USERS);
    $defaultSegment = $this->createOrUpdateSegment('Segment 1', SegmentEntity::TYPE_DEFAULT, 'My default segment');

    $result = $this->getApi()->getLists();

    $this->assertCount(1, $result);
    $resultItem = reset($result);
    $this->validateResponseItem($defaultSegment, $resultItem);
  }

  private function getApi(): API {
    return new API(
      $this->makeEmpty(RequiredCustomFieldValidator::class),
      $this->diContainer->get(CustomFields::class),
      $this->diContainer->get(Segments::class),
      $this->diContainer->get(Subscribers::class),
      $this->diContainer->get(Changelog::class)
    );
  }

  private function validateResponseItem(SegmentEntity $segment, array $item): void {
    $this->assertEquals($segment->getId(), $item['id']);
    $this->assertEquals($segment->getName(), $item['name']);
    $this->assertEquals($segment->getDescription(), $item['description']);
    $this->assertEquals($segment->getType(), $item['type']);
    $this->assertArrayHasKey('created_at', $item);
    $this->assertArrayHasKey('updated_at', $item);
    $this->assertNull($item['deleted_at']);
  }

  private function createOrUpdateSegment(string $name, string $type = SegmentEntity::TYPE_DEFAULT, string $description = ''): SegmentEntity {
    return $this->segmentFactory
      ->withName($name)
      ->withType($type)
      ->withDescription($description)
      ->create();
  }

  public function _after() {
    $this->truncateEntity(SegmentEntity::class);
  }
}
