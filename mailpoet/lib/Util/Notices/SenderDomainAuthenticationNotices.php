<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\FreeDomains;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Notice;
use MailPoetVendor\Carbon\Carbon;

class SenderDomainAuthenticationNotices {

  const LOWER_LIMIT = 500;
  const UPPER_LIMIT = 1000;

  const FREE_MAIL_KB_URL = 'https://kb.mailpoet.com/article/259-your-from-address-cannot-be-yahoo-com-gmail-com-outlook-com';
  const SPF_DKIM_DMARC_KB_URL = 'https://kb.mailpoet.com/article/295-spf-dkim-dmarc';

  const ENFORCEMENT_START_TIME = '2024-02-01 00:00:00 UTC';

  const INSTALLED_AFTER_NEW_RESTRICTIONS_OPTION = 'installed_after_new_domain_restrictions';

  private SettingsController $settingsController;

  private Subscribers $subscribersFeatures;

  private FreeDomains $freeDomains;

  private AuthorizedSenderDomainController $authorizedSenderDomainController;

  private Bridge $bridge;

  private NewsletterStatisticsRepository $newsletterStatisticsRepository;

  public function __construct(
    SettingsController $settingsController,
    Subscribers $subscribersFeatures,
    FreeDomains $freeDomains,
    AuthorizedSenderDomainController $authorizedEmailsController,
    Bridge $bridge,
    NewsletterStatisticsRepository $newsletterStatisticsRepository
  ) {
    $this->settingsController = $settingsController;
    $this->subscribersFeatures = $subscribersFeatures;
    $this->freeDomains = $freeDomains;
    $this->authorizedSenderDomainController = $authorizedEmailsController;
    $this->bridge = $bridge;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
  }

  public function getDefaultFromAddress(): string {
    return $this->settingsController->get('sender.address', '');
  }

  public function getDefaultFromDomain(): string {
    return Helpers::extractEmailDomain($this->getDefaultFromAddress());
  }

  public function isFreeMailUser(): bool {
    return $this->freeDomains->isEmailOnFreeDomain($this->getDefaultFromDomain());
  }

  // TODO: Remove after the enforcement date has passed
  public function isEnforcementOfNewRestrictionsInEffect(): bool {
    return Carbon::now() >= Carbon::parse(self::ENFORCEMENT_START_TIME);
  }

  public function isNewUser(): bool {
    $installedVersion = $this->settingsController->get('version');

    // Setup wizard has not been completed
    if ($installedVersion === null) {
      return true;
    }

    $installedAfterNewDomainRestrictions = $this->settingsController->get(self::INSTALLED_AFTER_NEW_RESTRICTIONS_OPTION, false);

    if ($installedAfterNewDomainRestrictions) {
      return true;
    }

    return $this->newsletterStatisticsRepository->countBy([]) === 0;
  }

  public function init($shouldDisplay): ?Notice {
    if (
      !$shouldDisplay
      || !$this->bridge->isMailpoetSendingServiceEnabled()
      || in_array($this->getDefaultFromDomain(), $this->authorizedSenderDomainController->getFullyVerifiedSenderDomains(true))
      || $this->isNewUser()
      || $this->isFreeMailUser() && $this->subscribersFeatures->getSubscribersCount() <= self::LOWER_LIMIT
    ) {
      return null;
    }

    return $this->display();
  }

  public function display(): Notice {
    $contactCount = $this->subscribersFeatures->getSubscribersCount();
    $isFreeMailUser = $this->isFreeMailUser();

    $noticeContent = $isFreeMailUser
      ? $this->getNoticeContentForFreeMailUsers($contactCount)
      : $this->getNoticeContentForBrandedDomainUsers($this->isPartiallyVerified(), $contactCount);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    if ($this->isErrorStyle()) {
      return Notice::displayError($noticeContent, $extraClasses, '', true, false);
    }

    return Notice::displayWarning($noticeContent, $extraClasses);
  }

