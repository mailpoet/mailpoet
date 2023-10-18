<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Settings\SettingsController;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20221028_105818_Test extends \MailPoetTest {
  /** @var Migration_20221028_105818 */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20221028_105818($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  public function testItMigratesEmailMachineOpensFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    verify($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_OPENED);
    verify($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'any']);
  }

  public function testItMigratesEmailOpensFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_MACHINE_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    verify($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_MACHINE_OPENED);
    verify($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'any']);
  }

  public function testItMigratesEmailNotOpenedFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $data = ['newsletter_id' => '1'];
    $id = $this->createSegmentFilter(EmailAction::ACTION_NOT_OPENED, $data, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    verify($filter->getFilterData()->getAction())->equals(EmailAction::ACTION_OPENED);
    verify($filter->getFilterData()->getData())->equals(['newsletters' => [1], 'operator' => 'none']);
  }

  public function testItMigratesEmailClicksFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $dataNoLink = ['newsletter_id' => '1'];
    $id1 = $this->createSegmentFilter(EmailAction::ACTION_CLICKED, $dataNoLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $dataWithLink = ['newsletter_id' => '1', 'link_id' => '2'];
    $id2 = $this->createSegmentFilter(EmailAction::ACTION_CLICKED, $dataWithLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filterNoLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id1);
    $filterLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id2);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterNoLink);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterLink);
    verify($filterNoLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filterNoLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'any', 'link_ids' => []]);
    verify($filterLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filterLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'any', 'link_ids' => [2]]);
  }

  public function testItMigratesEmailNotClickedFiltersCorrectly() {
    $this->settings->set('db_version', '3.76.0');
    $dataNoLink = ['newsletter_id' => '1'];
    $id1 = $this->createSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $dataNoLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $dataWithLink = ['newsletter_id' => '1', 'link_id' => '2'];
    $id2 = $this->createSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $dataWithLink, DynamicSegmentFilterData::TYPE_EMAIL);
    $this->migration->run();
    $filterNoLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id1);
    $filterLink = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id2);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterNoLink);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filterLink);
    verify($filterNoLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filterNoLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'none', 'link_ids' => []]);
    verify($filterLink->getFilterData()->getAction())->equals(EmailAction::ACTION_CLICKED);
    verify($filterLink->getFilterData()->getData())->equals(['newsletter_id' => '1', 'operator' => 'none', 'link_ids' => [2]]);
  }

  private function createSegmentFilter(string $action, array $data, string $type, $segmentId = 1): int {
    $filterTable = $this->entityManager->getClassMetadata(DynamicSegmentFilterEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeQuery(
      "INSERT into $filterTable (segment_id, filter_data, action, filter_type)
     VALUES (:segment, :filter_data, :action, :filter_type)",
      [
        'segment' => $segmentId,
        'action' => $action,
        'filter_data' => \serialize($data),
        'filter_type' => $type,
      ]
    );
    return (int)$this->entityManager->getConnection()->lastInsertId();
  }
}
