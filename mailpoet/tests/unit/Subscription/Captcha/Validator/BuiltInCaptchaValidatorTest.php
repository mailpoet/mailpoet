<?php declare(strict_types = 1);

namespace MailPoet\Subscription\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Captcha\CaptchaPhrase;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class BuiltInCaptchaValidatorTest extends \MailPoetUnitTest {


  /**
   * @var WPFunctions
   */
  private $wp;

  public function _before() {
    $this->wp = Stub::make(
      WPFunctions::class,
      [
        'isUserLoggedIn' => false,
        'applyFilters' => function($filter, $value) {
          return $value;
        },
        '__' => function($string) { return $string;
        },
      ],
      $this
    );
  }

  public function testHashIsValid() {

    $phrase = 'abc';
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase,
      ],
      $this
    );
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
      $subscriptionUrlFactory,
      $captchaPhrase,
      $captchaSession,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
    ];
    verify($testee->validate($data))->true();

  }

  /**
   * @dataProvider dataForTestSomeRolesCanBypassCaptcha
   */
  public function testSomeRolesCanBypassCaptcha($wp) {
    $phrase = 'abc';
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => 'something.else.' . $phrase,
      ],
      $this
    );
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);

    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
      $subscriptionUrlFactory,
      $captchaPhrase,
      $captchaSession,
      $wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
    ];
    verify($testee->validate($data))->true();
  }

  public function dataForTestSomeRolesCanBypassCaptcha() {
    return [
      'administrator_bypass_captcha' => [
        'wp' => Stub::make(
          WPFunctions::class,
          [
            'isUserLoggedIn' => true,
            'applyFilters' => function($filter, $value) {
              return $value;
            },
            '__' => function($string) { return $string;
            },
            'wpGetCurrentUser' => (object)[
              'roles' => ['administrator'],
            ],
          ],
          $this
        ),
      ],
      'editor_bypass_captcha' => [
        'wp' => Stub::make(
          WPFunctions::class,
          [
            'isUserLoggedIn' => true,
            'applyFilters' => function($filter, $value) {
              return $value;
            },
            '__' => function($string) { return $string;
            },
            'wpGetCurrentUser' => (object)[
              'roles' => ['editor'],
            ],
          ],
          $this
        ),
      ],
      'custom_role_can_bypass_with_filter' => [
        'wp' => Stub::make(
          WPFunctions::class,
          [
            'isUserLoggedIn' => true,
            'applyFilters' => function($filter, $value) {
              if ($filter === 'mailpoet_subscription_captcha_exclude_roles') {
                return ['custom-role'];
              }
              return $value;
            },
            '__' => function($string) { return $string;
            },
            'wpGetCurrentUser' => (object)[
              'roles' => ['custom-role'],
            ],
          ],
          $this
        ),
      ],
    ];
  }

  public function testEditorsBypassCaptcha() {
    $phrase = 'abc';
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $captchaPhrase = Stub::make(
    CaptchaPhrase::class,
    [
      'getPhrase' => 'something.else.' . $phrase,
    ],
    $this
    );
    $currentUser = (object)[
    'roles' => ['editor'],
    ];
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $wp = Stub::make(
    WPFunctions::class,
    [
      'isUserLoggedIn' => true,
      'applyFilters' => function($filter, $value) {
        return $value;
      },
      '__' => function($string) { return $string;
      },
      'wpGetCurrentUser' => $currentUser,
    ],
    $this
    );
    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
    $subscriptionUrlFactory,
    $captchaPhrase,
    $captchaSession,
    $wp,
    $subscriberIpRepository,
    $subscriberRepository
    );

    $data = [
    'captcha' => $phrase,
    ];
    verify($testee->validate($data))->true();

  }

  public function testNoCaptchaFound() {

    $phrase = 'abc';
    $newUrl = 'https://example.com';
    $subscriptionUrlFactory = Stub::make(
      SubscriptionUrlFactory::class,
      [
        'getCaptchaUrl' => $newUrl,
      ],
      $this
    );
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => null,
      ],
      $this
    );
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
      $subscriptionUrlFactory,
      $captchaPhrase,
      $captchaSession,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
    ];
    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      verify($error->getMessage())->equals('Please regenerate the CAPTCHA.');
      verify($error->getMeta()['redirect_url'])->equals($newUrl);
    }
    verify($error)->instanceOf(ValidationError::class);
  }

  public function testCaptchaMissmatch() {

    $phrase = 'abc';
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase . 'd',
        'resetPhrase' => null,
      ],
      $this
    );
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
      $subscriptionUrlFactory,
      $captchaPhrase,
      $captchaSession,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
    ];
    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      verify($error->getMessage())->equals('The characters entered do not match with the previous CAPTCHA.');
      verify($error->getMeta()['refresh_captcha'])->true();
    }
    verify($error)->instanceOf(ValidationError::class);
  }

  public function testNoCaptchaIsSend() {

    $phrase = 'abc';
    $newUrl = 'https://example.com';
    $subscriptionUrlFactory = Stub::make(
      SubscriptionUrlFactory::class,
      [
        'getCaptchaUrl' => $newUrl,
      ],
      $this
    );
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase,
      ],
      $this
    );
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new BuiltInCaptchaValidator(
      $subscriptionUrlFactory,
      $captchaPhrase,
      $captchaSession,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => '',
    ];
    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      verify($error->getMessage())->equals('Please fill in the CAPTCHA.');
      verify($error->getMeta()['redirect_url'])->equals($newUrl);
    }
    verify($error)->instanceOf(ValidationError::class);
  }
}
