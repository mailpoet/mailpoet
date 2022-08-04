<?php

namespace MailPoet\Subscribers;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\NotFoundException;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsFormsRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberSubscribeController {
  /** @var FormsRepository */
  private $formsRepository;

  /** @var Captcha */
  private $subscriptionCaptcha;

  /** @var CaptchaSession */
  private $captchaSession;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var FieldNameObfuscator */
  private $fieldNameObfuscator;

  /** @var SettingsController */
  private $settings;

  /** @var RequiredCustomFieldValidator */
  private $requiredCustomFieldValidator;

  /** @var SubscriberActions */
  private $subscriberActions;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriptionThrottling */
  private $throttling;

  /** @var StatisticsFormsRepository */
  private $statisticsFormsRepository;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  public function __construct(
    Captcha $subscriptionCaptcha,
    CaptchaSession $captchaSession,
    SubscriberActions $subscriberActions,
    SubscribersFinder $subscribersFinder,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    SubscriptionThrottling $throttling,
    FieldNameObfuscator $fieldNameObfuscator,
    RequiredCustomFieldValidator $requiredCustomFieldValidator,
    SettingsController $settings,
    FormsRepository $formsRepository,
    StatisticsFormsRepository $statisticsFormsRepository,
    WPFunctions $wp
  ) {
    $this->formsRepository = $formsRepository;
    $this->subscriptionCaptcha = $subscriptionCaptcha;
    $this->captchaSession = $captchaSession;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->requiredCustomFieldValidator = $requiredCustomFieldValidator;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
    $this->subscribersFinder = $subscribersFinder;
    $this->wp = $wp;
    $this->throttling = $throttling;
    $this->statisticsFormsRepository = $statisticsFormsRepository;
  }

  public function subscribe(array $data): array {
    $form = $this->getForm($data);

    if (!empty($data['email'])) {
      throw new UnexpectedValueException(__('Please leave the first field empty.', 'mailpoet'));
    }

    $captchaSettings = $this->settings->get('captcha');
    $data = $this->initCaptcha($captchaSettings, $form, $data);
    $data = $this->deobfuscateFormPayload($data);

    try {
      $this->requiredCustomFieldValidator->validate($data, $form);
    } catch (\Exception $e) {
      throw new UnexpectedValueException($e->getMessage());
    }

    $segmentIds = $this->getSegmentIds($form, $data['segments'] ?? []);
    unset($data['segments']);

    $meta = $this->validateCaptcha($captchaSettings, $data);
    if (isset($meta['error'])) {
      return $meta;
    }

    // only accept fields defined in the form
    $formFieldIds = array_filter(array_map(function (array $formField): ?string {
      if (!isset($formField['id'])) {
        return null;
      }
      return is_numeric($formField['id']) ? "cf_{$formField['id']}" : $formField['id'];
    }, $form->getBlocksByTypes(FormEntity::FORM_FIELD_TYPES)));
    $data = array_intersect_key($data, array_flip($formFieldIds));

    // make sure we don't allow too many subscriptions with the same ip address
    $timeout = $this->throttling->throttle();

    if ($timeout > 0) {
      $timeToWait = $this->throttling->secondsToTimeString($timeout);
      $meta['refresh_captcha'] = true;
      // translators: %s is the amount of time the user has to wait.
      $meta['error'] = sprintf(__('You need to wait %s before subscribing again.', 'mailpoet'), $timeToWait);
      return $meta;
    }

    /**
     * Fires before a subscription gets created.
     * To interrupt the subscription process, you can throw an MailPoet\Exception.
     * The error message will then be displayed to the user.
     *
     * @param array      $data       The subscription data.
     * @param array      $segmentIds The segment IDs the user gets subscribed to.
     * @param FormEntity $form       The form the user used to subscribe.
     */
    $this->wp->doAction('mailpoet_subscription_before_subscribe', $data, $segmentIds, $form);

    $subscriber = $this->subscriberActions->subscribe($data, $segmentIds);

    if (!empty($captchaSettings['type']) && $captchaSettings['type'] === Captcha::TYPE_BUILTIN) {
      // Captcha has been verified, invalidate the session vars
      $this->captchaSession->reset();
    }

    // record form statistics
    $this->statisticsFormsRepository->record($form, $subscriber);

    $formSettings = $form->getSettings();
    if (!empty($formSettings['on_success'])) {
      if ($formSettings['on_success'] === 'page') {
        // redirect to a page on a success, pass the page url in the meta
        $meta['redirect_url'] = $this->wp->getPermalink($formSettings['success_page']);
      } else if ($formSettings['on_success'] === 'url') {
        $meta['redirect_url'] = $formSettings['success_url'];
      }
    }

    return $meta;
  }

