<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Captcha\BehavioralSignals;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\RecaptchaValidator;
use MailPoet\Captcha\Validator\TurnstileValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsFormsRepository;
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
    $builtInCaptchaValidator = Stub::makeEmpty(CaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);

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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::makeEmpty(BehavioralSignals::class),
      Stub::makeEmpty(TrackingConsentCapture::class)
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
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
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
    $builtInCaptchaValidator = Stub::makeEmpty(CaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);

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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::make(BehavioralSignals::class, ['looksHuman' => true], $this),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    verify($result)->equals([
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
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
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
    $builtInCaptchaValidator = Stub::makeEmpty(CaptchaValidator::class);
    $recaptchaValidator = Stub::makeEmpty(RecaptchaValidator::class);
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);

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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::make(BehavioralSignals::class, ['looksHuman' => true], $this),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $this->expectException(UnexpectedValueException::class);
    $testee->subscribe(array_merge(['form_id' => 1], $submitData));
  }

  public function testBuiltInValidatorFails() {

    $captchaSessionId = 'captcha_session_id';
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          verify($receivedSessionId)->equals($captchaSessionId);
        },
      ]
    );
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
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);
    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_BUILTIN,
    ];
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function($value) use ($captchaSettings) {
          if ($value === 'captcha') {
            return $captchaSettings;
          }
        },
      ]
    );
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
      CaptchaValidator::class,
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
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);

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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::makeEmpty(BehavioralSignals::class),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    verify($result)->equals([
      'error' => 'Please fill in the CAPTCHA.',
      'redirect_url' => $expectedRedirectLink,
    ]);
  }

  public function testRecaptchaValidatorFails() {

    $captchaSessionId = 'captcha_session_id';

    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getCaptchaHash' => ['phrase' => 'a_string_that_does_not_match'],
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          verify($receivedSessionId)->equals($captchaSessionId);
        },
      ]
    );
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
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $requiredCustomFieldValidator = Stub::makeEmpty(RequiredCustomFieldValidator::class);

    $captchaSettings = [
      'type' => CaptchaConstants::TYPE_RECAPTCHA,
    ];
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function($value) use ($captchaSettings) {
          if ($value === 'captcha') {
            return $captchaSettings;
          }
        },
      ]
    );

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
      CaptchaValidator::class,
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
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);
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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::makeEmpty(BehavioralSignals::class),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    verify($result)->equals([
      'refresh_captcha' => true,
      'error' => 'The characters entered do not match with the previous CAPTCHA.',
    ]);
  }

  public function testItShouldReturnTrueIfSubscribedToAnySegmentsInForm() {
    $blockSegmentIds = [15, 16];
    $segmentIds = [17];
    $formSegments = [15, 16, 17];
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
      Stub::makeEmpty(CaptchaValidator::class),
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      Stub::makeEmpty(BehavioralSignals::class),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->isSubscribedToAnyFormSegments($form, $subscriber);
    verify($result)->equals(true);
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
      Stub::makeEmpty(CaptchaValidator::class),
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      Stub::makeEmpty(BehavioralSignals::class),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->isSubscribedToAnyFormSegments($form, $subscriber);
    verify($result)->equals(false);
  }

  public function testSubscribeSuccess() {

    $captchaSessionId = 'captcha_session_id';
    $captcha = 'captcha';

    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getCaptchaHash' => $captcha,
        'init' => function($receivedSessionId) use ($captchaSessionId) {
          verify($receivedSessionId)->equals($captchaSessionId);
        },
      ]
    );
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
            },
            array_keys($formFields)
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

          verify($receivedData)->equals($formFields);
          verify($receivedSegmentIds)->equals($segmentIds);
          return [$subscriber, ['confirmationEmailResult' => true]];
        },
      ],
      $this
    );
    $subscribersFinder = Stub::makeEmpty(SubscribersFinder::class);
    $throttling = Stub::makeEmpty(SubscriptionThrottling::class);
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
      'deobfuscateFormPayload' => function($data) { return $data;
      },
      ]
    );
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
    $receivedHooks = [];
    $wp = Stub::make(
      WPFunctions::class,
      [
      'doAction' => function($receivedHook, ...$args) use (&$receivedHooks, $formFields, $segmentIds, $form, $subscriber) {
        $receivedHooks[] = $receivedHook;
        if ($receivedHook === 'mailpoet_subscription_before_subscribe') {
          verify($args)->equals([$formFields, $segmentIds, $form]);
        } elseif ($receivedHook === 'mailpoet_subscription_after_subscribe') {
          verify($args)->equals([$subscriber, $formFields, $segmentIds, $form]);
        }
      },
      ]
    );
    $tagRepository = Stub::makeEmpty(TagRepository::class);
    $subscriberTagRepository = Stub::makeEmpty(SubscriberTagRepository::class);
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'validate' => function($data) use ($captcha) {
          verify($data['captcha'])->equals($captcha);
          return true;
        },
        'isUserExemptFromCaptcha' => false,
      ],
      $this
    );
    $recaptchaValidator = Stub::make(RecaptchaValidator::class);
    $turnstileValidator = Stub::makeEmpty(TurnstileValidator::class);

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
      $recaptchaValidator,
      $turnstileValidator,
      Stub::make(BehavioralSignals::class, ['looksHuman' => true], $this),
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(array_merge(['form_id' => 1], $submitData));
    verify($result)->equals([]);
    verify($receivedHooks)->equals([
      'mailpoet_subscription_before_subscribe',
      'mailpoet_subscription_after_subscribe',
    ]);
  }

  public function testBehavioralBaselineEscalatesWhenSignalsAreMissing() {
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => null];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $challengeMeta = [
      'show_captcha' => true,
      'captcha_session_id' => 'new_session',
      'captcha_image_url' => 'https://example.com/image',
      'captcha_audio_url' => 'https://example.com/audio',
      'redirect_url' => 'https://example.com/page',
    ];
    $capturedStash = null;
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'isUserExemptFromCaptcha' => false,
        'getInlineCaptchaChallenge' => Expected::once(function($formData) use ($challengeMeta, &$capturedStash) {
          $capturedStash = $formData;
          return $challengeMeta;
        }),
        'validate' => Expected::never(),
        'validateChallenge' => Expected::never(),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => false],
      $this
    );

    $testee = new SubscriberSubscribeController(
      Stub::makeEmpty(CaptchaSession::class),
      Stub::makeEmpty(SubscriberActions::class, ['subscribe' => Expected::never()], $this),
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(['form_id' => 1, 'segments' => [1]]);
    verify($result['show_captcha'])->true();
    verify($result['error'])->equals('Please fill in the CAPTCHA.');
    verify($result['captcha_session_id'])->equals('new_session');
    // The stash must keep the selected segments so the resubmit (inline or via
    // the non-JS captcha page) can subscribe to the same lists.
    verify($capturedStash['form_id'])->equals(1);
    verify($capturedStash['segments'])->equals([1]);
  }

  public function testBehavioralBaselinePassesSilentlyWhenSignalsLookHuman() {
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => null];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'isUserExemptFromCaptcha' => false,
        'getInlineCaptchaChallenge' => Expected::never(),
        'validate' => Expected::never(),
        'validateChallenge' => Expected::never(),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::once(true)],
      $this
    );
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function() use ($subscriber) {
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      Stub::makeEmpty(CaptchaSession::class),
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe(['form_id' => 1]);
    verify($result)->equals([]);
  }

  public function testBuiltInCaptchaEscalatesWhenSignalsLookBotLike() {
    // Built-in CAPTCHA + correct answer, but behavioral signals look bot-like:
    // we must re-issue a fresh challenge rather than subscribe.
    $captchaSessionId = 'sess';
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => CaptchaConstants::TYPE_BUILTIN];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() { return ['form_id' => 1];
        },
      ]
    );
    $challengeMeta = [
      'show_captcha' => true,
      'captcha_session_id' => 'new_session',
      'captcha_image_url' => 'https://example.com/image',
      'captcha_audio_url' => 'https://example.com/audio',
      'redirect_url' => 'https://example.com/page',
    ];
    $capturedStash = null;
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'validate' => Expected::once(true),
        'isUserExemptFromCaptcha' => false,
        'getInlineCaptchaChallenge' => Expected::once(function($formData) use ($challengeMeta, &$capturedStash) {
          $capturedStash = $formData;
          return $challengeMeta;
        }),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::once(false)],
      $this
    );
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      ['subscribe' => Expected::never()],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => $captchaSessionId,
      'captcha' => 'ABCDEF',
      BehavioralSignals::FIELD_NAME => ['time_ms' => 100],
    ]);
    verify($result['show_captcha'])->true();
    verify($result['error'])->equals('Please fill in the CAPTCHA.');
    verify($result['captcha_session_id'])->equals('new_session');
    // Stash must keep form_id but drop the suspect signals so the resubmit
    // is evaluated on freshly collected counters, not the cached bot-like ones.
    verify($capturedStash['form_id'])->equals(1);
    verify(isset($capturedStash[BehavioralSignals::FIELD_NAME]))->false();
  }

  public function testBuiltInCaptchaPassesWhenSignalsLookHuman() {
    $captchaSessionId = 'sess';
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => CaptchaConstants::TYPE_BUILTIN];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() { return ['form_id' => 1];
        },
      ]
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'validate' => Expected::once(true),
        'isUserExemptFromCaptcha' => Expected::once(false),
        'getInlineCaptchaChallenge' => Expected::never(),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::once(true)],
      $this
    );
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function() use ($subscriber) {
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => $captchaSessionId,
      'captcha' => 'ABCDEF',
      BehavioralSignals::FIELD_NAME => ['time_ms' => 5000, 'kd_count' => 5, 'focus_count' => 1],
    ]);
    verify($result)->equals([]);
  }

  public function testBuiltInCaptchaSkipsSignalCheckForExemptUsers() {
    // Admin / editor: exempt from CAPTCHA, must also be exempt from the
    // behavioral check so they aren't bounced when JS hasn't accumulated signals.
    $captchaSessionId = 'sess';
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => CaptchaConstants::TYPE_BUILTIN];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() { return ['form_id' => 1];
        },
      ]
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'validate' => Expected::once(true),
        'isUserExemptFromCaptcha' => Expected::once(true),
        'getInlineCaptchaChallenge' => Expected::never(),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::never()],
      $this
    );
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function() use ($subscriber) {
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => $captchaSessionId,
      'captcha' => 'ABCDEF',
    ]);
    verify($result)->equals([]);
  }

  public function testBuiltInCaptchaResubmitPrefersFreshSignalsOverStash() {
    // initCaptcha restores the stashed payload on resubmit, but the freshest
    // behavioral_signals from the current request must win so the user isn't
    // re-evaluated on the snapshot that triggered the original challenge.
    $captchaSessionId = 'sess';
    $stashedSignals = ['time_ms' => 50, 'mm_count' => 0, 'kd_count' => 0, 'focus_count' => 0];
    $freshSignals = ['time_ms' => 10000, 'mm_count' => 20, 'kd_count' => 10, 'focus_count' => 2];
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => CaptchaConstants::TYPE_BUILTIN];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() use ($stashedSignals) {
          return ['form_id' => 1, BehavioralSignals::FIELD_NAME => $stashedSignals];
        },
      ]
    );
    $capturedSignals = null;
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      [
        'looksHuman' => Expected::once(function($data) use (&$capturedSignals) {
          $capturedSignals = $data[BehavioralSignals::FIELD_NAME] ?? null;
          return true;
        }),
      ],
      $this
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'validate' => Expected::once(true),
        'isUserExemptFromCaptcha' => false,
        'getInlineCaptchaChallenge' => Expected::never(),
      ],
      $this
    );
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function() use ($subscriber) {
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => $captchaSessionId,
      'captcha' => 'ABCDEF',
      BehavioralSignals::FIELD_NAME => $freshSignals,
    ]);
    verify($result)->equals([]);
    verify($capturedSignals)->equals($freshSignals);
  }

  public function testBehavioralBaselineResubmitVerifiesChallengeAndRestoresStashedSegments() {
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    // A form whose segments come from a list-selection block: if the stashed
    // payload lost `segments`, getSegmentIds() would throw "Please select a list."
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSegmentBlocksSegmentIds' => function(): array { return [1, 2];
        },
        'getSettingsSegmentIds' => function(): array { return [99];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => null];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() { return ['form_id' => 1, 'segments' => [1]];
        },
      ]
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'isUserExemptFromCaptcha' => Expected::once(false),
        'getInlineCaptchaChallenge' => Expected::never(),
        'validate' => Expected::never(),
        'validateChallenge' => Expected::once(true),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::once(true)],
      $this
    );
    $capturedSegmentIds = null;
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function($data, $segmentIds) use ($subscriber, &$capturedSegmentIds) {
          $capturedSegmentIds = $segmentIds;
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => 'sess',
      'captcha' => 'ABCDEF',
    ]);
    verify($result)->equals([]);
    verify($capturedSegmentIds)->equals([1]);
  }

  public function testBehavioralBaselineResubmitReChallengesWhenSignalsStillLookBotLike() {
    // No CAPTCHA configured + correct CAPTCHA answer on the escalated challenge,
    // but the resubmit still has non-human signals (e.g. JS-disabled client):
    // solving the CAPTCHA alone must not bypass the baseline.
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => null];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() { return ['form_id' => 1];
        },
      ]
    );
    $challengeMeta = [
      'show_captcha' => true,
      'captcha_session_id' => 'new_session',
      'captcha_image_url' => 'https://example.com/image',
      'captcha_audio_url' => 'https://example.com/audio',
      'redirect_url' => 'https://example.com/page',
    ];
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'isUserExemptFromCaptcha' => Expected::once(false),
        'validateChallenge' => Expected::once(true),
        'getInlineCaptchaChallenge' => Expected::once(function() use ($challengeMeta) {
          return $challengeMeta;
        }),
        'validate' => Expected::never(),
      ],
      $this
    );
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      ['looksHuman' => Expected::once(false)],
      $this
    );
    $subscriberActions = Stub::makeEmpty(
      SubscriberActions::class,
      ['subscribe' => Expected::never()],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => 'sess',
      'captcha' => 'ABCDEF',
    ]);
    verify($result['show_captcha'])->true();
    verify($result['captcha_session_id'])->equals('new_session');
    verify($result['error'])->equals('Please fill in the CAPTCHA.');
  }

  public function testBehavioralBaselineResubmitPrefersFreshSignalsOverStash() {
    // The stash carries bot-like signals from the original submit. On resubmit,
    // the current request's freshest signals must override the stash so the
    // resubmit's signal check reflects accumulated interaction.
    $stashedSignals = ['time_ms' => 50, 'kd_count' => 0];
    $freshSignals = ['time_ms' => 10000, 'mm_count' => 20, 'kd_count' => 10, 'focus_count' => 2];
    $subscriber = Stub::makeEmpty(SubscriberEntity::class);
    $form = Stub::makeEmpty(
      FormEntity::class,
      [
        'getId' => 1,
        'getSettingsSegmentIds' => function(): array { return [1];
        },
        'getBlocksByTypes' => function(): array { return [];
        },
        'getSettings' => function(): array { return [];
        },
      ]
    );
    $formsRepository = Stub::makeEmpty(
      FormsRepository::class,
      [
        'findOneById' => function() use ($form): FormEntity { return $form;
        },
      ]
    );
    $settings = Stub::makeEmpty(
      SettingsController::class,
      [
        'get' => function() { return ['type' => null];
        },
      ]
    );
    $fieldNameObfuscator = Stub::makeEmpty(
      FieldNameObfuscator::class,
      [
        'deobfuscateFormPayload' => function($data) { return $data;
        },
      ]
    );
    $captchaSession = Stub::makeEmpty(
      CaptchaSession::class,
      [
        'getFormData' => function() use ($stashedSignals) {
          return ['form_id' => 1, BehavioralSignals::FIELD_NAME => $stashedSignals];
        },
      ]
    );
    $capturedSignals = null;
    $behavioralSignals = Stub::make(
      BehavioralSignals::class,
      [
        'looksHuman' => Expected::once(function($data) use (&$capturedSignals) {
          $capturedSignals = $data[BehavioralSignals::FIELD_NAME] ?? null;
          return true;
        }),
      ],
      $this
    );
    $builtInCaptchaValidator = Stub::make(
      CaptchaValidator::class,
      [
        'isUserExemptFromCaptcha' => false,
        'validateChallenge' => Expected::once(true),
        'getInlineCaptchaChallenge' => Expected::never(),
        'validate' => Expected::never(),
      ],
      $this
    );
    $subscriberActions = Stub::make(
      SubscriberActions::class,
      [
        'subscribe' => Expected::once(function() use ($subscriber) {
          return [$subscriber, ['confirmationEmailResult' => true]];
        }),
      ],
      $this
    );

    $testee = new SubscriberSubscribeController(
      $captchaSession,
      $subscriberActions,
      Stub::makeEmpty(SubscribersFinder::class),
      Stub::makeEmpty(SubscriptionThrottling::class),
      $fieldNameObfuscator,
      Stub::makeEmpty(RequiredCustomFieldValidator::class),
      $settings,
      $formsRepository,
      Stub::makeEmpty(StatisticsFormsRepository::class),
      Stub::makeEmpty(TagRepository::class),
      Stub::makeEmpty(SubscriberTagRepository::class),
      Stub::makeEmpty(WPFunctions::class),
      $builtInCaptchaValidator,
      Stub::makeEmpty(RecaptchaValidator::class),
      Stub::makeEmpty(TurnstileValidator::class),
      $behavioralSignals,
      Stub::makeEmpty(TrackingConsentCapture::class)
    );

    $result = $testee->subscribe([
      'form_id' => 1,
      'captcha_session_id' => 'sess',
      'captcha' => 'ABCDEF',
      BehavioralSignals::FIELD_NAME => $freshSignals,
    ]);
    verify($result)->equals([]);
    verify($capturedSignals)->equals($freshSignals);
  }
}
