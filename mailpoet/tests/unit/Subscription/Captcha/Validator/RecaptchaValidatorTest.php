<?php declare(strict_types = 1);

namespace MailPoet\Subscription\Captcha\Validator;

use Codeception\Stub;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\WP\Functions as WPFunctions;

class RecaptchaValidatorTest extends \MailPoetUnitTest {
  public function testSuccessfulInvisibleValidation() {

    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];
    $recaptchaResponseToken = 'recaptchaResponseToken';
    $response = json_encode(['success' => true]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function($key) use ($captchaSettings) {
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
        'wpRemotePost' => function($url, $args) use ($recaptchaResponseToken, $captchaSettings, $response) {
          expect($url)->equals('https://www.google.com/recaptcha/api/siteverify');
          expect($args['body']['secret'])->equals($captchaSettings['recaptcha_invisible_secret_token']);
          expect($args['body']['response'])->equals($recaptchaResponseToken);
          return $response;
        },
        'isWpError' => false,
        'wpRemoteRetrieveBody' => function($data) use ($response) {
          expect($data)->equals($response);
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($settings, $wp);
    $data = [
      'recaptchaResponseToken' => $recaptchaResponseToken,
    ];
    expect($testee->validate($data))->true();
  }

  public function testSuccessfulValidation() {

    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];
    $recaptchaResponseToken = 'recaptchaResponseToken';
    $response = json_encode(['success' => true]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function($key) use ($captchaSettings) {
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
        'wpRemotePost' => function($url, $args) use ($recaptchaResponseToken, $captchaSettings, $response) {
          expect($url)->equals('https://www.google.com/recaptcha/api/siteverify');
          expect($args['body']['secret'])->equals($captchaSettings['recaptcha_secret_token']);
          expect($args['body']['response'])->equals($recaptchaResponseToken);
          return $response;
        },
        'isWpError' => false,
        'wpRemoteRetrieveBody' => function($data) use ($response) {
          expect($data)->equals($response);
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($settings, $wp);
    $data = [
      'recaptchaResponseToken' => $recaptchaResponseToken,
    ];
    expect($testee->validate($data))->true();
  }

  public function testFailingValidation() {

    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];
    $recaptchaResponseToken = 'recaptchaResponseToken';
    $response = json_encode(['success' => false]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function($key) use ($captchaSettings) {
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
        'wpRemotePost' => function() use ($response) {
          return $response;
        },
        'isWpError' => false,
        'wpRemoteRetrieveBody' => function() use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new RecaptchaValidator($settings, $wp);
    $data = [
      'recaptchaResponseToken' => $recaptchaResponseToken,
    ];
    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      expect($error->getMessage())->equals('Error while validating the CAPTCHA.');
    }
    expect($error)->isInstanceOf(ValidationError::class);
  }

  public function testConnectionError() {

    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
      'recaptcha_invisible_secret_token' => 'recaptcha_invisible_secret_token',
      'recaptcha_secret_token' => 'recaptcha_secret_token',
    ];
    $recaptchaResponseToken = 'recaptchaResponseToken';
    $response = (object)['wp-error'];
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function($key) use ($captchaSettings) {
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
        'wpRemotePost' => function() use ($response) {
          return $response;
        },
        'isWpError' => true,
      ],
      $this
    );

    $testee = new RecaptchaValidator($settings, $wp);
    $data = [
      'recaptchaResponseToken' => $recaptchaResponseToken,
    ];
    $error = null;
    try {
      $testee->validate($data);
    } catch (ValidationError $error) {
      expect($error->getMessage())->equals('Error while validating the CAPTCHA.');
    }
    expect($error)->isInstanceOf(ValidationError::class);
  }
}
