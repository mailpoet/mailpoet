<?php

use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Config\Populator;
use MailPoet\Subscription\Url as SubscriptionUrl;

class ShortcodesTest extends MailPoetTest {
  public $rendered_newsletter;
  public $newsletter;
  public $subscriber;

  function _before() {
    $populator = new Populator();
    $populator->up();
    $this->wp_user = $this->_createWPUser();
    $this->subscriber = $this->_createSubscriber();
    $this->newsletter = array(
      'subject' => 'some subject',
      'type' => 'notification',
      'id' => 2
    );
    $post = get_post(1);
    $this->latest_post_title = $post->post_title;
    $this->rendered_newsletter = '
      Hello [user:displayname | default:member].
      Your first name is [user:firstname | default:First Name].
      Your last name is [user:lastname | default:Last Name].
      Thank you for subscribing with [user:email].
      We already have [user:count] users.

      <h1 data-post-id="1">some post</h1>
      <h1 data-post-id="2">another post</h1>

      There are [newsletter:total] posts in this newsletter.
      You are reading [newsletter:subject].
      The latest post in this newsletter is called [newsletter:post_title].
      The issue number of this newsletter is [newsletter:number].

      Date: [date:d].
      Ordinal date: [date:dordinal].
      Date text: [date:dtext].
      Month: [date:m].
      Month text: [date:mtext].
      Year: [date:y]

      You can unsubscribe here: [subscription:unsubscribe_url].
      Manage your subscription here: [subscription:manage_url].
      View this newsletter in browser: [newsletter:view_in_browser_url].';
    $this->shortcodes_object = new MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->newsletter,
      $this->subscriber
    );
  }

  function testItCanExtractShortcodes() {
    $shortcodes = $this->shortcodes_object->extract($this->rendered_newsletter);
    expect(count($shortcodes))->equals(18);
  }

  function testItCanProcessShortcodes() {
    $wp_user = get_userdata($this->wp_user);

    $queue = SendingQueue::create();
    $queue->newsletter_id = $this->newsletter['id'];
    $queue->save();
    $issue_number = 1;

    $number_of_posts = 2;

    $date = new \DateTime('now');
    $subscriber_count = Subscriber::count();
    $newsletter_with_replaced_shortcodes = $this->shortcodes_object->replace(
      $this->rendered_newsletter
    );

    $unsubscribe_url = SubscriptionUrl::getUnsubscribeUrl($this->subscriber);
    $manage_url = SubscriptionUrl::getManageUrl($this->subscriber);
    $view_in_browser_url = '#TODO';

    expect($newsletter_with_replaced_shortcodes)->equals("
      Hello {$wp_user->user_login}.
      Your first name is {$this->subscriber->first_name}.
      Your last name is {$this->subscriber->last_name}.
      Thank you for subscribing with {$this->subscriber->email}.
      We already have {$subscriber_count} users.

      <h1 data-post-id=\"1\">some post</h1>
      <h1 data-post-id=\"2\">another post</h1>

      There are {$number_of_posts} posts in this newsletter.
      You are reading {$this->newsletter['subject']}.
      The latest post in this newsletter is called {$this->latest_post_title}.
      The issue number of this newsletter is {$issue_number}.

      Date: {$date->format('d')}.
      Ordinal date: {$date->format('dS')}.
      Date text: {$date->format('D')}.
      Month: {$date->format('m')}.
      Month text: {$date->format('F')}.
      Year: {$date->format('Y')}

      You can unsubscribe here: {$unsubscribe_url}.
      Manage your subscription here: {$manage_url}.
      View this newsletter in browser: {$view_in_browser_url}.");
  }

  function _createWPUser() {
    $wp_user = wp_create_user('phoenix_test_user', 'pass', 'phoenix@test.com');
    if(is_wp_error($wp_user)) {
      $wp_user = get_user_by('login', 'phoenix_test_user');
      $wp_user = $wp_user->ID;
    }
    return $wp_user;
  }

  function _createSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        'email' => 'mister@trump.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'wp_user_id' => $this->wp_user
      )
    );
    $subscriber->save();
    return Subscriber::findOne($subscriber->id);
  }

  function _after() {
    Subscriber::deleteMany();
  }
}