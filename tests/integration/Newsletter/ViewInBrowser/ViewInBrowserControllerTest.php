<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub\Expected;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Idiorm\ORM;

class ViewInBrowserControllerTest extends \MailPoetTest {
  /** @var ViewInBrowserController */
  private $viewInBrowserController;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var Newsletter */
  private $newsletter;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SendingTask */
  private $sendingTask;

  /** @var mixed[] */
  private $browserPreviewData;

  public function _before() {
    // instantiate class
    $this->viewInBrowserController = $this->diContainer->get(ViewInBrowserController::class);
    $this->linkTokens = $this->diContainer->get(LinkTokens::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();

    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $this->subscriber = $subscriber;

    // create task & queue
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->id;
    $sendingTask->setSubscribers([$subscriber->getId()]);
    $sendingTask->updateProcessedSubscribers([$subscriber->getId()]);
    $this->sendingTask = $sendingTask->save();
    $linkTokens = new LinkTokens;

    // build browser preview data
    $subscriberModel = Subscriber::findOne($subscriber->getId());
    $this->browserPreviewData = [
      'queue_id' => $sendingTask->queue()->id,
      'subscriber_id' => $subscriber->getId(),
      'newsletter_id' => $newsletter->id,
      'newsletter_hash' => $newsletter->hash,
      'subscriber_token' => $linkTokens->getToken($subscriberModel),
      'preview' => false,
    ];
  }

  public function testItThrowsWhenDataIsMissing() {
    // newsletter ID is required
    $data = $this->browserPreviewData;
    unset($data['newsletter_id']);
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Missing 'newsletter_id'");

    // newsletter hash is required
    $data = $this->browserPreviewData;
    unset($data['newsletter_hash']);
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Missing 'newsletter_hash'");

    // subscriber token is required if subscriber is provided
    $data = $this->browserPreviewData;
    unset($data['subscriber_token']);
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Missing 'subscriber_token'");
  }

  public function testItThrowsWhenDataIsInvalid() {
    // newsletter ID is invalid
    $data = $this->browserPreviewData;
    $data['newsletter_id'] = 99;
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Invalid 'newsletter_id'");

    // newsletter hash is invalid
    $data = $this->browserPreviewData;
    $data['newsletter_hash'] = 'invalid-hash';
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Invalid 'newsletter_hash'");

    // subscriber token is invalid
    $data = $this->browserPreviewData;
    $data['subscriber_token'] = 'invalid-token';
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, "Invalid 'subscriber_token'");
  }

  public function testItThrowsWhenSubscriberIsNotOnProcessedList() {
    $data = $this->browserPreviewData;
    $sendingTask = $this->sendingTask;
    $sendingTask->setSubscribers([]);
    $sendingTask->updateProcessedSubscribers([]);
    $sendingTask->save();
    $this->expectViewThrowsExceptionWithMessage($this->viewInBrowserController, $data, 'Subscriber did not receive the newsletter yet');
  }

  public function testItSetsSubscriberToLoggedInWPUserWhenPreviewIsEnabled() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, Newsletter $newsletter, SubscriberEntity $subscriber = null, SendingQueue $queue = null) {
        assert($subscriber !== null); // PHPStan
        expect($subscriber)->notNull();
        expect($subscriber->getId())->equals(1);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    unset($data['subscriber_id']);
    $data['preview'] = true;

    $this->subscriber->setWpUserId(1);
    $this->subscribersRepository->flush();
    wp_set_current_user(1);
    $viewInBrowserController->view($data);
  }

  public function testItGetsQueueByQueueId() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, Newsletter $newsletter, SubscriberEntity $subscriber = null, SendingQueue $queue = null) {
        assert($queue !== null); // PHPStan
        expect($queue)->notNull();
        expect($queue->id)->same($this->sendingTask->id);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    $data['queueId'] = $this->sendingTask->queue()->id;
    $viewInBrowserController->view($data);
  }

  public function testItGetsQueueByNewsletter() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, Newsletter $newsletter, SubscriberEntity $subscriber = null, SendingQueue $queue = null) {
        assert($queue !== null); // PHPStan
        expect($queue)->notNull();
        expect($queue->id)->same($this->sendingTask->queue()->id);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    $data['queueId'] = null;
    $viewInBrowserController->view($data);
  }

  public function testItResetsQueueForWelcomeEmails() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, Newsletter $newsletter, SubscriberEntity $subscriber = null, SendingQueue $queue = null) {
        expect($queue)->null();
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    // queue will be set to null for welcome email
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->save();
    $viewInBrowserController->view($this->browserPreviewData);
  }

  public function testItResetsQueueForAutomaticEmailsInPreview() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, Newsletter $newsletter, SubscriberEntity $subscriber = null, SendingQueue $queue = null) {
        expect($queue)->null();
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    // queue will be set to null for automatic email
    $data = $this->browserPreviewData;
    $data['preview'] = true;
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_AUTOMATIC;
    $newsletter->save();
    $viewInBrowserController->view($data);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    // reset WP user role
    $wpUser = wp_get_current_user();
    $wpUser->add_role('administrator');
  }

  private function expectViewThrowsExceptionWithMessage(ViewInBrowserController $viewInBrowserController, array $data, string $message) {
    try {
      $viewInBrowserController->view($data);
      $this->fail("Expected 'InvalidArgumentException' with message '$message' was not thrown");
    } catch (\InvalidArgumentException $e) {
      expect($e->getMessage())->same($message);
    }
  }

  private function createController($viewInBrowserRenderer): ViewInBrowserController {
    return new ViewInBrowserController(
      $this->linkTokens,
      $viewInBrowserRenderer,
      $this->subscribersRepository
    );
  }
}