  /**
   * Checks if the subscriber is subscribed to any segments in the form
   *
   * @param  FormEntity       $form       The form entity
   * @param  SubscriberEntity $subscriber The subscriber entity
   * @return bool True if the subscriber is subscribed to any of the segments in the form
   */
  public function isSubscribedToAnyFormSegments(FormEntity $form, SubscriberEntity $subscriber): bool {
    $formSegments = array_merge( $form->getSegmentBlocksSegmentIds(), $form->getSettingsSegmentIds());

    $subscribersFound = $this->subscribersFinder->findSubscribersInSegments([$subscriber->getId()], $formSegments);
    if (!empty($subscribersFound)) return true;

    return false;
  }

  private function deobfuscateFormPayload($data): array {
    return $this->fieldNameObfuscator->deobfuscateFormPayload($data);
  }

  private function initCaptcha(?array $captchaSettings, FormEntity $form, array $data): array {
    if (!$captchaSettings || !isset($captchaSettings['type'])) {
      return $data;
    }

    if ($captchaSettings['type'] === Captcha::TYPE_BUILTIN) {
      $captchaSessionId = isset($data['captcha_session_id']) ? $data['captcha_session_id'] : null;
      $this->captchaSession->init($captchaSessionId);
      if (!isset($data['captcha'])) {
        // Save form data to session
        $this->captchaSession->setFormData(array_merge($data, ['form_id' => $form->getId()]));
      } elseif ($this->captchaSession->getFormData()) {
        // Restore form data from session
        $data = array_merge($this->captchaSession->getFormData(), ['captcha' => $data['captcha']]);
      }
      // Otherwise use the post data
    }
    return $data;
  }

  private function validateCaptcha($captchaSettings, $data): array {
    if (empty($captchaSettings['type'])) {
      return [];
    }

    $meta = [];
    $isBuiltinCaptchaRequired = false;
    if ($captchaSettings['type'] === Captcha::TYPE_BUILTIN) {
      $isBuiltinCaptchaRequired = $this->subscriptionCaptcha->isRequired(isset($data['email']) ? $data['email'] : '');
      if ($isBuiltinCaptchaRequired && empty($data['captcha'])) {
        $meta['redirect_url'] = $this->subscriptionUrlFactory->getCaptchaUrl($this->captchaSession->getId());
        $meta['error'] = __('Please fill in the CAPTCHA.', 'mailpoet');
        return $meta;
      }
    }

    if (Captcha::isReCaptcha($captchaSettings['type']) && empty($data['recaptchaResponseToken'])) {
      return ['error' => __('Please check the CAPTCHA.', 'mailpoet')];
    }

    if (Captcha::isReCaptcha($captchaSettings['type'])) {
      if ($captchaSettings['type'] === Captcha::TYPE_RECAPTCHA_INVISIBLE) {
        $secretToken = $captchaSettings['recaptcha_invisible_secret_token'];
      } else {
        $secretToken = $captchaSettings['recaptcha_secret_token'];
      }

      $response = empty($data['recaptchaResponseToken']) ? $data['recaptcha-no-js'] : $data['recaptchaResponseToken'];
      $response = $this->wp->wpRemotePost('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
          'secret' => $secretToken,
          'response' => $response,
        ],
      ]);
      if (is_wp_error($response)) {
        return ['error' => __('Error while validating the CAPTCHA.', 'mailpoet')];
      }
      $response = json_decode(wp_remote_retrieve_body($response));
      if (empty($response->success)) {
        return ['error' => __('Error while validating the CAPTCHA.', 'mailpoet')];
      }

    } elseif ($captchaSettings['type'] === Captcha::TYPE_BUILTIN && $isBuiltinCaptchaRequired) {
      $captchaHash = $this->captchaSession->getCaptchaHash();
      if (empty($captchaHash)) {
        $meta['error'] = __('Please regenerate the CAPTCHA.', 'mailpoet');
      } elseif (!hash_equals(strtolower($data['captcha']), strtolower($captchaHash))) {
        $this->captchaSession->setCaptchaHash(null);
        $meta['refresh_captcha'] = true;
        $meta['error'] = __('The characters entered do not match with the previous CAPTCHA.', 'mailpoet');
      }
    }

    return $meta;
  }

  private function getSegmentIds(FormEntity $form, array $segmentIds): array {

    // If form contains segment selection blocks allow only segments ids configured in those blocks
    $segmentBlocksSegmentIds = $form->getSegmentBlocksSegmentIds();
    if (!empty($segmentBlocksSegmentIds)) {
      $segmentIds = array_intersect($segmentIds, $segmentBlocksSegmentIds);
    } else {
      $segmentIds = $form->getSettingsSegmentIds();
    }

    if (empty($segmentIds)) {
      throw new UnexpectedValueException(__('Please select a list.', 'mailpoet'));
    }

    return $segmentIds;
  }

  private function getForm(array $data): FormEntity {
    $formId = (isset($data['form_id']) ? (int)$data['form_id'] : false);
    $form = $this->formsRepository->findOneById($formId);

    if (!$form) {
      throw new NotFoundException(__('Please specify a valid form ID.', 'mailpoet'));
    }

    return $form;
  }
}
