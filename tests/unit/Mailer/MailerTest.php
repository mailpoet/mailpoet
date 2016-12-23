<?php
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

class MailerTest extends MailPoetTest {
  function _before() {
    $this->available_mailer_methods = array(
      array(
        'method' => 'AmazonSES',
        'region' => 'us-west-2',
        'access_key' => '1234567890',
        'secret_key' => 'abcdefghijk',
      ),
      array(
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'abcdefghijk'
      ),
      array(
        'method' => 'SendGrid',
        'api_key' => 'abcdefghijk'
      ),
      array(
        'method' => 'PHPMail'
      ),
      array(
        'method' => 'SMTP',
        'host' => 'example.com',
        'port' => 25,
        'authentication' => true,
        'login' => 'username',
        'password' => 'password',
        'encryption' => 'tls',
      )
    );
    $this->sender = array(
      'name' => 'Sender',
      'address' => 'staff@mailinator.com'
    );
    $this->reply_to = array(
      'name' => 'Reply To',
      'address' => 'staff@mailinator.com'
    );
    $this->return_path = 'bounce@test.com';
    $this->mailer = array(
      'method' => 'MailPoet',
      'mailpoet_api_key' => getenv('WP_TEST_MAILER_MAILPOET_API') ?
        getenv('WP_TEST_MAILER_MAILPOET_API') :
        '1234567890'
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing Mailer',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function testItRequiresMailerMethod() {
    // reset mta settings so that we have no default mailer
    Setting::setValue('mta', null);
    try {
      $mailer = new Mailer();
      $this->fail('Mailer did not throw an exception');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Mailer is not configured');
    }
  }

  function testItRequiresSender() {
    try {
      $mailer = new Mailer($mailer = $this->mailer);
      $this->fail('Mailer did not throw an exception');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Sender name and email are not configured');
    }
  }

  function testItCanConstruct() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to, $this->return_path);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->sender['from_email'])->equals($this->sender['address']);
    expect($mailer->reply_to['reply_to_name'])->equals($this->reply_to['name']);
    expect($mailer->reply_to['reply_to_email'])->equals($this->reply_to['address']);
    expect($mailer->return_path)->equals($this->return_path);
  }

  function testItCanBuildKnownMailerInstances() {
    foreach($this->available_mailer_methods as $method) {
      $mailer = new Mailer($method, $this->sender);
      $mailer->buildMailer();
      expect(get_class($mailer->mailer_instance))
        ->equals('MailPoet\Mailer\Methods\\' . $method['method']);
    }
  }

  function testItThrowsUnknownMailerException() {
    try {
      $mailer = new Mailer(array('method' => 'Unknown'), $this->sender);
      $this->fail('Mailer did not throw an exception');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Mailing method does not exist');
    }
  }

  function testItSetsReplyToAddressWhenOnlyNameIsAvailable() {
    $reply_to = array('name' => 'test');
    $mailer = new Mailer($this->mailer, $this->sender, $reply_to);
    $reply_to = $mailer->getReplyToNameAndAddress();
    expect($reply_to['reply_to_email'])->equals($this->sender['address']);
  }

  function testItGetsReturnPathAddress() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    $return_path = $mailer->getReturnPathAddress('bounce@test.com');
    expect($return_path)->equals('bounce@test.com');
    Setting::setValue('bounce', array('address' => 'settngs_bounce@test.com'));
    $return_path = $mailer->getReturnPathAddress($return_path = false);
    expect($return_path)->equals('settngs_bounce@test.com');
  }

  function testItCanTransformSubscriber() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    expect($mailer->formatSubscriberNameAndEmailAddress('test@email.com'))
      ->equals('test@email.com');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      array(
        'email' => 'test@email.com'
      ))
    )->equals('test@email.com');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      array(
        'first_name' => 'First',
        'email' => 'test@email.com'
      ))
    )->equals('First <test@email.com>');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      array(
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('Last <test@email.com>');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      array(
        'first_name' => 'First',
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('First Last <test@email.com>');
  }

  function testItCanConvertNonASCIIEmailAddressString() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->reply_to['reply_to_name'])->equals($this->reply_to['name']);
    $sender = array(
      'name' => 'Sender Außergewöhnlichen тест системы',
      'address' => 'staff@mailinator.com'
    );
    $reply_to = array(
      'name' => 'Reply-To Außergewöhnlichen тест системы',
      'address' => 'staff@mailinator.com'
    );
    $mailer = new Mailer($this->mailer, $sender, $reply_to);
    expect($mailer->sender['from_name'])
      ->equals(sprintf('=?utf-8?B?%s?=', base64_encode($sender['name'])));
    expect($mailer->reply_to['reply_to_name'])
      ->equals(sprintf('=?utf-8?B?%s?=', base64_encode($reply_to['name'])));
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    $result = $mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->true();
  }
}