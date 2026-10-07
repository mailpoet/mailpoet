<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Captcha\CaptchaPhrase;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaSessionLimitException;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaValidatorTest extends \MailPoetUnitTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';
  const NEW_SESSION_ID = 'zyxw9876zyxw9876zyxw9876zyxw9876';

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

  private function makeSession(array $overrides = []) {
    return Stub::make(
      CaptchaSession::class,
      array_merge([
        'exists' => true,
        'generateSessionId' => self::NEW_SESSION_ID,
        'setFormData' => null,
        'reset' => null,
      ], $overrides),
      $this
    );
  }

  private function makeUrlFactory() {
    return Stub::make(
      CaptchaUrlFactory::class,
      [
        'getCaptchaUrlForMPForm' => 'https://example.com/captcha',
        'getCaptchaImageUrl' => 'https://example.com/image',
        'getCaptchaAudioUrl' => 'https://example.com/audio',
      ],
      $this
    );
  }

  private function makeValidator($captchaPhrase, $captchaSession = null, $wp = null): CaptchaValidator {
    return new CaptchaValidator(
      $this->makeUrlFactory(),
      $captchaPhrase,
      $wp ?? $this->wp,
      Stub::makeEmpty(SubscriberIPsRepository::class),
      Stub::makeEmpty(SubscribersRepository::class),
      $captchaSession ?? $this->makeSession()
    );
  }

  private function getError(callable $callback): ValidationError {
    try {
      $callback();
    } catch (ValidationError $error) {
      return $error;
    }
    $this->fail('Expected a ValidationError.');
  }

  public function testHashIsValid() {
    $phrase = 'abc';
    $urlFactory = Stub::makeEmpty(CaptchaUrlFactory::class);
    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase,
        'consume' => null,
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => self::SESSION_ID,
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => self::SESSION_ID,
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => self::SESSION_ID,
    ];

    verify($testee->validate($data))->true();
  }

  public function testNoCaptchaFound() {
    $phrase = 'abc';
    $newUrl = 'https://example.com';
    $captchaController = Stub::make(
      CaptchaUrlFactory::class,
      [
        'getCaptchaUrlForMPForm' => $newUrl,
        'getCaptchaImageUrl' => 'https://example.com/image',
        'getCaptchaAudioUrl' => 'https://example.com/audio',
      ],
      $this
    );

    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => null,
        'createPhrase' => 'new_phrase',
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => self::SESSION_ID,
    ];

    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      verify($error->getMessage())->equals('Please fill in the CAPTCHA.');
      verify($error->getMeta()['redirect_url'])->equals($newUrl);
      verify($error->getMeta()['captcha_session_id'])->equals(self::NEW_SESSION_ID);
    }

    verify($error)->instanceOf(ValidationError::class);
  }

  public function testCaptchaMissmatch() {
    $phrase = 'abc';
    $redirectUrl = 'https://example.com/captcha';
    $urlFactory = Stub::make(
      CaptchaUrlFactory::class,
      [
        'getCaptchaUrlForMPForm' => $redirectUrl,
      ],
      $this
    );
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => $phrase,
      'captcha_session_id' => self::SESSION_ID,
    ];

    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      verify($error->getMessage())->equals('The characters entered do not match with the previous CAPTCHA.');
      verify($error->getMeta()['refresh_captcha'])->true();
      verify($error->getMeta()['redirect_url'])->equals($redirectUrl);
    }

    verify($error)->instanceOf(ValidationError::class);
  }

  public function testNoCaptchaIsSend() {
    $phrase = 'abc';
    $newUrl = 'https://example.com';
    $captchaController = Stub::make(
      CaptchaUrlFactory::class,
      [
        'getCaptchaUrlForMPForm' => $newUrl,
        'getCaptchaImageUrl' => 'https://example.com/image',
        'getCaptchaAudioUrl' => 'https://example.com/audio',
      ],
      $this
    );

    $captchaPhrase = Stub::make(
      CaptchaPhrase::class,
      [
        'getPhrase' => $phrase,
        'createPhrase' => 'new_phrase',
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
      $subscriberRepository,
      $this->makeSession()
    );

    $data = [
      'captcha' => '',
      'captcha_session_id' => self::SESSION_ID,
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

  public function testMissingOrNonStringSessionIdStartsANewChallenge() {
    $createdFor = [];
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'createPhrase' => function ($sessionId) use (&$createdFor) {
        $createdFor[] = $sessionId;
        return 'new';
      },
    ], $this);
    $testee = $this->makeValidator($captchaPhrase);

    foreach ([[], ['captcha_session_id' => ['a']], ['captcha_session_id' => 12345678], ['captcha_session_id' => 'short']] as $extra) {
      $error = $this->getError(function () use ($testee, $extra) {
        $testee->validate(array_merge(['captcha' => 'abc'], $extra));
      });
      verify($error->getMessage())->equals('Please fill in the CAPTCHA.');
      verify($error->getMeta()['captcha_session_id'])->equals(self::NEW_SESSION_ID);
      verify($error->getMeta()['show_captcha'])->true();
    }
    verify($createdFor)->equals(array_fill(0, 4, self::NEW_SESSION_ID));
  }

  public function testUnknownSessionWithEmptyAnswerStartsANewChallengeWithTheStash() {
    $createdFor = [];
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'createPhrase' => function ($sessionId) use (&$createdFor) {
        $createdFor[] = $sessionId;
        return 'new';
      },
    ], $this);
    $stashed = [];
    $session = $this->makeSession([
      'exists' => false,
      'setFormData' => function ($sessionId, $data) use (&$stashed) {
        $stashed[] = [$sessionId, $data];
      },
    ]);
    $testee = $this->makeValidator($captchaPhrase, $session);

    $error = $this->getError(function () use ($testee) {
      $testee->validate([
        'captcha_session_id' => self::SESSION_ID,
        'captcha' => '',
        'email' => 'a@example.com',
        'form_id' => 5,
      ]);
    });

    verify($error->getMeta()['captcha_session_id'])->equals(self::NEW_SESSION_ID);
    verify($createdFor)->equals([self::NEW_SESSION_ID]);
    verify($stashed)->equals([[self::NEW_SESSION_ID, ['email' => 'a@example.com', 'form_id' => 5]]]);
  }

  public function testExistingSessionWithEmptyAnswerKeepsItsId() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'createPhrase' => Stub\Expected::once(function ($sessionId) {
        verify($sessionId)->equals(self::SESSION_ID);
        return 'new';
      }),
    ], $this);
    $session = $this->makeSession(['setFormData' => Stub\Expected::never()]);
    $testee = $this->makeValidator($captchaPhrase, $session);

    $error = $this->getError(function () use ($testee) {
      $testee->validate(['captcha_session_id' => self::SESSION_ID, 'captcha' => '']);
    });
    verify($error->getMeta()['captcha_session_id'])->equals(self::SESSION_ID);
  }

  public function testNewSessionLimitReturnsAReadableError() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'createPhrase' => function () {
        throw new CaptchaSessionLimitException();
      },
    ], $this);
    $testee = $this->makeValidator($captchaPhrase, $this->makeSession(['exists' => false]));

    $error = $this->getError(function () use ($testee) {
      $testee->validate(['captcha_session_id' => self::SESSION_ID, 'captcha' => '']);
    });
    verify($error->getMessage())->equals('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.');
    verify($error->getMeta())->arrayHasNotKey('show_captcha');
  }

  /**
   * @dataProvider dataForExistingChallengeFailures
   */
  public function testExistingChallengeResetsTheSessionAndNeverCreatesAPhrase(array $data, bool $sessionIdIsValid, string $storedPhrase) {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'getPhrase' => $storedPhrase !== '' ? $storedPhrase : null,
      'createPhrase' => Stub\Expected::never(),
    ], $this);
    $session = $this->makeSession([
      'exists' => false,
      'generateSessionId' => Stub\Expected::never(),
      'setFormData' => Stub\Expected::never(),
      'reset' => $sessionIdIsValid ? Stub\Expected::once(function ($sessionId) {
        verify($sessionId)->equals(self::SESSION_ID);
      }) : Stub\Expected::never(),
    ]);
    $testee = $this->makeValidator($captchaPhrase, $session);

    $error = $this->getError(function () use ($testee, $data) {
      $testee->validateExistingChallenge($data);
    });
    verify($error->getMessage())->equals('CAPTCHA verification failed. Please try again.');
    verify($error->getMeta())->arrayHasNotKey('show_captcha');
    verify($error->getMeta())->arrayHasNotKey('captcha_session_id');
  }

  public function dataForExistingChallengeFailures(): array {
    return [
      'no session id' => [['captcha' => 'abc'], false, ''],
      'malformed session id' => [['captcha' => 'abc', 'captcha_session_id' => 'short'], false, ''],
      'unknown session' => [['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID], true, ''],
      'empty answer' => [['captcha' => '', 'captcha_session_id' => self::SESSION_ID], true, 'abc'],
      'wrong answer' => [['captcha' => 'xyz', 'captcha_session_id' => self::SESSION_ID], true, 'abc'],
    ];
  }

  public function testExistingChallengeAcceptsTheCorrectAnswer() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, ['getPhrase' => 'abc', 'consume' => null], $this);
    $testee = $this->makeValidator($captchaPhrase);
    verify($testee->validateExistingChallenge(['captcha' => 'ABC', 'captcha_session_id' => self::SESSION_ID]))->true();
  }

  public function testExistingChallengeIsSkippedForExemptUsers() {
    $wp = Stub::make(WPFunctions::class, [
      'isUserLoggedIn' => true,
      'applyFilters' => function ($filter, $value) {
        return $value;
      },
      'wpGetCurrentUser' => (object)['roles' => ['administrator']],
    ], $this);
    $captchaPhrase = Stub::make(CaptchaPhrase::class, ['getPhrase' => Stub\Expected::never()], $this);
    $session = $this->makeSession(['reset' => Stub\Expected::never()]);
    $testee = $this->makeValidator($captchaPhrase, $session, $wp);

    verify($testee->validateExistingChallenge([]))->true();
  }

  public function testAcceptedAnswerIsConsumedOnce() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'getPhrase' => 'abc',
      'consume' => Stub\Expected::once(function ($sessionId) {
        verify($sessionId)->equals(self::SESSION_ID);
      }),
    ], $this);
    $session = $this->makeSession(['reset' => Stub\Expected::never()]);
    $testee = $this->makeValidator($captchaPhrase, $session);

    verify($testee->validate(['captcha' => 'ABC', 'captcha_session_id' => self::SESSION_ID]))->true();
  }

  public function testRejectedAnswersAreNotConsumed() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'getPhrase' => 'abc',
      'createPhrase' => 'new',
      'consume' => Stub\Expected::never(),
    ], $this);
    $testee = $this->makeValidator($captchaPhrase);

    foreach (['xyz', ''] as $answer) {
      $this->getError(function () use ($testee, $answer) {
        $testee->validate(['captcha' => $answer, 'captcha_session_id' => self::SESSION_ID]);
      });
      $this->getError(function () use ($testee, $answer) {
        $testee->validateExistingChallenge(['captcha' => $answer, 'captcha_session_id' => self::SESSION_ID]);
      });
    }
  }

  public function testAcceptedAnswerOnTheRegistrationPathIsConsumedAndTheSessionIsReset() {
    $captchaPhrase = Stub::make(CaptchaPhrase::class, [
      'getPhrase' => 'abc',
      'consume' => Stub\Expected::once(),
    ], $this);
    $session = $this->makeSession([
      'reset' => Stub\Expected::once(function ($sessionId) {
        verify($sessionId)->equals(self::SESSION_ID);
      }),
    ]);
    $testee = $this->makeValidator($captchaPhrase, $session);

    verify($testee->validateExistingChallenge(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]))->true();
  }
}
