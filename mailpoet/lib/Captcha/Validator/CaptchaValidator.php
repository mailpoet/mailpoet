<?php declare(strict_types = 1);

namespace MailPoet\Captcha\Validator;

use MailPoet\Captcha\CaptchaPhrase;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaSessionLimitException;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaValidator {
  /** @var CaptchaUrlFactory */
  private $captchaUrlFactory;

  /** @var CaptchaPhrase */
  private $captchaPhrase;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriberIPsRepository */
  private $subscriberIPsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var CaptchaSession */
  private $captchaSession;

  public function __construct(
    CaptchaUrlFactory $urlFactory,
    CaptchaPhrase $captchaPhrase,
    WPFunctions $wp,
    SubscriberIPsRepository $subscriberIPsRepository,
    SubscribersRepository $subscribersRepository,
    CaptchaSession $captchaSession
  ) {
    $this->captchaUrlFactory = $urlFactory;
    $this->captchaPhrase = $captchaPhrase;
    $this->wp = $wp;
    $this->subscriberIPsRepository = $subscriberIPsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->captchaSession = $captchaSession;
  }

  public function validate(array $data): bool {
    $isBuiltinCaptchaRequired = $this->isRequired(isset($data['email']) ? $data['email'] : null);
    if (!$isBuiltinCaptchaRequired) {
      return true;
    }
    return $this->validateChallenge($data);
  }

  /**
   * Validates the user's CAPTCHA answer without consulting isRequired().
   * Use this when the caller has already decided a challenge is needed
   * (e.g. the behavioral-signal escalation path).
   *
   * With $allowNewChallenge set to false, a failed check never creates a new
   * session: the existing one is reset and a plain error is thrown.
   *
   * @param array<string,mixed> $data
   */
  public function validateChallenge(array $data, bool $allowNewChallenge = true): bool {
    $sessionId = $data['captcha_session_id'] ?? null;
    $hasValidId = is_string($sessionId) && $this->captchaSession->isValidId($sessionId);

    if (!$allowNewChallenge) {
      if (!$hasValidId || !$this->isAnswerCorrect($sessionId, $data['captcha'] ?? null)) {
        if ($hasValidId) {
          $this->captchaSession->reset($sessionId);
        }
        throw new ValidationError(__('CAPTCHA verification failed. Please try again.', 'mailpoet'));
      }
      $this->captchaPhrase->consume($sessionId);
      $this->captchaSession->reset($sessionId);
      return true;
    }

    try {
      return $this->validateChallengeWithNewChallenges($data, $hasValidId ? $sessionId : null);
    } catch (CaptchaSessionLimitException $e) {
      throw new ValidationError(__('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.', 'mailpoet'));
    }
  }

  /**
   * Validates a challenge that was started earlier and never starts a new one.
   * Logged-in users that are exempt from CAPTCHA pass without an answer.
   *
   * @param array<string,mixed> $data
   */
  public function validateExistingChallenge(array $data): bool {
    if ($this->isUserExemptFromCaptcha()) {
      return true;
    }
    return $this->validateChallenge($data, false);
  }

  /**
   * @param array<string,mixed> $data
   */
  private function validateChallengeWithNewChallenges(array $data, ?string $sessionId): bool {
    if ($sessionId === null) {
      throw $this->newChallengeError($data);
    }

    $answer = $data['captcha'] ?? null;
    if (empty($answer) || !is_string($answer)) {
      if (!$this->captchaSession->exists($sessionId)) {
        throw $this->newChallengeError($data);
      }
      $this->captchaPhrase->createPhrase($sessionId);
      throw new ValidationError(
        __('Please fill in the CAPTCHA.', 'mailpoet'),
        $this->getInlineCaptchaData($sessionId)
      );
    }

    $captchaHash = $this->captchaPhrase->getPhrase($sessionId);
    if (empty($captchaHash)) {
      throw $this->newChallengeError($data);
    }

    if (!hash_equals(strtolower($answer), strtolower($captchaHash))) {
      if ($this->captchaPhrase->registerFailedAttempt($sessionId) >= CaptchaPhrase::MAX_ATTEMPTS) {
        $this->captchaSession->reset($sessionId);
        throw new ValidationError(
          __('Too many incorrect attempts. Here’s a new CAPTCHA to try.', 'mailpoet'),
          $this->getInlineCaptchaChallenge($data)
        );
      }
      $this->captchaPhrase->createPhrase($sessionId);
      throw new ValidationError(
        __('The characters entered do not match with the previous CAPTCHA.', 'mailpoet'),
        [
          'refresh_captcha' => true,
          // Keep redirect_url for non-JavaScript form submissions
          'redirect_url' => $this->captchaUrlFactory->getCaptchaUrlForMPForm($sessionId),
        ]
      );
    }

    $this->captchaPhrase->consume($sessionId);
    return true;
  }

  /**
   * @param mixed $answer
   */
  private function isAnswerCorrect(string $sessionId, $answer): bool {
    if (!is_string($answer) || $answer === '') {
      return false;
    }
    $captchaHash = $this->captchaPhrase->getPhrase($sessionId);
    return !empty($captchaHash) && hash_equals(strtolower($answer), strtolower($captchaHash));
  }

  /**
   * @param array<string,mixed> $data
   */
  private function newChallengeError(array $data): ValidationError {
    return new ValidationError(
      __('Please fill in the CAPTCHA.', 'mailpoet'),
      $this->getInlineCaptchaChallenge($data)
    );
  }

  /**
   * Creates a fresh inline CAPTCHA challenge: generates a session, primes a
   * phrase, and returns the meta the client uses to render it inline.
   * Optionally stashes form data so the non-JS captcha-page fallback can
   * restore the original submission on resubmit.
   *
   * @param array<string, mixed>|null $formData Form data to stash, or null to skip stashing.
   */
  public function getInlineCaptchaChallenge(?array $formData = null): array {
    $sessionId = $this->captchaSession->generateSessionId();
    $this->captchaPhrase->createPhrase($sessionId);
    if ($formData !== null) {
      unset($formData['captcha_session_id'], $formData['captcha']);
      $this->captchaSession->setSubscriptionFormData($sessionId, $formData);
    }
    return $this->getInlineCaptchaData($sessionId);
  }

  public function isRequired($subscriberEmail = null) {
    if ($this->isUserExemptFromCaptcha()) {
      return false;
    }

    $subscriptionCaptchaRecipientLimit = $this->wp->applyFilters('mailpoet_subscription_captcha_recipient_limit', 0);
    if ($subscriptionCaptchaRecipientLimit === 0) {
      return true;
    }

    // Check limits per recipient if enabled
    if ($subscriberEmail) {
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberEmail]);
      if (
        $subscriber && $subscriber->getConfirmationsCount() >= $subscriptionCaptchaRecipientLimit
      ) {
        return true;
      }
    }

    // Check limits per IP address
    /** @var int|string $subscriptionCaptchaWindow */
    $subscriptionCaptchaWindow = $this->wp->applyFilters('mailpoet_subscription_captcha_window', MONTH_IN_SECONDS);

    $subscriberIp = Helpers::getIP();
    if (empty($subscriberIp)) {
      return false;
    }

    $subscriptionCount = $this->subscriberIPsRepository->getCountByIPAndCreatedAtAfterTimeInSeconds(
      $subscriberIp,
      (int)$subscriptionCaptchaWindow
    );

    if ($subscriptionCount > 0) {
      return true;
    }

    return false;
  }

  public function isUserExemptFromCaptcha(): bool {
    if (!$this->wp->isUserLoggedIn()) {
      return false;
    }
    $user = $this->wp->wpGetCurrentUser();
    $roles = $this->wp->applyFilters('mailpoet_subscription_captcha_exclude_roles', ['administrator', 'editor']);
    $stringRoles = is_array($roles) ? array_values(array_filter($roles, 'is_string')) : [];
    return !empty(array_intersect($stringRoles, $user->roles));
  }

  /**
   * Returns data needed to display the captcha inline within the form.
   * Includes redirect_url as a fallback for non-JavaScript submissions.
   */
  private function getInlineCaptchaData(string $sessionId): array {
    return [
      'show_captcha' => true,
      'captcha_session_id' => $sessionId,
      'captcha_image_url' => $this->captchaUrlFactory->getCaptchaImageUrl($sessionId),
      'captcha_audio_url' => $this->captchaUrlFactory->getCaptchaAudioUrl($sessionId),
      // Keep redirect_url for non-JavaScript form submissions
      'redirect_url' => $this->captchaUrlFactory->getCaptchaUrlForMPForm($sessionId),
    ];
  }
}
