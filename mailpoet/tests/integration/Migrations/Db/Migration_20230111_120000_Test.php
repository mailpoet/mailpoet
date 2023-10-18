<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Segment;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20230111_120000_Test extends \MailPoetTest {
  /** @var Migration_20230111_120000 */
  private $migration;

  /** @var SettingsController */
  private $settings;

  /** @var SegmentEntity[] */
  private $segments;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20230111_120000($this->diContainer);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $segmentsFactory = new Segment();
    $this->segments[] = $segmentsFactory->withName('Segment 1')->create();
    $this->segments[] = $segmentsFactory->withName('Segment 2')->create();
    $this->segments[] = $segmentsFactory->withName('Segment 3')->create();

    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("
      ALTER TABLE {$segmentsTable}
      DROP COLUMN display_in_manage_subscription_page;
    ");
  }

  public function testItSetsDisplayInManageSubscriptionPageProperly() {
    $this->settings->set('subscription', ['segments' => [$this->segments[0]->getId(), $this->segments[2]->getId()]]);
    $this->migration->run();
    foreach ($this->segments as $segment) {
      $this->entityManager->refresh($segment);
    }
    verify($this->segments[0]->getDisplayInManageSubscriptionPage())->equals(true);
    verify($this->segments[1]->getDisplayInManageSubscriptionPage())->equals(false);
    verify($this->segments[2]->getDisplayInManageSubscriptionPage())->equals(true);
    verify($this->settings->fetch('subscription.segments'))->equals([]);
  }

  public function testItSetsAllToTrueWithoutDefinedSetting() {
    $this->settings->delete('subscription');
    $this->migration->run();
    foreach ($this->segments as $segment) {
      $this->entityManager->refresh($segment);
    }
    verify($this->segments[0]->getDisplayInManageSubscriptionPage())->equals(true);
    verify($this->segments[1]->getDisplayInManageSubscriptionPage())->equals(true);
    verify($this->segments[2]->getDisplayInManageSubscriptionPage())->equals(true);
  }
}
