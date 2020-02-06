<?php

namespace MailPoet\Newsletter\ViewInBrowser;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\AccessControl;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\ViewInBrowser\ViewInBrowserRenderer as NewsletterViewInBrowser;
use MailPoet\Router\Endpoints\ViewInBrowser;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions;
use MailPoetVendor\Idiorm\ORM;

class ViewInBrowserControllerTest extends \MailPoetTest {
  public $viewInBrowser;
  public $browserPreviewData;
  public $queue;
  public $subscriber;
  public $newsletter;

  public function _before() {
    parent::_before();
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->firstName = 'First';
    $subscriber->lastName = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    $linkTokens = new LinkTokens;
    // build browser preview data
    $this->browserPreviewData = [
      'queue_id' => $queue->id,
      'subscriber_id' => $subscriber->id,
      'newsletter_id' => $newsletter->id,
      'newsletter_hash' => $newsletter->hash,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'preview' => false,
    ];
    // instantiate class
    $this->viewInBrowser = new ViewInBrowser(new AccessControl(), new LinkTokens(), $this->diContainer->get(NewsletterViewInBrowser::class));
  }

  public function testItAbortsWhenBrowserPreviewDataIsMissing() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      'linkTokens' => $this->make(LinkTokens::class, [
        'verifyToken' => true,
      ]),
      '_abort' => Expected::exactly(2),
    ]);

    // newsletter ID is required
    $data = $this->browserPreviewData;
    unset($data['newsletter_id']);
    $viewInBrowser->view($data);

    // TODO: newsletter hash is required

    // subscriber token is required if subscriber is provided
    $data = $this->browserPreviewData;
    unset($data['subscriber_token']);
    $viewInBrowser->view($data);
  }

  public function testItAbortsWhenBrowserPreviewDataIsInvalid() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      'linkTokens' => new LinkTokens,
      '_abort' => Expected::exactly(3),
    ]);

    // newsletter ID is invalid
    $data = $this->browserPreviewData;
    $data['newsletter_id'] = 99;
    $viewInBrowser->view($data);

    // subscriber token is invalid
    $data = $this->browserPreviewData;
    $data['subscriber_token'] = false;
    $viewInBrowser->view($data);

    // subscriber token is invalid
    $data = $this->browserPreviewData;
    $data['subscriber_token'] = 'invalid';
    $viewInBrowser->view($data);
    // subscriber has not received the newsletter
  }

  public function testItAbortsWhenSubscriberTokenDoesNotMatch() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      'linkTokens' => new LinkTokens,
      '_abort' => Expected::once(),
    ]);

    $subscriber = $this->subscriber;
    $subscriber->email = 'random@email.com';
    $subscriber->save();
    $data = array_merge(
      $this->browserPreviewData,
      [
        'queue' => $this->queue,
        'subscriber' => $subscriber,
        'newsletter' => $this->newsletter,
        'subscriber_token' => 'somewrongtoken',
      ]
    );
    $viewInBrowser->view($data);
  }

  public function testItAbortsWhenNewsletterHashIsInvalid() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      '_abort' => Expected::once(),
    ]);
    $data = $this->browserPreviewData;
    $data['newsletter_hash'] = 'invalid-hash';
    $viewInBrowser->view($data);
  }

  public function testItAbortsWhenSubscriberIsNotOnProcessedList() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      'linkTokens' => $this->make(LinkTokens::class, [
        'verifyToken' => true,
      ]),
      'newsletterViewInBrowser' => $this->make(NewsletterViewInBrowser::class, [
        'view' => Expected::once(),
      ]),
      '_abort' => Expected::once(),
      '_displayNewsletter' => Expected::once(),
    ]);
    $data = $this->browserPreviewData;
    $viewInBrowser->view($data);

    $queue = $this->queue;
    $queue->setSubscribers([]);
    $queue->updateProcessedSubscribers([]);
    $queue->save();
    $viewInBrowser->view($data);
  }

  public function testItDoesNotRequireWpAdministratorToBeOnProcessedListWhenPreviewIsEnabled() {
    $viewInBrowser = $this->make($this->viewInBrowser, [
      'accessControl' => new AccessControl(),
      'linkTokens' => $this->make(LinkTokens::class, [
        'verifyToken' => true,
      ]),
      'newsletterViewInBrowser' => $this->make(NewsletterViewInBrowser::class, [
        'view' => Expected::exactly(3),
      ]),
      '_abort' => Expected::once(),
      '_displayNewsletter' => Expected::exactly(3),
    ]);

    $data = array_merge(
      $this->browserPreviewData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $data['preview'] = true;

    // when WP user is not logged, abort should be called
    $viewInBrowser->view($data);

    $wpUser = wp_set_current_user(0);
    // when WP user does not have 'manage options' permission, false should be returned
    $wpUser->remove_role('administrator');
    //$viewInBrowser = new ViewInBrowser(new AccessControl(), SettingsController::getInstance(), new LinkTokens(), new Emoji());
    $viewInBrowser->view($data);

    // when WP has 'manage options' permission, data should be returned
    $wpUser->add_role('administrator');
    //$viewInBrowser = new ViewInBrowser(new AccessControl(), SettingsController::getInstance(), new LinkTokens(), new Emoji());
    $viewInBrowser->view($data);
  }

  public function testItSetsSubscriberToLoggedInWPUserWhenPreviewIsEnabled() {
    $viewInBrowser = $this->viewInBrowser;
    $data = array_merge(
      $this->browserPreviewData,
      [
        'queue' => $this->queue,
        'subscriber' => null,
        'newsletter' => $this->newsletter,
      ]
    );
    $data->preview = true;
    wp_set_current_user(1);
    $viewInBrowser = new ViewInBrowser(new AccessControl(), SettingsController::getInstance(), new LinkTokens(), new Emoji());
    $result = $viewInBrowser->_validateBrowserPreviewData($data);
    expect($result->subscriber->id)->equals(1);
  }

  public function testItGetsOrFindsQueueWhenItIsNotAWelcomeEmail() {
    $data = $this->browserPreviewData;
    // queue will be found when not defined
    $data->queueId = null;
    $result = $this->viewInBrowser->_validateBrowserPreviewData($data);
    expect($result->queue->id)->equals($this->queue->id);
    // queue will be found when defined
    $data->queueId = $this->queue->id;
    $result = $this->viewInBrowser->_validateBrowserPreviewData($data);
    expect($result->queue->id)->equals($this->queue->id);
    // queue will not be found when it is a welcome email
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->save();
    $data->queueId = null;
    $result = $this->viewInBrowser->_validateBrowserPreviewData($data);
    expect($result->queue)->false();
  }

  public function testItProcessesBrowserPreviewData() {
    $processedData = $this->viewInBrowser->_processBrowserPreviewData($this->browserPreviewData);
    expect($processedData->queue->id)->equals($this->queue->id);
    expect($processedData->subscriber->id)->equals($this->subscriber->id);
    expect($processedData->newsletter->id)->equals($this->newsletter->id);
  }

  public function testItReturnsViewActionResult() {
    $viewInBrowser = Stub::make($this->viewInBrowser, [
      'linkTokens' => new LinkTokens,
      '_displayNewsletter' => Expected::exactly(1),
      'settings' => SettingsController::getInstance(),
      'emoji' => new Emoji(),
    ], $this);
    $viewInBrowser->view($this->browserPreviewData);
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
}
