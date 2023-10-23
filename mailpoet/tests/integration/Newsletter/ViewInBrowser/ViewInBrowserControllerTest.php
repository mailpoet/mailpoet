<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub\Expected;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Util\Security;

class ViewInBrowserControllerTest extends \MailPoetTest {
  /** @var ViewInBrowserController */
  private $viewInBrowserController;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SendingTask */
  private $sendingTask;

  /** @var mixed[] */
  private $browserPreviewData;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var Url */
  private $newsletterUrl;

  public function _before() {
    // instantiate class
    $this->viewInBrowserController = $this->diContainer->get(ViewInBrowserController::class);
    $this->linkTokens = $this->diContainer->get(LinkTokens::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterUrl = $this->diContainer->get(Url::class);

    // create newsletter
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->create();
    $newsletter->setHash(Security::generateHash());

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
    $sendingTask->newsletterId = $newsletter->getId();
    $sendingTask->setSubscribers([$subscriber->getId()]);
    $sendingTask->updateProcessedSubscribers([$subscriber->getId()]);
    $this->sendingTask = $sendingTask->save();

    // build browser preview data
    $this->browserPreviewData = [
      'queue_id' => $sendingTask->queue()->id,
      'subscriber_id' => $subscriber->getId(),
      'newsletter_id' => $newsletter->getId(),
      'newsletter_hash' => $newsletter->getHash(),
      'subscriber_token' => $this->linkTokens->getToken($subscriber),
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

  public function testUsesEmptySubscriberWhenNotLoggedIn() {

    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, NewsletterEntity $newsletter, SubscriberEntity $subscriber = null, SendingQueueEntity $queue = null) {
        $this->assertNotNull($subscriber); // PHPStan
        verify($subscriber)->notNull();
        verify($subscriber->getId())->equals(0);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    unset($data['subscriber_id']);
    $viewInBrowserController->view($data);
  }

  public function testItSetsSubscriberToLoggedInWPUserWhenPreviewIsEnabled() {
    $subscriberId = $this->subscriber->getId();
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, NewsletterEntity $newsletter, SubscriberEntity $subscriber = null, SendingQueueEntity $queue = null) use ($subscriberId) {
        $this->assertNotNull($subscriber); // PHPStan
        verify($subscriber)->notNull();
        verify($subscriber->getId())->equals($subscriberId);
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
      'render' => Expected::once(function (bool $isPreview, NewsletterEntity $newsletter, SubscriberEntity $subscriber = null, SendingQueueEntity $queue = null) {
        $this->assertNotNull($queue); // PHPStan
        verify($queue)->notNull();
        verify($queue->getId())->equals($this->sendingTask->id);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    $data['queueId'] = $this->sendingTask->queue()->id;
    $viewInBrowserController->view($data);
  }

  public function testItGetsQueueByNewsletter() {
    $viewInBrowserRenderer = $this->make(ViewInBrowserRenderer::class, [
      'render' => Expected::once(function (bool $isPreview, NewsletterEntity $newsletter, SubscriberEntity $subscriber = null, SendingQueueEntity $queue = null) {
        $this->assertNotNull($queue); // PHPStan
        verify($queue)->notNull();
        verify($queue->getId())->equals($this->sendingTask->queue()->id);
      }),
    ]);

    $viewInBrowserController = $this->createController($viewInBrowserRenderer);

    $data = $this->browserPreviewData;
    $data['queueId'] = null;
    $viewInBrowserController->view($data);
  }

  public function _after() {
    parent::_after();
    // reset WP user role
    $wpUser = wp_get_current_user();
    $wpUser->add_role('administrator');
  }

  private function expectViewThrowsExceptionWithMessage(ViewInBrowserController $viewInBrowserController, array $data, string $message) {
    try {
      $viewInBrowserController->view($data);
      $this->fail("Expected 'InvalidArgumentException' with message '$message' was not thrown");
    } catch (\InvalidArgumentException $e) {
      verify($e->getMessage())->same($message);
    }
  }

  private function createController($viewInBrowserRenderer): ViewInBrowserController {
    return new ViewInBrowserController(
      $this->linkTokens,
      $this->newsletterUrl,
      $this->newslettersRepository,
      $viewInBrowserRenderer,
      $this->sendingQueuesRepository,
      $this->subscribersRepository
    );
  }
}
