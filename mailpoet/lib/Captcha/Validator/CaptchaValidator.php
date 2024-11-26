<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Captcha\Validator;

use MailPoet\Captcha\CaptchaPhrase;
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

  public function __construct(
    CaptchaUrlFactory $urlFactory,
    CaptchaPhrase $captchaPhrase,
    WPFunctions $wp,
    SubscriberIPsRepository $subscriberIPsRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->captchaUrlFactory = $urlFactory;
    $this->captchaPhrase = $captchaPhrase;
    $this->wp = $wp;
    $this->subscriberIPsRepository = $subscriberIPsRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function validate(array $data): bool {
    $isBuiltinCaptchaRequired = $this->isRequired(isset($data['email']) ? $data['email'] : null);
    if (!$isBuiltinCaptchaRequired) {
      return true;
    }

    // session ID must be set at this point
    $sessionId = $data['captcha_session_id'] ?? null;
    if (!$sessionId) {
      throw new ValidationError(__('CAPTCHA verification failed.', 'mailpoet'));
    }

    if (empty($data['captcha'])) {
      throw new ValidationError(
        __('Please fill in the CAPTCHA.', 'mailpoet'),
        [
          'redirect_url' => $this->captchaUrlFactory->getCaptchaUrlForMPForm($sessionId),
        ]
      );
    }

    $captchaHash = $this->captchaPhrase->getPhrase($sessionId);
    if (empty($captchaHash)) {
      throw new ValidationError(
        __('Please regenerate the CAPTCHA.', 'mailpoet'),
        [
          'redirect_url' => $this->captchaUrlFactory->getCaptchaUrlForMPForm($sessionId),
        ]
      );
    }

    if (!hash_equals(strtolower($data['captcha']), strtolower($captchaHash))) {
      $this->captchaPhrase->createPhrase($sessionId);
      throw new ValidationError(
        __('The characters entered do not match with the previous CAPTCHA.', 'mailpoet'),
        [
          'refresh_captcha' => true,
        ]
      );
    }

    return true;
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

  private function isUserExemptFromCaptcha() {
    if (!$this->wp->isUserLoggedIn()) {
      return false;
    }
    $user = $this->wp->wpGetCurrentUser();
    $roles = $this->wp->applyFilters('mailpoet_subscription_captcha_exclude_roles', ['administrator', 'editor']);
    return !empty(array_intersect((array)$roles, $user->roles));
  }
}
