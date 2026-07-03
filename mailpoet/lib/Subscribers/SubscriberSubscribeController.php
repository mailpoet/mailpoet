<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers;

use MailPoet\Captcha\BehavioralSignals;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\RecaptchaValidator;
use MailPoet\Captcha\Validator\TurnstileValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\NotFoundException;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsFormsRepository;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\Tags\TagRepository;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberSubscribeController {
  /** @var FormsRepository */
  private $formsRepository;

  /** @var CaptchaSession */
  private $captchaSession;

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

  /** @var TagRepository */
  private $tagRepository;

  /** @var SubscriberTagRepository */
  private $subscriberTagRepository;
  /** @var CaptchaValidator  */
  private $builtInCaptchaValidator;

  /** @var RecaptchaValidator  */
  private $recaptchaValidator;

  /** @var TurnstileValidator  */
  private $turnstileValidator;

  /** @var BehavioralSignals */
  private $behavioralSignals;

  public function __construct(
    CaptchaSession $captchaSession,
    SubscriberActions $subscriberActions,
    SubscribersFinder $subscribersFinder,
    SubscriptionThrottling $throttling,
    FieldNameObfuscator $fieldNameObfuscator,
    RequiredCustomFieldValidator $requiredCustomFieldValidator,
    SettingsController $settings,
    FormsRepository $formsRepository,
    StatisticsFormsRepository $statisticsFormsRepository,
    TagRepository $tagRepository,
    SubscriberTagRepository $subscriberTagRepository,
    WPFunctions $wp,
    CaptchaValidator $builtInCaptchaValidator,
    RecaptchaValidator $recaptchaValidator,
    TurnstileValidator $turnstileValidator,
    BehavioralSignals $behavioralSignals
  ) {
    $this->formsRepository = $formsRepository;
    $this->captchaSession = $captchaSession;
    $this->requiredCustomFieldValidator = $requiredCustomFieldValidator;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
    $this->subscribersFinder = $subscribersFinder;
    $this->wp = $wp;
    $this->throttling = $throttling;
    $this->statisticsFormsRepository = $statisticsFormsRepository;
    $this->tagRepository = $tagRepository;
    $this->subscriberTagRepository = $subscriberTagRepository;
    $this->builtInCaptchaValidator = $builtInCaptchaValidator;
    $this->recaptchaValidator = $recaptchaValidator;
    $this->turnstileValidator = $turnstileValidator;
    $this->behavioralSignals = $behavioralSignals;
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

    // Keep `segments` in $data until after CAPTCHA validation so that, if the
    // behavioral-baseline path stashes the submission for a deferred challenge,
    // the stash still carries the selected segments for the resubmit.
    $meta = $this->validateCaptcha($captchaSettings, $data, $form);
    if (isset($meta['error'])) {
      return $meta;
    }
    unset($data['segments']);

    $submittedTimeZone = SubscriberEntity::sanitizeTimeZone($data[SubscriberEntity::TIME_ZONE_FIELD_NAME] ?? null);

    // only accept fields defined in the form
    $formFieldIds = array_filter(array_map(function (array $formField): ?string {
      if (!isset($formField['id'])) {
        return null;
      }
      return is_numeric($formField['id']) ? "cf_{$formField['id']}" : $formField['id'];
    }, $form->getBlocksByTypes(FormEntity::FORM_FIELD_TYPES)));
    $data = array_intersect_key($data, array_flip($formFieldIds));
    if ($submittedTimeZone !== null) {
      $data[SubscriberEntity::TIME_ZONE_FIELD_NAME] = $submittedTimeZone;
    }

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

    [$subscriber, $subscriptionMeta] = $this->subscriberActions->subscribe($data, $segmentIds);

    if (
      isset($data['captcha_session_id']) && (
      ($captchaSettings['type'] ?? null) === CaptchaConstants::TYPE_BUILTIN
      || CaptchaConstants::isDisabled($captchaSettings['type'] ?? null)
      )
    ) {
      // Captcha has been verified, invalidate the session vars
      $this->captchaSession->reset($data['captcha_session_id']);
    }

    // record form statistics
    $this->statisticsFormsRepository->record($form, $subscriber);

    // add tags to subscriber if they are filled
    $formSettings = $form->getSettings();
    $this->addTagsToSubscriber($formSettings['tags'] ?? [], $subscriber);

    // Confirmation email failed. We want to show the error message
    if ($subscriptionMeta['confirmationEmailResult'] instanceof \Exception) {
      $meta['error'] = $subscriptionMeta['confirmationEmailResult']->getMessage();
      return $meta;
    }
    if (!empty($subscriptionMeta['error'])) {
      $meta['error'] = $subscriptionMeta['error'];
      return $meta;
    }

    $this->wp->doAction('mailpoet_subscription_after_subscribe', $subscriber, $data, $segmentIds, $form);

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
    $formSegments = array_merge($form->getSegmentBlocksSegmentIds(), $form->getSettingsSegmentIds());

    $subscribersFound = $this->subscribersFinder->findSubscribersInSegments([$subscriber->getId()], $formSegments);
    if (!empty($subscribersFound)) return true;

    return false;
  }

  private function deobfuscateFormPayload($data): array {
    return $this->fieldNameObfuscator->deobfuscateFormPayload($data);
  }

  private function initCaptcha(?array $captchaSettings, FormEntity $form, array $data): array {
    $type = $captchaSettings['type'] ?? null;

    if ($type === CaptchaConstants::TYPE_BUILTIN) {
      // When serving the built-in CAPTCHA for the first time, generate a new session ID.
      if (!isset($data['captcha_session_id'])) {
        $data['captcha_session_id'] = $this->captchaSession->generateSessionId();
      }
      $sessionId = $data['captcha_session_id'];

      if (!isset($data['captcha'])) {
        // Save form data to session
        $this->captchaSession->setFormData($sessionId, array_merge($data, ['form_id' => $form->getId()]));
      } elseif ($this->captchaSession->getFormData($sessionId)) {
        // Restore form data from session, but keep the current request's captcha
        // and behavioral signals so the resubmit reflects accumulated interaction
        // rather than the (possibly bot-like) snapshot from the first submit.
        $preserve = ['captcha' => $data['captcha']];
        if (isset($data[BehavioralSignals::FIELD_NAME])) {
          $preserve[BehavioralSignals::FIELD_NAME] = $data[BehavioralSignals::FIELD_NAME];
        }
        $data = array_merge($this->captchaSession->getFormData($sessionId), $preserve);
      }
      return $data;
    }

    // Disabled with behavioral baseline: restore stashed form data on resubmit
    // (after a previous behavioral escalation). The first submit stashes inside
    // the escalation path; here we only handle the restore side.
    if (
      CaptchaConstants::isDisabled($type)
      && isset($data['captcha_session_id'], $data['captcha'])
    ) {
      $stashed = $this->captchaSession->getFormData($data['captcha_session_id']);
      if (is_array($stashed)) {
        // Keep the current request's behavioral signals over the stash so the
        // resubmit's signal check reflects accumulated interaction, not the
        // (possibly bot-like) snapshot that triggered the original challenge.
        $preserve = [
          'captcha' => $data['captcha'],
          'captcha_session_id' => $data['captcha_session_id'],
        ];
        if (isset($data[BehavioralSignals::FIELD_NAME])) {
          $preserve[BehavioralSignals::FIELD_NAME] = $data[BehavioralSignals::FIELD_NAME];
        }
        $data = array_merge($stashed, $preserve);
      }
    }

    return $data;
  }

  private function validateCaptcha($captchaSettings, $data, FormEntity $form): array {
    $type = $captchaSettings['type'] ?? null;
    try {
      if (CaptchaConstants::isDisabled($type)) {
        $this->enforceBehavioralBaseline($data, $form);
        return [];
      }
      if ($type === CaptchaConstants::TYPE_BUILTIN) {
        $this->builtInCaptchaValidator->validate($data);
        $this->requireHumanSignals($data, $form);
      }
      if (CaptchaConstants::isReCaptcha($type)) {
        $this->recaptchaValidator->validate($data);
      }
      if (CaptchaConstants::isTurnstile($type)) {
        $this->turnstileValidator->validate($data);
      }
    } catch (ValidationError $error) {
      return $error->getMeta();
    }
    return [];
  }

  /**
   * Baseline protection when no CAPTCHA is configured: behavioral signals must
   * look human, otherwise escalate to the built-in CAPTCHA inline challenge.
   * isRequired()'s IP-history heuristic is intentionally bypassed here — the
   * decision is made on per-submission signals, not on the IP's CAPTCHA history.
   * On resubmit (after a previous escalation), signals are re-checked so that
   * solving the CAPTCHA alone isn't enough to bypass the baseline.
   */
  private function enforceBehavioralBaseline(array $data, FormEntity $form): void {
    if (!empty($data['captcha_session_id'])) {
      $this->builtInCaptchaValidator->validateChallenge($data);
    }
    $this->requireHumanSignals($data, $form);
  }

  /**
   * Throws a fresh CAPTCHA challenge unless behavioral signals look human.
   * Admin/editor exempt. The suspect signals are dropped from the stash so the
   * resubmit is evaluated on the current request's freshest counters (via
   * initCaptcha's preserve step).
   */
  private function requireHumanSignals(array $data, FormEntity $form): void {
    if ($this->builtInCaptchaValidator->isUserExemptFromCaptcha()) {
      return;
    }
    if ($this->behavioralSignals->looksHuman($data)) {
      return;
    }
    $stash = array_merge($data, ['form_id' => $form->getId()]);
    unset($stash[BehavioralSignals::FIELD_NAME]);
    $challenge = $this->builtInCaptchaValidator->getInlineCaptchaChallenge($stash);
    throw new ValidationError(__('Please fill in the CAPTCHA.', 'mailpoet'), $challenge);
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

  /**
   * @param string[] $tagNames
   */
  private function addTagsToSubscriber(array $tagNames, SubscriberEntity $subscriber): void {
    foreach ($tagNames as $tagName) {
      $tag = $this->tagRepository->createOrUpdate(['name' => $tagName]);

      $subscriberTag = $subscriber->getSubscriberTag($tag);
      if (!$subscriberTag) {
        $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
        $subscriber->getSubscriberTags()->add($subscriberTag);
        $this->subscriberTagRepository->persist($subscriberTag);
        $this->subscriberTagRepository->flush();
        $this->wp->doAction('mailpoet_subscriber_tag_added', $subscriberTag);
      }
    }
  }
}
