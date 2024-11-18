<?php declare(strict_types = 1);

namespace unit\Captcha;

use Codeception\Stub;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\ReCaptchaValidator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class ReCaptchaValidatorTest extends \MailPoetUnitTest {
  const RES_TOKEN = 'someToken';

  public function testItValidatesInvisibleType() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];

    $response = json_encode(['success' => true]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function ($url, $args) use ($captchaSettings, $response) {
          verify($url)->equals('https://www.google.com/recaptcha/api/siteverify');
          verify($args['body']['secret'])->equals($captchaSettings['recaptcha_invisible_secret_token']);
          verify($args['body']['response'])->equals(self::RES_TOKEN);
          return $response;
        },
        'wpRemoteRetrieveBody' => function ($data) use ($response) {
          verify($data)->equals($response);
          return $response;
        },
      ],
      $this
    );

    $testee = new ReCaptchaValidator($wp, $settings);
    verify($testee->validate(self::RES_TOKEN))->null();
  }

  public function testItValidatesCheckboxType() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];

    $response = json_encode(['success' => true]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function ($url, $args) use ($captchaSettings, $response) {
          verify($url)->equals('https://www.google.com/recaptcha/api/siteverify');
          verify($args['body']['secret'])->equals($captchaSettings['recaptcha_secret_token']);
          verify($args['body']['response'])->equals(self::RES_TOKEN);
          return $response;
        },
        'wpRemoteRetrieveBody' => function ($data) use ($response) {
          verify($data)->equals($response);
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($wp, $settings);
    verify($testee->validate(self::RES_TOKEN))->null();
  }

  public function testItFailsIfTokenIsMissing() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
    ];

    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(WPFunctions::class);
    $testee = new RecaptchaValidator($wp, $settings);
    try {
      $testee->validate('');
    } catch (\Exception $error) {
      verify($error)->instanceOf(\Exception::class);
      verify($error->getMessage())->equals('Please check the CAPTCHA.');
    }
  }

  public function testItFailsIfTokenIsInvalid() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];

    $response = json_encode(['success' => false]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
        'wpRemoteRetrieveBody' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($wp, $settings);
    try {
      $testee->validate('anyValue');
    } catch (\Exception $error) {
      verify($error)->instanceOf(\Exception::class);
      verify($error->getMessage())->equals('Invalid CAPTCHA. Try again.');
    }
  }

  public function testItFailsOnJsonError() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];

    $response = 'invalidJson';
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
        'wpRemoteRetrieveBody' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($wp, $settings);
    try {
      $testee->validate('anyValue');
    } catch (\Exception $error) {
      verify($error)->instanceOf(\Exception::class);
      verify($error->getMessage())->equals('Error while validating the CAPTCHA.');
    }
  }

  public function testItFailsOnHttpError() {
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];

    $response = (object)['wp-error'];
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => true,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($wp, $settings);
    try {
      $testee->validate('anyValue');
    } catch (\Exception $error) {
      verify($error)->instanceOf(\Exception::class);
      verify($error->getMessage())->equals('Error while validating the CAPTCHA.');
    }
  }
}
