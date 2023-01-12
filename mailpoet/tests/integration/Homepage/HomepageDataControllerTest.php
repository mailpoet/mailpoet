<?php declare(strict_types = 1);

namespace MailPoet\Homepage;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
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
    expect($data['taskListDismissed'])->false();
    expect($data['productDiscoveryDismissed'])->false();
    expect($data['taskListStatus'])->array();
    expect($data['taskListStatus'])->notEmpty();
    expect($data['productDiscoveryStatus'])->array();
    expect($data['productDiscoveryStatus'])->notEmpty();
    expect($data['wooCustomersCount'])->int();
    expect($data['subscribersCount'])->int();
  }

  public function testItFetchesSenderTaskListStatus(): void {
    $settings = $this->diContainer->get(SettingsController::class);

    $settings->set('sender', null);
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.address', 'test@email.com');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->false();

    $settings->set('sender.name', 'John Doe');
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['senderSet'])->true();
  }

  public function testItDoesntFetchTaskListStatusWhenTaskListDismissed(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('homepage.task_list_dismissed', true);
    $data = $this->homepageDataController->getPageData();
    expect($data['taskListStatus'])->null();
  }

  public function testItFetchesSubscribersAddedTaskListStatus(): void {
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->false();

    $form = (new Form())->create();
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->true();
    $this->entityManager->remove($form);
    $this->entityManager->flush($form);

    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->false();

    for ($x = 0; $x <= 11; $x++) {
      (new Subscriber())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    }
    $data = $this->homepageDataController->getPageData();
    $taskListStatus = $data['taskListStatus'];
    expect($taskListStatus['subscribersAdded'])->true();
  }

  public function testItFetchesProductDiscoveryStatusForWelcomeCampaign(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->false();

    // Not done when welcome newsletter is activated
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->false();

    // Done when welcome newsletter is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpWelcomeCampaign'])->true();
  }

  public function testItFetchesProductDiscoveryStatusSentNewsletters(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Not done when standard newsletter is draft
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Done when standard newsletter is scheduled
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Done when standard newsletter is sent
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Not done when post notification is draft
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->false();

    // Done when post notification is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();

    // Done when automatic email active
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['sendFirstNewsletter'])->true();
  }

  public function testItFetchesProductDiscoveryStatusSetUpAbandonedCartEmail(): void {
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->false();

    $newsletter = (new Newsletter())
      ->withAutomaticTypeWooCommerceAbandonedCart()
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->create();

    // Not done when abandoned cart email is draft
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->false();

    // Done when abandoned cart email is active
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->flush();
    $productDiscoveryStatus = $this->homepageDataController->getPageData()['productDiscoveryStatus'];
    expect($productDiscoveryStatus['setUpAbandonedCartEmail'])->true();
  }

  private function cleanup(): void {
    $this->truncateEntity(SettingEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(FormEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
  }
}
