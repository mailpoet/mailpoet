<?php
declare(strict_types=1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsFormsRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberSubscribeControllerUnitTest extends \MailPoetUnitTest {
  public function testErrorGetsThrownWhenEmailFieldIsNotObfuscated() {
    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $throttling = Stub::makeEmpty(SubscriptionThrottling::class);
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $settings = Stub::makeEmpty(SettingsController::class);
    $form = Stub::makeEmpty(FormEntity::class);

    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(
      StatisticsFormsRepository::class,
      [
        'subscribe' => Expected::never(),
      ]
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $this->expectException(UnexpectedValueException::class);
    $testee->subscribe(
      [
        'form_id' => 2,
        'email' => 'john.doe@gmail.com',
      ]
    );
  }

  public function testNoSubscriptionWhenThrottle() {
    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $throttling = Stub::makeEmpty(
      SubscriptionThrottling::class,
      [
        'throttle' => 1,
        'secondsToTimeString' => '1',
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $settings = Stub::makeEmpty(SettingsController::class);
    $submitData = [];
    $segmentIds = [1];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(
      StatisticsFormsRepository::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'refresh_captcha' => true,
      'error' => 'You need to wait 1 before subscribing again.',
    ]);
  }

  public function testNoSubscriptionWhenActionHookBeforeSubscriptionThrowsError() {
    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class);
    $throttling = Stub::makeEmpty(SubscriptionThrottling::class);
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $settings = Stub::makeEmpty(SettingsController::class);
    $submitData = [];
    $segmentIds = [1];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(
      StatisticsFormsRepository::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => function($hook) {
          if ($hook === 'mailpoet_subscription_before_subscribe') {
            throw new \MailPoet\UnexpectedValueException("Value not expected.");
          }
        },
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $this->expectException(UnexpectedValueException::class);
    $testee->subscribe(array_merge(['form_id' => 1], $submitData));
  }

  public function testBuiltinCaptchaNotFilledOut() {

    $captchaSessionId = 'captcha_session_id';
    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class, [
      'isRequired' => true,
    ]);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class,
      [
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          expect($receivedSessionId)->equals($captchaSessionId);
        },
      ]);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $expectedRedirectLink = 'redirect';
    $subscriptionUrlFactory = Stub::makeEmpty(
      SubscriptionUrlFactory::class,
      [
        'getCaptchaUrl' => $expectedRedirectLink,
      ]
    );
    $throttling = Stub::makeEmpty(
      SubscriptionThrottling::class,
      [
        'throttle' => 1,
        'secondsToTimeString' => '1',
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $captchaSettings = [
      'type' => Captcha::TYPE_BUILTIN,
    ];
    $settings = Stub::makeEmpty(SettingsController::class,
      [
        'get' => function($value) use ($captchaSettings) {
          if ($value === 'captcha') {
            return $captchaSettings;
          }
        },
      ]);
    $submitData = [
      'captcha_session_id' => $captchaSessionId,
    ];
    $segmentIds = [1];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(
      StatisticsFormsRepository::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'redirect_url' => $expectedRedirectLink,
      'error' => 'Please fill in the CAPTCHA.',
    ]);
  }

  public function testBuiltinCaptchaNotValid() {

    $captchaSessionId = 'captcha_session_id';

    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class, [
      'isRequired' => true,
    ]);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class,
      [
        'getCaptchaHash' => 'a_string_that_does_not_match',
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          expect($receivedSessionId)->equals($captchaSessionId);
        },
      ]);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ]
    );
    $expectedRedirectLink = 'redirect';
    $subscriptionUrlFactory = Stub::makeEmpty(
      SubscriptionUrlFactory::class,
      [
        'getCaptchaUrl' => $expectedRedirectLink,
      ]
    );
    $throttling = Stub::makeEmpty(
      SubscriptionThrottling::class,
      [
        'throttle' => 1,
        'secondsToTimeString' => '1',
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);

    $captchaSettings = [
      'type' => Captcha::TYPE_BUILTIN,
    ];
    $settings = Stub::makeEmpty(SettingsController::class,
      [
        'get' => function($value) use ($captchaSettings) {
          if ($value === 'captcha') {
            return $captchaSettings;
          }
        },
      ]);

    $submitData = [
      'captcha_session_id' => $captchaSessionId,
      'captcha' => 'captcha',
    ];
    $segmentIds = [1];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
      ]
    );

    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(
      StatisticsFormsRepository::class,
      [
        'subscribe' => Expected::never(),
      ]
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'refresh_captcha' => true,
      'error' => 'The characters entered do not match with the previous CAPTCHA.',
    ]);
  }

  public function testSubscribeSuccess() {

    $captchaSessionId = 'captcha_session_id';
    $captcha = 'captcha';

    $subscriptionCaptcha = Stub::makeEmpty(Captcha::class, [
      'isRequired' => true,
    ]);
    $captchaSession = Stub::makeEmpty(CaptchaSession::class,
      [
        'getCaptchaHash' => $captcha,
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          expect($receivedSessionId)->equals($captchaSessionId);
        },
      ]);
    $formFields = [
      'field_a' => 'value_a',
      'field_b' => 'value_b',
    ];
    $submitData = array_merge([
      'captcha_session_id' => $captchaSessionId,
      'captcha' => $captcha,
    ], $formFields);
    $segmentIds = [1];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
        'getBlocksByTypes' => function() use ($formFields) {
          $fields = array_values(array_map(
            function(string $id): array {
              return [
                'id' => $id,
              ];
            }, array_keys($formFields)
          ));

          return $fields;
        },
      ]
    );
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => function($receivedData, $receivedSegmentIds) use ($formFields, $segmentIds, $subscriber) {

          expect($receivedData)->equals($formFields);
          expect($receivedSegmentIds)->equals($segmentIds);
          return $subscriber;
        },
      ],
      $this
    );
    $subscriptionUrlFactory = Stub::makeEmpty(SubscriptionUrlFactory::class,
      [
        'subscribe' => function($receivedSubscriber, $receivedForm) use ($subscriber, $form) {
          expect($receivedSubscriber)->equals($subscriber);
          expect($receivedForm)->equals($form);
        },
      ]);
    $throttling = Stub::makeEmpty(SubscriptionThrottling::class);
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class,
    [
      'deobfuscateFormPayload' => function($data) { return $data;
      },
    ]);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function($value) {
          if ($value === 'captcha') {
            return [
              'type' => Captcha::TYPE_BUILTIN,
            ];
          }
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity {
          return $form;
        },
      ]
    );
    $statisticsFormsRepository = Stub::makeEmpty(StatisticsFormsRepository::class);
    $wp = Stub::make(
      WPFunctions::class,
    [
      'doAction' => function($receivedHook, $receivedData, $receivedSegmentIds, $receivedForm) use ($formFields, $segmentIds, $form) {
        expect($receivedHook)->equals('mailpoet_subscription_before_subscribe');
        expect($receivedData)->equals($formFields);
        expect($receivedSegmentIds)->equals($segmentIds);
        expect($receivedForm)->equals($form);
      },
      ]
    );

    $testee = new SubscriberSubscribeController(
      $subscriptionCaptcha,
      $captchaSession,
      $subscriberActions,
      $subscriptionUrlFactory,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $wp
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([]);
  }
}
