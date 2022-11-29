<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsFormsRepository;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Subscription\Captcha\Validator\BuiltInCaptchaValidator;
use MailPoet\Subscription\Captcha\Validator\RecaptchaValidator;
use MailPoet\Subscription\Captcha\Validator\ValidationError;
use MailPoet\Subscription\Throttling;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\Tags\TagRepository;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberSubscribeControllerUnitTest extends \MailPoetUnitTest {
  public function testErrorGetsThrownWhenEmailFieldIsNotObfuscated() {
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
    $throttling = Stub::makeEmpty(SubscriptionThrottling::class);
    $fieldNameObfuscator = Stub::makeEmpty(FieldNameObfuscator::class);
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $settings = Stub::makeEmpty(SettingsController::class);
    $form = Stub::makeEmpty(FormEntity::class);
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::makeEmpty(BuiltInCaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);

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
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
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
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
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
      ],
      $this
    );
    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::makeEmpty(BuiltInCaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'refresh_captcha' => true,
      'error' => 'You need to wait 1 before subscribing again.',
    ]);
  }

  public function testNoSubscriptionWhenActionHookBeforeSubscriptionThrowsError() {
    $captchaSession = Stub::makeEmpty(CaptchaSession::class);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
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
      ],
      $this
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
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::makeEmpty(BuiltInCaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
    );

    $this->expectException(UnexpectedValueException::class);
    $testee->subscribe(array_merge(['form_id' => 1], $submitData));
  }

  public function testBuiltInValidatorFails() {

    $captchaSessionId = 'captcha_session_id';
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
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
    $expectedRedirectLink = 'redirect';
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
      'type' => CaptchaConstants::TYPE_BUILTIN,
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
      ],
      $this
    );
    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::make(
      BuiltInCaptchaValidator::class,
      [
        'validate' => Expected::once(function() use ($expectedRedirectLink) {
          throw new ValidationError('Please fill in the CAPTCHA.', ['redirect_url' => $expectedRedirectLink]);
        }),
      ],
      $this
    );
    $recaptchaValidator = Stub::make(
      RecaptchaValidator::class,
      [
        'validate' => Expected::never(),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'error' => 'Please fill in the CAPTCHA.',
      'redirect_url' => $expectedRedirectLink,
    ]);
  }

  public function testRecaptchaValidatorFails() {

    $captchaSessionId = 'captcha_session_id';

    $captchaSession = Stub::makeEmpty(CaptchaSession::class,
      [
        'getCaptchaHash' => ['phrase' => 'a_string_that_does_not_match'],
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          expect($receivedSessionId)->equals($captchaSessionId);
        },
      ]);
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      [
        'subscribe' => Expected::never(),
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
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
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
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
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'doAction' => Expected::never(),
      ],
      $this
    );
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::make(
      BuiltInCaptchaValidator::class,
      [
        'validate' => Expected::never(),
      ],
      $this
    );
    $recaptchaValidator = Stub::make(
      RecaptchaValidator::class,
      [
        'validate' => function() {
          throw new ValidationError(
            "The characters entered do not match with the previous CAPTCHA.",
            [
              'refresh_captcha' => true,
            ]
          );
        },
      ],
      $this
    );
    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([
      'refresh_captcha' => true,
      'error' => 'The characters entered do not match with the previous CAPTCHA.',
    ]);
  }

  public function testItShouldReturnTrueIfSubscribedToAnySegmentsInForm() {
    $blockSegmentIds = [15,16];
    $segmentIds = [17];
    $formSegments = [15,16,17];
    $subscriberId = 1;

    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
        'getSegmentBlocksSegmentIds' => function() use ($blockSegmentIds) {
          return $blockSegmentIds;
        },
      ]
    );

    $subscriber = Stub::makeEmpty(
      SubscriberEntity::class,
      [
        'getId' => function() use($subscriberId): int {
          return $subscriberId;
        },
      ]
    );

    $subscribersFinder = $this->createMock(SubscribersFinder::class);
    $subscribersFinder->expects($this->once())->method('findSubscribersInSegments')
      ->with([$subscriberId], $formSegments)
      ->willReturn([15]);

    $testee = new SubscriberSubscribeController(
      Stub::makeEmpty(CaptchaSession::class),
      Stub::makeEmpty(SubscriberActions::class),
      $subscribersFinder,
      Stub::makeEmpty(Throttling::class),
      Stub::makeEmpty(FieldNameObfuscator::class),
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      Stub::makeEmpty(SettingsController::class),
      Stub::makeEmpty(FormsRepository::class),
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      Stub::makeEmpty(BuiltInCaptchaValidator::class),
      Stub::makeEmpty(RecaptchaValidator::class)
    );

    $result = $testee->isSubscribedToAnyFormSegments($form, $subscriber);
    expect($result)->equals(true);
  }

  public function testItShouldReturnFalseIfNotSubscribedToAnySegmentsInForm() {
    $blockSegmentIds = [];
    $segmentIds = [17];
    $formSegments = [17];
    $subscriberId = 1;

    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getSettingsSegmentIds' => function() use ($segmentIds): array {
          return $segmentIds;
        },
        'getSegmentBlocksSegmentIds' => function() use ($blockSegmentIds) {
          return $blockSegmentIds;
        },
      ]
    );

    $subscriber = Stub::makeEmpty(
      SubscriberEntity::class,
      [
        'getId' => function() use($subscriberId): int {
          return $subscriberId;
        },
      ]
    );

    $subscribersFinder = $this->createMock(SubscribersFinder::class);
    $subscribersFinder->expects($this->once())->method('findSubscribersInSegments')
      ->with([$subscriberId], $formSegments)
      ->willReturn([]);

    $testee = new SubscriberSubscribeController(
      Stub::makeEmpty(CaptchaSession::class),
      Stub::makeEmpty(SubscriberActions::class),
      $subscribersFinder,
      Stub::makeEmpty(SubscriptionThrottling::class),
      Stub::makeEmpty(FieldNameObfuscator::class),
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      Stub::makeEmpty(SettingsController::class),
      Stub::makeEmpty(FormsRepository::class),
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      Stub::makeEmpty(BuiltInCaptchaValidator::class),
      Stub::makeEmpty(RecaptchaValidator::class)
    );

    $result = $testee->isSubscribedToAnyFormSegments($form, $subscriber);
    expect($result)->equals(false);
  }

  public function testSubscribeSuccess() {

    $captchaSessionId = 'captcha_session_id';
    $captcha = 'captcha';

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
          return [$subscriber, ['confirmationEmailResult' => true]];
        },
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
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
              'type' => CaptchaConstants::TYPE_BUILTIN,
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
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::make(
      BuiltInCaptchaValidator::class,
      [
        'validate' => function($data) use ($captcha) {
          expect($data['captcha'])->equals($captcha);
          return true;
        },
      ],
      $this
    );
    $recaptchaValidator = Stub::make(RecaptchaValidator::class);

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      $subscribersFinder,
      $throttling,
      $fieldNameObfuscator,
      $requiredCustomFieldValidator,
      $settings,
      $formsRepository,
      $statisticsFormsRepository,
      $tagRepository,
      $subscriberTagRepository,
      $wp,
      $builtInCaptchaValidator,
      $recaptchaValidator
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    expect($result)->equals([]);
  }
}
