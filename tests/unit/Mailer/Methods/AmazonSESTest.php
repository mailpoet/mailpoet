<?php
namespace MailPoet\Test\Mailer\Methods;

use MailPoet\Mailer\Methods\AmazonSES;

class AmazonSESTest extends \MailPoetTest {
  function _before() {
    $this->settings = array(
      'method' => 'AmazonSES',
      'access_key' => getenv('WP_TEST_MAILER_AMAZON_ACCESS') ?
        getenv('WP_TEST_MAILER_AMAZON_ACCESS') :
        '1234567890',
      'secret_key' => getenv('WP_TEST_MAILER_AMAZON_SECRET') ?
        getenv('WP_TEST_MAILER_AMAZON_SECRET') :
        'abcdefghijk',
      'region' => getenv('WP_TEST_MAILER_AMAZON_REGION') ?
        getenv('WP_TEST_MAILER_AMAZON_REGION') :
        'us-west-2',
    );
    $this->sender = array(
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>'
    );
    $this->reply_to = array(
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>'
    );
    $this->return_path = 'bounce@mailpoet.com';
    $this->mailer = new AmazonSES(
      $this->settings['region'],
      $this->settings['access_key'],
      $this->settings['secret_key'],
      $this->sender,
      $this->reply_to,
      $this->return_path
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing AmazonSES',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
    $this->extra_params = array(
      'unsubscribe_url' => 'http://www.mailpoet.com'
    );
  }

  function testItsConstructorWorks() {
    expect($this->mailer->aws_endpoint)
      ->equals(
        sprintf('email.%s.amazonaws.com', $this->settings['region'])
      );
    expect($this->mailer->url)
      ->equals(
        sprintf('https://email.%s.amazonaws.com', $this->settings['region'])
      );
    expect(preg_match('!^\d{8}T\d{6}Z$!', $this->mailer->date))->equals(1);
    expect(preg_match('!^\d{8}$!', $this->mailer->date_without_time))->equals(1);
  }

  function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new AmazonSES(
      $this->settings['region'],
      $this->settings['access_key'],
      $this->settings['secret_key'],
      $this->sender,
      $this->reply_to,
      $return_path = false
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItChecksForValidRegion() {
    try {
      $mailer = new AmazonSES(
        'random_region',
        $this->settings['access_key'],
        $this->settings['secret_key'],
        $this->sender,
        $this->reply_to,
        $this->return_path
      );
      $this->fail('Unsupported region exception was not thrown');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Unsupported Amazon SES region');
    }
  }

  function testItCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['Action'])->equals('SendRawEmail');
    expect($body['Version'])->equals('2010-12-01');
    expect($body['Source'])->equals($this->sender['from_name_email']);
    expect($body['RawMessage.Data'])
      ->equals($this->mailer->encodeMessage($this->mailer->message));
  }

  function testItCanCreateMessage() {
    $message = $this->mailer
      ->createMessage($this->newsletter, $this->subscriber, $this->extra_params);
    expect($message->getTo())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => 'Recipient'));
    expect($message->getFrom())
      ->equals(array($this->sender['from_email'] => $this->sender['from_name']));
    expect($message->getSender())
      ->equals(array($this->sender['from_email'] => null));
    expect($message->getReplyTo())
      ->equals(array($this->reply_to['reply_to_email'] => $this->reply_to['reply_to_name']));
    expect($message->getSubject())
      ->equals($this->newsletter['subject']);
    expect($message->getBody())
      ->equals($this->newsletter['body']['html']);
    expect($message->getChildren()[0]->getContentType())
      ->equals('text/plain');
    expect($message->getHeaders()->get('List-Unsubscribe')->getValue())
      ->equals('<' . $this->extra_params['unsubscribe_url'] . '>');
  }

  function testItCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    // preserve the original message
    $raw_message = $this->mailer->encodeMessage($this->mailer->message);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    // substitute the message to synchronize hashes
    $body['RawMessage.Data'] = $raw_message;
    $body = array_map('urlencode', $body);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.1');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Host'])->equals($this->mailer->aws_endpoint);
    expect($request['headers']['Authorization'])
      ->equals($this->mailer->signRequest($body));
    expect($request['headers']['X-Amz-Date'])->equals($this->mailer->date);
    expect($request['body'])->equals(urldecode(http_build_query($body)));
  }

  function testItCanCreateCanonicalRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $canonicalRequest = explode(
      "\n",
      $this->mailer->getCanonicalRequest($body)
    );
    expect($canonicalRequest)
      ->equals(
        array(
          'POST',
          '/',
          '',
          'host:' . $this->mailer->aws_endpoint,
          'x-amz-date:' . $this->mailer->date,
          '',
          'host;x-amz-date',
          hash($this->mailer->hash_algorithm,
               urldecode(http_build_query($body))
          )
        )
      );
  }

  function testItCanCreateCredentialScope() {
    $credentialScope = $this->mailer->getCredentialScope();
    expect($credentialScope)
      ->equals(
        $this->mailer->date_without_time . '/' .
        $this->mailer->aws_region . '/' .
        $this->mailer->aws_service . '/' .
        $this->mailer->aws_termination_string
      );
  }

  function testItCanCreateStringToSign() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $credentialScope = $this->mailer->getCredentialScope();
    $canonicalRequest = $this->mailer->getCanonicalRequest($body);
    $stringToSing = $this->mailer->createStringToSign(
      $credentialScope,
      $canonicalRequest
    );
    $stringToSing = explode("\n", $stringToSing);
    expect($stringToSing)
      ->equals(
        array(
          $this->mailer->aws_signing_algorithm,
          $this->mailer->date,
          $credentialScope,
          hash($this->mailer->hash_algorithm, $canonicalRequest)
        )
      );
  }

  function testItCanSignRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $signedRequest = $this->mailer->signRequest($body);
    expect($signedRequest)
      ->contains(
        $this->mailer->aws_signing_algorithm . ' Credential=' .
        $this->mailer->aws_access_key . '/' .
        $this->mailer->getCredentialScope() . ', ' .
        'SignedHeaders=host;x-amz-date, Signature='
      );
    expect(preg_match('!Signature=[A-Fa-f0-9]{64}$!', $signedRequest))
      ->equals(1);
  }

  function testItCannotSendWithoutProperAccessKey() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->aws_access_key = 'somekey';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItCatchesSendingErrors() {
    $invalid_subscriber = 'john.@doe.com';
    $result = $this->mailer->send(
      $this->newsletter,
      $invalid_subscriber
    );
    expect($result['response'])->false();
    expect($result['error_message'])->contains('does not comply with RFC 2822');
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}
