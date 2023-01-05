<?php declare(strict_types = 1);

namespace MailPoet\Homepage;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Subscriber;

class HomepageDataControllerTest extends \MailPoetTest {
  /** @var HomepageDataController */
  private $homepageDataController;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->homepageDataController = $this->diContainer->get(HomepageDataController::class);
  }

  public function testItFetchesBasicData(): void {
    $data = $this->homepageDataController->getPageData();
    expect($data)->notEmpty();
    expect($data['task_list_dismissed'])->false();
    expect($data['product_discovery_dismissed'])->false();
    expect($data['task_list_status'])->array();
    expect($data['task_list_status'])->notEmpty();
    expect($data['woo_customers_count'])->int();
    expect($data['subscribers_count'])->int();
  }

  public function testItFetchesSenderTaskListStatus(): void {
    $settings = $this->diContainer->get(\MailPoet\Settings\SettingsController::class);

    $settings->set('sender', null);
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.address', 'test@email.com');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.name', 'John Doe');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['senderSet'])->true();
  }

  public function testItFetchesSubscribersAddedTaskListStatus(): void {
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['subscribersAdded'])->false();

    $form = (new Form())->create();
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['subscribersAdded'])->true();
    $this->entityManager->remove($form);
    $this->entityManager->flush($form);

    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['subscribersAdded'])->false();

    for ($x = 0; $x <= 11; $x++) {
      (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    }
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['task_list_status'];
    expect($taskListStatus['subscribersAdded'])->true();
  }

  private function cleanup(): void {
    $this->truncateEntity(SettingEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(FormEntity::class);
  }
}
