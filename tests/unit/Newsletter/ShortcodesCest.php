<?php

use MailPoet\Models\Subscriber;

class ShortcodesCest {
  public $rendered_newsletter;
  public $newsletter;
  public $subscriber;

  function __construct() {
    $this->wp_user = $this->_createWPUser();
    $this->subscriber = $this->_createSubscriber();
    $this->newsletter['subject'] = 'some subject';
    $this->rendered_newsletter = '
      Hello [user:displayname | default:member].
      Your first name is [user:firstname | default:First Name].
      Your last name is [user:lastname | default:Last Name].
      Thank you for subscribing with [user:email].
      We already have [user:count] users.

      There are [newsletter:total] posts on this blog.
      You are reading [newsletter:subject].
      The latest post on this blog is called [newsletter:post_title].
      The issue number of this newsletter is [newsletter:number].

      Date: [date:d].
      Ordinal date: [date:dordinal].
      Date text: [date:dtext].
      Month: [date:m].
      Month text: [date:mtext].
      Year: [date:y]

      You can usubscribe here: [global:unsubscribe].
      Manage your subscription here: [global:manage].
      View this newsletter in browser: [global:browser].';
    $this->shortcodes_object = new MailPoet\Newsletter\Shortcodes\Shortcodes(
      $this->rendered_newsletter,
      $this->newsletter,
      $this->subscriber
    );
  }

  function itCanProcessShortcodes() {
    $shortcodes = $this->shortcodes_object->extract();
    expect(count($shortcodes))->equals(18);
    $wp_user = get_userdata($this->wp_user);
    $wp_post_count = wp_count_posts();
    $wp_latest_post = wp_get_recent_posts(array('numberposts' => 1));
    $wp_latest_post = (isset($wp_latest_post)) ?
      $wp_latest_post[0]['post_title'] :
      false;
    $date = new \DateTime('now');
    $subscriber_count = Subscriber::count();
    $newsletter_with_replaced_shortcodes = $this->shortcodes_object->replace();
    expect($newsletter_with_replaced_shortcodes)->equals("
      Hello {$wp_user->user_login}.
      Your first name is {$this->subscriber->first_name}.
      Your last name is {$this->subscriber->last_name}.
      Thank you for subscribing with {$this->subscriber->email}.
      We already have {$subscriber_count} users.

      There are {$wp_post_count->publish} posts on this blog.
      You are reading {$this->newsletter['subject']}.
      The latest post on this blog is called {$wp_latest_post}.
      The issue number of this newsletter is [newsletter:number].

      Date: {$date->format('d')}.
      Ordinal date: {$date->format('dS')}.
      Date text: {$date->format('D')}.
      Month: {$date->format('m')}.
      Month text: {$date->format('F')}.
      Year: {$date->format('Y')}

      You can usubscribe here: [global:unsubscribe].
      Manage your subscription here: [global:manage].
      View this newsletter in browser: [global:browser].");
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
        'wp_user_id' => $this->wp_user
      )
    );
    $subscriber->save();
    return Subscriber::findOne($subscriber->id);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}