  public function isErrorStyle(): bool {
    if (!$this->isEnforcementOfNewRestrictionsInEffect()) {
      return false;
    }
    if (
      $this->subscribersFeatures->getSubscribersCount() < self::UPPER_LIMIT
      || $this->isPartiallyVerified()
    ) {
      return false;
    }

    return true;
  }

  public function isPartiallyVerified(): bool {
    return in_array($this->getDefaultFromDomain(), $this->authorizedSenderDomainController->getPartiallyVerifiedSenderDomains(true));
  }

  public function getNoticeContentForFreeMailUsers(int $contactCount): string {
    if (!$this->isEnforcementOfNewRestrictionsInEffect()) {
      // translators: %1$s is the domain of the user's default from address, %2$s is a rewritten version of their default from address, %3$s is HTML for an 'update sender' button, and %4$s is HTML for a Learn More button
      return sprintf(__("<strong>Update your sender email address to a branded domain by February 1st, 2024 to continue sending your campaigns.</strong>
<span>Starting on February 1st, 2024, MailPoet will no longer be able to send from email addresses on shared 3rd party domains like <strong>%1\$s</strong>. Please change your campaigns to send from an email address on your site's branded domain. Your emails will temporarily be sent from <strong>%2\$s</strong>.</span> <p>%3\$s &nbsp; %4\$s</p>", 'mailpoet'),
        "@" . $this->getDefaultFromDomain(),
        $this->authorizedSenderDomainController->getRewrittenEmailAddress($this->getDefaultFromAddress()),
        $this->getUpdateSenderButton(),
        $this->getLearnMoreAboutFreeMailButton()
      );
    }

    if ($contactCount <= self::UPPER_LIMIT) {
      // translators: %1$s is the domain of the user's default from address, %2$s is a rewritten version of their default from address, %3$s is HTML for an 'update sender' button, and %4$s is HTML for a Learn More button
      return sprintf(__("<strong>Update your sender email address to a branded domain to continue sending your campaigns.</strong>
<span>MailPoet can no longer send from email addresses on shared 3rd party domains like <strong>%1\$s</strong>. Please change your campaigns to send from an email address on your site's branded domain. Your existing scheduled and active emails will temporarily be sent from <strong>%2\$s</strong>.</span> <p>%3\$s &nbsp; %4\$s</p>", 'mailpoet'),
        "@" . $this->getDefaultFromDomain(),
        $this->authorizedSenderDomainController->getRewrittenEmailAddress($this->getDefaultFromAddress()),
        $this->getUpdateSenderButton(),
        $this->getLearnMoreAboutFreeMailButton()
      );
    }

    // translators: %1$s is the domain of the user's default from address, %2$s is a rewritten version of their default from address, %3$s is HTML for an 'update sender' button, and %4$s is HTML for a Learn More button
    return sprintf(__("<strong>Your newsletters and post notifications have been paused. Update your sender email address to a branded domain to continue sending your campaigns.</strong>
<span>MailPoet can no longer send from email addresses on shared 3rd party domains like <strong>%1\$s</strong>. Please change your campaigns to send from an email address on your site's branded domain. Your marketing automations and transactional emails will temporarily be sent from <strong>%2\$s</strong>.</span> <p>%3\$s &nbsp; %4\$s</p>", 'mailpoet'),
      "@" . $this->getDefaultFromDomain(),
      $this->authorizedSenderDomainController->getRewrittenEmailAddress($this->getDefaultFromAddress()),
      $this->getUpdateSenderButton(),
      $this->getLearnMoreAboutFreeMailButton()
    );
  }

  public function getNoticeContentForBrandedDomainUsers(bool $isPartiallyVerified, int $contactCount): string {
    if (!$this->isEnforcementOfNewRestrictionsInEffect() || $isPartiallyVerified || $contactCount <= self::LOWER_LIMIT) {
      // translators: %1$s is HTML for an 'authenticate domain' button, %2$s is HTML for a Learn More button
      return sprintf(__("<strong>Authenticate your sender domain to improve email delivery rates.</strong>
<span>Major mailbox providers require you to authenticate your sender domain to confirm you sent the emails, and may place unauthenticated emails in the \"Spam\" folder. Please authenticate your sender domain to ensure your marketing campaigns are compliant and will reach your contacts.</span><p>%1\$s &nbsp; %2\$s</p>", 'mailpoet'),
        $this->getAuthenticateDomainButton(),
        $this->getLearnMoreAboutSpfDkimDmarcButton()
      );
    }

    if ($contactCount <= self::UPPER_LIMIT) {
      // translators: %1$s is a rewritten version of the user's default from address, %2$s is HTML for an 'authenticate domain' button, %3$s is HTML for a Learn More button
      return sprintf(__("<strong>Authenticate your sender domain to send new emails.</strong>
      <span>Major mailbox providers require you to authenticate your sender domain to confirm you sent the emails, and may place unauthenticated emails in the \"Spam\" folder. Please authenticate your sender domain to ensure your marketing campaigns are compliant and will reach your contacts. Your existing scheduled and active emails will temporarily be sent from <strong>%1\$s</strong>.</span> <p>%2\$s &nbsp; %3\$s</span>", 'mailpoet'),
        $this->authorizedSenderDomainController->getRewrittenEmailAddress($this->getDefaultFromAddress()),
        $this->getAuthenticateDomainButton(),
        $this->getLearnMoreAboutSpfDkimDmarcButton()
      );
    }

    // translators: %1$s is a rewritten version of the user's default from address, %2$s is HTML for an 'authenticate domain' button, %3$s is HTML for a Learn More button
    return sprintf(__("<strong>Your newsletters and post notifications have been paused. Authenticate your sender domain to continue sending.</strong>
<span>Major mailbox providers require you to authenticate your sender domain to confirm you sent the emails, and may place unauthenticated emails in the \"Spam\" folder. Please authenticate your sender domain to ensure your marketing campaigns are compliant and will reach your contacts. Your marketing automations and transactional emails will temporarily be sent from <strong>%1\$s</strong>.</span> <p>%2\$s &nbsp; %3\$s</p>", 'mailpoet'),
      $this->authorizedSenderDomainController->getRewrittenEmailAddress($this->getDefaultFromAddress()),
      $this->getAuthenticateDomainButton(),
      $this->getLearnMoreAboutSpfDkimDmarcButton()
    );
  }

  public function getUpdateSenderButton(): string {
    $buttonClass = $this->subscribersFeatures->getSubscribersCount() > self::UPPER_LIMIT
      ? 'button-primary'
      : 'button-secondary';
    $button = sprintf('<a href="admin.php?page=mailpoet-settings" class="button %1$s">%2$s</a>', $buttonClass, __('Update sender email', 'mailpoet'));
    return $button;
  }

  public function getLearnMoreAboutFreeMailButton(): string {
    $button = '<a href="' . self::FREE_MAIL_KB_URL . '" rel="noopener noreferer" target="_blank" class="button button-link">' . __('Learn more', 'mailpoet') . '</a>';
    return $button;
  }

  public function getLearnMoreAboutSpfDkimDmarcButton(): string {
    $button = '<a href="' . self::SPF_DKIM_DMARC_KB_URL . '" rel="noopener noreferer" target="_blank" class="button button-link">' . __('Learn more', 'mailpoet') . '</a>';
    return $button;
  }

  public function getAuthenticateDomainButton() {
    $buttonClass = $this->isErrorStyle()
      ? 'button-primary'
      : 'button-secondary';
    $button = sprintf('<a href="#" class="button %s mailpoet-js-button-authorize-email-and-sender-domain" data-email="%s" data-type="domain">%s</a>',
      $buttonClass,
      esc_attr($this->getDefaultFromAddress()),
      __('Authenticate domain', 'mailpoet')
    );
    return $button;
  }
}
