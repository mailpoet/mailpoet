<?php

namespace MailPoet\Subscription\Captcha\Validator;

use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscription\Captcha\CaptchaPhrase;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class BuiltInCaptchaValidator implements CaptchaValidator {


  /** @var SubscriptionUrlFactory  */
  private $subscriptionUrlFactory;

  /** @var CaptchaPhrase  */
  private $captchaPhrase;

  /** @var CaptchaSession  */
  private $captchaSession;

  /** @var WPFunctions  */
  private $wp;

  /** @var SubscriberIPsRepository  */
  private $subscriberIPsRepository;

  public function __construct(
    SubscriptionUrlFactory $subscriptionUrlFactory,
    CaptchaPhrase $captchaPhrase,
    CaptchaSession $captchaSession,
    WPFunctions $wp,
    SubscriberIPsRepository $subscriberIPsRepository
  ) {
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->captchaPhrase = $captchaPhrase;
    $this->captchaSession = $captchaSession;
    $this->wp = $wp;
    $this->subscriberIPsRepository = $subscriberIPsRepository;
  }

  public function validate(array $data): bool {
    $isBuiltinCaptchaRequired = $this->isRequired(isset($data['email']) ? $data['email'] : '');
    if (!$isBuiltinCaptchaRequired) {
      return true;
    }
    if (empty($data['captcha'])) {
      throw new ValidationError(
        __('Please fill in the CAPTCHA.', 'mailpoet'),
        [
          'redirect_url' => $this->subscriptionUrlFactory->getCaptchaUrl($this->getSessionId()),
        ]
      );
    }
    $captchaHash = $this->captchaPhrase->getPhrase();
    if (empty($captchaHash)) {
      throw new ValidationError(
        __('Please regenerate the CAPTCHA.', 'mailpoet'),
        [
          'redirect_url' => $this->subscriptionUrlFactory->getCaptchaUrl($this->getSessionId()),
        ]
      );
    }

    if (!hash_equals(strtolower($data['captcha']), strtolower($captchaHash))) {
      $this->captchaPhrase->resetPhrase();
      throw new ValidationError(
        __('The characters entered do not match with the previous CAPTCHA.', 'mailpoet'),
        [
          'refresh_captcha' => true,
        ]
      );
    }

    return true;

  }

  private function getSessionId() {
    $id = $this->captchaSession->getId();
    if ($id === null) {
      $this->captchaSession->init();
      $id = $this->captchaSession->getId();
    }
    return $id;
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
      $subscriber = Subscriber::where('email', $subscriberEmail)->findOne();
      if (
        $subscriber instanceof Subscriber
        && $subscriber->countConfirmations >= $subscriptionCaptchaRecipientLimit
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
