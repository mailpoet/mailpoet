<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;

class DynamicSegmentsTest extends \MailPoetTest {

  const SUCCESS_RESPONSE_CODE = 200;
  const SEGMENT_NOT_FOUND_RESPONSE_CODE = 404;
  const INVALID_DATA_RESPONSE_CODE = 400;
  const SERVER_ERROR_RESPONSE_CODE = 409;

  /** @var DynamicSegments */
  private $endpoint;

  public function _before() {
    $this->endpoint = ContainerWrapper::getInstance()->get(DynamicSegments::class);
  }

  public function testGetReturnsResponse() {
    $segment = $this->createDynamicSegmentEntity('s1', '');
    $response = $this->endpoint->get(['id' => $segment->getId()]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['id'])->equals($segment->getId());
  }

  public function testGetReturnsError() {
    $response = $this->endpoint->get(['id' => 5]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SEGMENT_NOT_FOUND_RESPONSE_CODE);
  }

  public function testSaverSavesData() {
    $response = $this->endpoint->save([
      'name' => 'Test dynamic',
      'description' => 'description dynamic',
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'editor',
        'action' => UserRole::TYPE,
      ]],
    ]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals('Test dynamic');
  }

  public function testSaverReturnsErrorOnInvalidFilterData() {
    $response = $this->endpoint->save([
      'name' => 'Test dynamic',
    ]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Please add at least one condition for filtering.');
  }

  public function testSaverReturnsErrorOnDuplicateRecord() {
    $data = [
      'name' => 'Test dynamic',
      'description' => 'description dynamic',
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'editor',
      ]],
    ];
    $this->endpoint->save($data);
    $response = $this->endpoint->save($data);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Another record already exists. Please specify a different "name".');
  }

  public function testSaverReturnsErrorOnEmptyName() {
    $data = [
      'description' => 'description dynamic',
      'filters' => [[
        'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
        'wordpressRole' => 'editor',
      ]],
    ];
    $this->endpoint->save($data);
    $response = $this->endpoint->save($data);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Please specify a name.');
  }

  public function testItCanTrashASegment() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Trash test', 'description');

    $response = $this->endpoint->trash(['id' => $dynamicSegment->getId()]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals($dynamicSegment->getName());
    expect($response->meta['count'])->equals(1);

    $this->entityManager->refresh($dynamicSegment);
    $this->assertInstanceOf(SegmentEntity::class, $dynamicSegment);
    expect($dynamicSegment->getDeletedAt())->notNull();
  }

  public function testItReturnsErrorWhenTrashingSegmentWithActiveNewsletter() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Trash test 2', 'description');
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $dynamicSegment);
    $this->entityManager->persist($newsletter);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    $response = $this->endpoint->trash(['id' => $dynamicSegment->getId()]);
    $this->entityManager->refresh($dynamicSegment);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals("Segment cannot be deleted because it’s used for 'Subject' email");
  }

  public function testItCanRestoreASegment() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Trash test', 'description');

    $response = $this->endpoint->restore(['id' => $dynamicSegment->getId()]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals($dynamicSegment->getName());
    expect($response->meta['count'])->equals(1);

    $this->entityManager->refresh($dynamicSegment);
    $this->assertInstanceOf(SegmentEntity::class, $dynamicSegment);
    expect($dynamicSegment->getDeletedAt())->null();
  }

  public function testItCanDeleteASegment() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Delete test', 'description');
    $dynamicSegmentFilter = $dynamicSegment->getDynamicFilters()->first();
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $dynamicSegmentFilter);

    $response = $this->endpoint->delete(['id' => $dynamicSegment->getId()]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals(null);
    expect($response->meta['count'])->equals(1);

    // Clear entity manager to forget all entities
    $this->entityManager->clear();

    expect($this->entityManager->find(SegmentEntity::class, $dynamicSegment->getId()))->null();
    expect($this->entityManager->find(DynamicSegmentFilterEntity::class, $dynamicSegmentFilter->getId()))->null();
  }

  public function testItCanBulkDeleteSegments() {
    $dynamicSegment1 = $this->createDynamicSegmentEntity('Test 1', 'description');
    $dynamicSegment2 = $this->createDynamicSegmentEntity('Test 2', 'description');

    $response = $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(2);

    $this->entityManager->refresh($dynamicSegment1);
    $this->entityManager->refresh($dynamicSegment2);
    expect($dynamicSegment1->getDeletedAt())->notNull();
    expect($dynamicSegment2->getDeletedAt())->notNull();

    $response = $this->endpoint->bulkAction([
      'action' => 'restore',
      'listing' => ['group' => 'trash'],
    ]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(2);

    $this->entityManager->refresh($dynamicSegment1);
    $this->entityManager->refresh($dynamicSegment2);
    expect($dynamicSegment1->getDeletedAt())->null();
    expect($dynamicSegment2->getDeletedAt())->null();

    $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(2);

    // Second delete doesn't delete anything
    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(0);

    $this->entityManager->clear();

    expect($this->entityManager->find(SegmentEntity::class, $dynamicSegment1->getId()))->null();
    expect($this->entityManager->find(SegmentEntity::class, $dynamicSegment2->getId()))->null();
  }

  private function createDynamicSegmentEntity(string $name, string $description): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, $description);
    $filterData = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE,
      ['wordpressRole' => 'editor']
    );
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    $this->entityManager->flush();
    return $segment;
  }
}
