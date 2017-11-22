<?php

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Util\Stub;
use MailPoet\Config\AccessControl;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\ViewInBrowser;

class ViewInBrowserTest extends \MailPoetTest {
  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->subscribers = array('processed' => array($subscriber->id));
    $this->queue = $queue->save();
    // build browser preview data
    $this->browser_preview_data = array(
      'queue_id' => $queue->id,
      'subscriber_id' => $subscriber->id,
      'newsletter_id' => $newsletter->id,
      'subscriber_token' => Subscriber::generateToken($subscriber->email),
      'preview' => false
    );
    // instantiate class
    $this->view_in_browser = new ViewInBrowser($this->browser_preview_data, new AccessControl());
  }

  function testItAbortsWhenBrowserPreviewDataIsMissing() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_abort' => Stub::exactly(2)
    ), $this);
    // newsletter ID is required
    $data = $this->browser_preview_data;
    unset($data['newsletter_id']);
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber token is required if subscriber is provided
    $data = $this->browser_preview_data;
    unset($data['subscriber_token']);
    $view_in_browser->_processBrowserPreviewData($data);
  }

  function testItAbortsWhenBrowserPreviewDataIsInvalid() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_abort' => Stub::exactly(3)
    ), $this);
    // newsletter ID is invalid
    $data = $this->browser_preview_data;
    $data['newsletter_id'] = 99;
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber token is invalid
    $data = $this->browser_preview_data;
    $data['subscriber_token'] = false;
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber token is invalid
    $data = $this->browser_preview_data;
    $data['subscriber_token'] = 'invalid';
    $view_in_browser->_processBrowserPreviewData($data);
    // subscriber has not received the newsletter
  }

  function testItFailsValidationWhenSubscriberTokenDoesNotMatch() {
    $subscriber = $this->subscriber;
    $subscriber->email = 'random@email.com';
    $subscriber->save();
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $subscriber,
        'newsletter' => $this->newsletter
      )
    );
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();
  }

  function testItFailsValidationWhenNewsletterIdIsProvidedButSubscriberDoesNotExist() {
    $data = (object)$this->browser_preview_data;
    $data->subscriber_id = false;
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();
  }

  function testItValidatesThatNewsletterExistsByCheckingHashFirst() {
    $newsletter_1 = $this->newsletter;
    $newsletter_2 = Newsletter::create();
    $newsletter_2->type = 'type';
    $newsletter_2 = $newsletter_2->save();
    $data = (object)$this->browser_preview_data;
    $data->newsletter_hash = $newsletter_2->hash;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result->newsletter->id)->equals($newsletter_2->id);
    $data->newsletter_hash = false;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result->newsletter->id)->equals($newsletter_1->id);
  }

  function testItFailsValidationWhenPreviewIsEnabledButNewsletterHashNotProvided() {
    $data = (object)$this->browser_preview_data;
    $data->newsletter_hash = false;
    $data->preview = true;
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();
  }

  function testItFailsValidationWhenSubscriberIsNotOnProcessedList() {
    $data = (object)$this->browser_preview_data;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result)->notEmpty();
    $queue = $this->queue;
    $queue->subscribers = array('processed' => array());
    $queue->save();
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result)->false();
  }

  function testItDoesNotRequireWpAdministratorToBeOnProcessedListWhenPreviewIsEnabled() {
    $view_in_browser = $this->view_in_browser;
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter
      )
    );
    $data->preview = true;

    // when WP user is not logged, false should be returned
    expect($view_in_browser->_validateBrowserPreviewData($data))->false();

    $wp_user = wp_set_current_user(0);
    // when WP user does not have 'manage options' permission, false should be returned
    $wp_user->remove_role('administrator');
    $view_in_browser->access_control = new AccessControl();
    expect($this->view_in_browser->_validateBrowserPreviewData($data))->false();

    // when WP has 'manage options' permission, data should be returned
    $wp_user->add_role('administrator');
    $view_in_browser->access_control = new AccessControl();
    expect($view_in_browser->_validateBrowserPreviewData($data))->equals($data);
  }

  function testItSetsSubscriberToLoggedInWPUserWhenPreviewIsEnabled() {
    $view_in_browser = $this->view_in_browser;
    $data = (object)array_merge(
      $this->browser_preview_data,
      array(
        'queue' => $this->queue,
        'subscriber' => null,
        'newsletter' => $this->newsletter
      )
    );
    $data->preview = true;
    wp_set_current_user(1);
    $view_in_browser->access_control = new AccessControl();
    $result = $view_in_browser->_validateBrowserPreviewData($data);
    expect($result->subscriber->id)->equals(1);
  }

  function testItGetsOrFindsQueueWhenItIsNotAWelcomeEmail() {
    $data = (object)$this->browser_preview_data;
    // queue will be found when not defined
    $data->queue_id = null;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result->queue->id)->equals($this->queue->id);
    // queue will be found when defined
    $data->queue_id = $this->queue->id;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result->queue->id)->equals($this->queue->id);
    // queue will not be found when it is a welcome email
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->save();
    $data->queue_id = null;
    $result = $this->view_in_browser->_validateBrowserPreviewData($data);
    expect($result->queue)->false();
  }

  function testItProcessesBrowserPreviewData() {
    $processed_data = $this->view_in_browser->_processBrowserPreviewData($this->browser_preview_data);
    expect($processed_data->queue->id)->equals($this->queue->id);
    expect($processed_data->subscriber->id)->equals($this->subscriber->id);
    expect($processed_data->newsletter->id)->equals($this->newsletter->id);
  }

  function testItReturnsViewActionResult() {
    $view_in_browser = Stub::make($this->view_in_browser, array(
      '_displayNewsletter' => Stub::exactly(1)
    ), $this);
    $view_in_browser->data = $view_in_browser->_processBrowserPreviewData($this->browser_preview_data);
    $view_in_browser->view();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    // reset WP user role
    $wp_user = wp_get_current_user();
    $wp_user->add_role('administrator');
  }
}