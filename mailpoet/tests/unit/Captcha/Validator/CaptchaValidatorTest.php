<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Captcha\CaptchaPhrase;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaValidatorTest extends \MailPoetUnitTest {


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
    $urlFactory = Stub::makeEmpty(CaptchaUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase,
      ],
      $this
    );

    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new CaptchaValidator(
      $urlFactory,
      $captchaPhrase,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => '123',
    ];

    verify($testee->validate($data))->true();
  }

  /**
   * @dataProvider dataForTestSomeRolesCanBypassCaptcha
   */
  public function testSomeRolesCanBypassCaptcha($wp) {
    $phrase = 'abc';
    $urlFactory = Stub::makeEmpty(CaptchaUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => 'something.else.' . $phrase,
      ],
      $this
    );

    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new CaptchaValidator(
      $urlFactory,
      $captchaPhrase,
      $wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => '123',
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
    $urlFactory = Stub::makeEmpty(CaptchaUrlFactory::class);
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
    $testee = new CaptchaValidator(
      $urlFactory,
      $captchaPhrase,
      $wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => '123',
    ];

    verify($testee->validate($data))->true();
  }

  public function testNoCaptchaFound() {
    $phrase = 'abc';
    $newUrl = 'https://example.com';
    $captchaController = Stub::make(
      CaptchaUrlFactory::class,
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
    $testee = new CaptchaValidator(
      $captchaController,
      $captchaPhrase,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => '123',
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
    $urlFactory = Stub::makeEmpty(CaptchaUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase . 'd',
        'createPhrase' => 'null',
      ],
      $this
    );

    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new CaptchaValidator(
      $urlFactory,
      $captchaPhrase,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => '123',
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
    $captchaController = Stub::make(
      CaptchaUrlFactory::class,
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

    $subscriberIpRepository = Stub::makeEmpty(SubscriberIPsRepository::class);
    $subscriberRepository = Stub::makeEmpty(SubscribersRepository::class);
    $testee = new CaptchaValidator(
      $captchaController,
      $captchaPhrase,
      $this->wp,
      $subscriberIpRepository,
      $subscriberRepository
    );

    $data = [
      'captcha' => '',
      'captcha_session_id' => '123',
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
