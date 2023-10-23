<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\AmazonSES;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use MailPoet\WP\Functions as WPFunctions;

class AmazonSESTest extends \MailPoetTest {
  public $extraParams;
  public $newsletter;
  public $subscriber;
  /** @var AmazonSES */
  public $mailer;
  public $returnPath;
  public $replyTo;
  public $sender;
  public $settings;

  public function _before() {
    parent::_before();
    $this->settings = [
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
    ];
    $this->sender = [
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>',
    ];
    $this->replyTo = [
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>',
    ];
    $this->returnPath = 'bounce@mailpoet.com';
    $this->mailer = new AmazonSES(
      $this->settings['region'],
      $this->settings['access_key'],
      $this->settings['secret_key'],
      $this->sender,
      $this->replyTo,
      $this->returnPath,
      new AmazonSESMapper(),
      new WPFunctions()
    );
    $this->subscriber = 'Recipient <blackhole@mailpoet.com>';
    $this->newsletter = [
      'subject' => 'testing AmazonSES … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extraParams = [
      'unsubscribe_url' => 'https://www.mailpoet.com',
    ];
  }

  public function testItsConstructorWorks() {
    verify($this->mailer->awsEndpoint)
      ->equals(
        sprintf('email.%s.amazonaws.com', $this->settings['region'])
      );
    verify($this->mailer->url)
      ->equals(
        sprintf('https://email.%s.amazonaws.com', $this->settings['region'])
      );
    verify(preg_match('!^\d{8}T\d{6}Z$!', $this->mailer->date))->equals(1);
    verify(preg_match('!^\d{8}$!', $this->mailer->dateWithoutTime))->equals(1);
  }

  public function testItChecksForValidRegion() {
    try {
      $mailer = new AmazonSES(
        'random_region',
        $this->settings['access_key'],
        $this->settings['secret_key'],
        $this->sender,
        $this->replyTo,
        $this->returnPath,
        new AmazonSESMapper(),
        new WPFunctions()
      );
      $this->fail('Unsupported region exception was not thrown');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals('Unsupported Amazon SES region');
    }
  }

  public function testItCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    verify($body['Action'])->equals('SendRawEmail');
    verify($body['Version'])->equals('2010-12-01');
    verify($body['Source'])->equals($this->sender['from_name_email']);
    verify($body['RawMessage.Data'])
      ->equals($this->mailer->encodeMessage($this->mailer->rawMessage));
  }

  public function testItCanCreateMessage() {
    $mailer = $this->mailer->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extraParams);
    verify($mailer->CharSet)->equals('UTF-8'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getToAddresses())->equals([[
      'blackhole@mailpoet.com',
      'Recipient',
    ]]);
    verify($mailer->getAllRecipientAddresses())->equals(['blackhole@mailpoet.com' => true]);
    verify($mailer->From)->equals($this->sender['from_email']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->FromName)->equals($this->sender['from_name']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getReplyToAddresses())->equals([
      $this->replyTo['reply_to_email'] => [
        $this->replyTo['reply_to_email'],
        $this->replyTo['reply_to_name'],
      ],
    ]);
    verify($mailer->Sender)->equals($this->returnPath); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->ContentType)->equals('text/html'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Subject)->equals($this->newsletter['subject']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Body)->equals($this->newsletter['body']['html']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->AltBody)->equals($this->newsletter['body']['text']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getCustomHeaders())->equals([[
      'List-Unsubscribe',
      '<https://www.mailpoet.com>',
    ]]);
  }

  public function testItCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    // preserve the original message
    $rawMessage = $this->mailer->encodeMessage($this->mailer->rawMessage);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    // substitute the message to synchronize hashes
    $body['RawMessage.Data'] = $rawMessage;
    $body = array_map('urlencode', $body);
    verify($request['timeout'])->equals(10);
    verify($request['httpversion'])->equals('1.1');
    verify($request['method'])->equals('POST');
    verify($request['headers']['Host'])->equals($this->mailer->awsEndpoint);
    verify($request['headers']['Authorization'])
      ->equals($this->mailer->signRequest($body));
    verify($request['headers']['X-Amz-Date'])->equals($this->mailer->date);
    verify($request['body'])->equals(urldecode(http_build_query($body)));
  }

  public function testItCanCreateCanonicalRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $canonicalRequest = explode(
      "\n",
      $this->mailer->getCanonicalRequest($body)
    );
    verify($canonicalRequest)
      ->equals(
        [
          'POST',
          '/',
          '',
          'host:' . $this->mailer->awsEndpoint,
          'x-amz-date:' . $this->mailer->date,
          '',
          'host;x-amz-date',
          hash($this->mailer->hashAlgorithm,
               urldecode(http_build_query($body))
          ),
        ]
      );
  }

  public function testItCanCreateCredentialScope() {
    $credentialScope = $this->mailer->getCredentialScope();
    verify($credentialScope)
      ->equals(
        $this->mailer->dateWithoutTime . '/' .
        $this->mailer->awsRegion . '/' .
        $this->mailer->awsService . '/' .
        $this->mailer->awsTerminationString
      );
  }

  public function testItCanCreateStringToSign() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $credentialScope = $this->mailer->getCredentialScope();
    $canonicalRequest = $this->mailer->getCanonicalRequest($body);
    $stringToSing = $this->mailer->createStringToSign(
      $credentialScope,
      $canonicalRequest
    );
    $stringToSing = explode("\n", $stringToSing);
    verify($stringToSing)
      ->equals(
        [
          $this->mailer->awsSigningAlgorithm,
          $this->mailer->date,
          $credentialScope,
          hash($this->mailer->hashAlgorithm, $canonicalRequest),
        ]
      );
  }

  public function testItCanSignRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $signedRequest = $this->mailer->signRequest($body);
    verify($signedRequest)
      ->stringContainsString(
        $this->mailer->awsSigningAlgorithm . ' Credential=' .
        $this->mailer->awsAccessKey . '/' .
        $this->mailer->getCredentialScope() . ', ' .
        'SignedHeaders=host;x-amz-date, Signature='
      );
    verify(preg_match('!Signature=[A-Fa-f0-9]{64}$!', $signedRequest))
      ->equals(1);
  }

  public function testItCannotSendWithoutProperAccessKey() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->mailer->awsAccessKey = 'somekey';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    verify($result['response'])->false();
  }

  public function testItCatchesSendingErrors() {
    $this->mailer->sender['from_name_email'] = 'invalid';
    $result = $this->mailer->send($this->newsletter, 'test@example.com');
    verify($result['response'])->false();
    verify($result['error'])->instanceOf(MailerError::class);
    verify($result['error']->getMessage())->stringContainsString("Missing final '@domain'");
  }

  public function testItChecksBlacklistBeforeSending() {
    $blacklistedSubscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'errorMapper' => new AmazonSESMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklistedSubscriber
    );
    verify($result['response'])->false();
    verify($result['error'])->instanceOf(MailerError::class);
    verify($result['error']->getMessage())->stringContainsString('AmazonSES has returned an unknown error.');
  }

  public function testItCanSend() {
    $mockedWp = $this->createMock(WPFunctions::class);
    /**
     * We don't want to send a real email through AmazonSES, thus mocking
     */
    $mockedWp->method('wpRemotePost')
      ->willReturn(['response' => true]);
    $mockedWp->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);

    $mailer = new AmazonSES(
      $this->settings['region'],
      $this->settings['access_key'],
      $this->settings['secret_key'],
      $this->sender,
      $this->replyTo,
      $this->returnPath,
      new AmazonSESMapper(),
      $mockedWp
    );
    $result = $mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    verify($result['response'])->true();
  }
}
