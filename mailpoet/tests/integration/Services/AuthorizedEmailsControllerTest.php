<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Stub\Expected;
use InvalidArgumentException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class AuthorizedEmailsControllerTest extends \MailPoetTest {

  const AUTHORIZED_EMAILS_TYPE_ALL = AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_API_TYPE_ALL;

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->newsletterFactory = new NewsletterFactory();
    delete_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY);
    delete_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
    delete_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  public function _after() {
    parent::_after();
    delete_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY);
    delete_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
    delete_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  public function testItResetsAuthorisedEmailsErrorIfMssIsNotActive() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, 'Error');
    $controller = $this->getController($authorizedEmailsFromApi = null);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItResetsErrorForBundledSmallSenders() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'invalid@email.com']);
    $this->setMailPoetSendingMethod();

    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'shouldSkipAuthorization' => Expected::once(true),
    ]);

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItResetsMailerLogErrorForBundledSmallSenders() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();

    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'shouldSkipAuthorization' => Expected::once(true),
    ]);

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    verify($error)->null();
  }

  public function testItSetsProperErrorForOldUsers() {
    $this->settings->set('installed_at', '2018-03-04');
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  public function testItSetsProperErrorForInvalidDefaultSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  public function testItSetEmptyErrorWhenDefaultSenderAddressIsCorrect() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItSetEmptyErrorWhenDomainIsVerifiedButSenderAddressIsNotAuthorized() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'not-valid-auth@email.com');
    $this->setMailPoetSendingMethod();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once(['authorized' => ['auth@email.com']]),
    ]);

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
      'shouldSkipAuthorization' => false,
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->checkAuthorizedEmailAddresses();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItSetErrorForScheduledNewsletterWithUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SCHEDULED);
  }

  public function testItSetErrorForActiveWelcomeEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_ACTIVE);
  }

  public function testItSetErrorForPostNotificationUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_NOTIFICATION, NewsletterEntity::STATUS_ACTIVE);
  }

  public function testItSetErrorForAutomaticEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::STATUS_ACTIVE);
  }

  public function testItSetErrorForAutomationEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::STATUS_ACTIVE);
  }

  /**
   * @group woo
   */
  public function testItSetErrorForAutomationTransactionalEmailUnauthorizedSender() {
    $this->checkUnauthorizedInNewsletter(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL, NewsletterEntity::STATUS_ACTIVE);
  }

  public function testItResetErrorWhenAllSendersAreCorrect() {
    $this->newsletterFactory
      ->withSenderAddress('auth@email.com')
      ->create();

    $this->newsletterFactory
      ->withSenderAddress('auth@email.com')
      ->create();

    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    verify($error)->null();
  }

  public function testItResetsUnauthorizedErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    verify($error)->null();
  }

  public function testItResetsUnauthorizedErrorInMailerLogWhenDomainIsVerified() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once(['authorized' => ['auth@email.com']]),
    ]);

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
      'shouldSkipAuthorization' => false,
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);

    $controller->checkAuthorizedEmailAddresses();

    $error = MailerLog::getError();
    verify($error)->null();
  }

  public function testItDoesNotResetOtherErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_SEND, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect(is_array($error));
    if (is_array($error)) {
      verify($error['operation'])->equals(MailerError::OPERATION_SEND);
    }
  }

  public function testItDoesNotResetMailerLogItErrorPersists() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['authorized' => ['auth@email.com']]);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect(is_array($error));
    if (is_array($error)) {
      verify($error['operation'])->equals(MailerError::OPERATION_AUTHORIZATION);
    }
  }

  private function checkUnauthorizedInNewsletter($type, $status) {
    $newsletter = $this->newsletterFactory
      ->withSubject('Subject')
      ->withType($type)
      ->withStatus($status)
      ->withSenderAddress('invalid@email.com')
      ->create();

    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $bridgeMock = $this->make(Bridge::class, [
      'isMailpoetSendingServiceEnabled' => Expected::once(true),
      'getAuthorizedEmailAddresses' => Expected::once(['authorized' => ['auth@email.com']]),
    ]);
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'shouldSkipAuthorization' => Expected::once(false),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once([]),
    ]);
    $newslettersRepository = $this->createMock(NewslettersRepository::class);
    $newslettersRepository
      ->expects($this->once())
      ->method('getScheduledStandardEmailsAndActiveAutomaticEmails')
      ->with($this->callback(function (array $automaticEmailTypes): bool {
        return in_array(NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL, $automaticEmailTypes, true);
      }))
      ->willReturn([$newsletter]);

    $controller = $this->getControllerWithCustomMocks([
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
      'NewslettersRepository' => $newslettersRepository,
    ]);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    if (!is_array($error)) {
      $this->fail('Expected unauthorized sender error to be stored in settings.');
    }
    $invalidSenders = $error['invalid_senders_in_newsletters'] ?? null;
    if (!is_array($invalidSenders)) {
      $this->fail('Expected unauthorized newsletter senders to be stored in settings.');
    }
    $invalidSender = $invalidSenders[0] ?? null;
    if (!is_array($invalidSender)) {
      $this->fail('Expected unauthorized newsletter sender details to be stored in settings.');
    }

    verify(count($invalidSenders))->equals(1);
    verify($invalidSender['newsletter_id'])->equals($newsletter->getId());
    verify($invalidSender['sender_address'])->equals('invalid@email.com');
    verify($invalidSender['subject'])->equals('Subject');
  }

  public function testItSetsFromAddressInSettings() {
    $this->settings->set('sender.address', '');
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $controller->setFromEmailAddress('authorized@email.com');
    verify($this->settings->get('sender.address'))->same('authorized@email.com');
  }

  public function testItSetsFromAddressInSettingsWhenDomainIsVerified() {
    $this->settings->set('sender.address', '');

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once(['authorized' => []]),
    ]);

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
      'shouldSkipAuthorization' => false,
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->setFromEmailAddress('authorized@email.com');
    verify($this->settings->get('sender.address'))->same('authorized@email.com');
  }

  public function testSenderAddressIsNotValidForActivationIfRestrictionsApplyAndNotAuthorizedSender() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter))->false();
  }

  public function testSenderAddressIsValidForActivationIfRestrictionsApplyAndAuthorizedSender() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@email.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter))->true();
  }

  public function testSenderAddressIsValidForSendingIfRestrictionsApplyAndAuthorizedSender() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomains' => Expected::once($verifiedDomains),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@email.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter, 'sending'))->true();
  }

  public function testSenderAddressIsValidForSendingIfRestrictionsApplyAndShopDoesNotReturnData() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomains' => Expected::once([]),
      'isCacheAvailable' => Expected::once(false),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@email.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter, 'sending'))->true();
  }

  /**
   * @param string $senderAddress
   * @param array<string, mixed> $overrides
   */
  private function isSenderAddressValidWithMock(string $context, string $senderAddress, array $overrides, bool $requireVerifiedDomain = false, string $mtaMethod = Mailer::METHOD_MAILPOET): bool {
    $this->settings->set('mta.method', $mtaMethod);
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, $overrides);
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress($senderAddress);
    $controller = $this->getControllerWithCustomMocks(['AuthorizedSenderDomainController' => $senderDomainMock]);
    return $controller->isSenderAddressValid($newsletter, $context, $requireVerifiedDomain);
  }

  public function testForcedResumeRejectsUnverifiedDomainForSmallSender() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => false,
      'shouldSkipAuthorization' => false,
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(['email.com']),
      'isLastReloadFailed' => false,
    ], true);
    verify($result)->false();
  }

  public function testForcedResumeAcceptsVerifiedDomain() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => false,
      'shouldSkipAuthorization' => false,
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(['email.com']),
      'isLastReloadFailed' => false,
    ], true);
    verify($result)->true();
  }

  public function testForcedResumeIsValidWhenSendingMethodIsNotMailPoet() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => false,
      'shouldSkipAuthorization' => false,
      'getVerifiedSenderDomainsIgnoringCache' => ['email.com'],
      'isLastReloadFailed' => false,
    ], true, Mailer::METHOD_AMAZONSES);
    verify($result)->true();
  }

  public function testForcedResumeIsValidWhenAuthorizationIsSkipped() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => false,
      'shouldSkipAuthorization' => true,
      'getVerifiedSenderDomainsIgnoringCache' => ['email.com'],
      'isLastReloadFailed' => false,
    ], true);
    verify($result)->true();
  }

  public function testForcedResumeIsValidWhenReloadFailed() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => false,
      'shouldSkipAuthorization' => false,
      'getVerifiedSenderDomainsIgnoringCache' => [],
      'isLastReloadFailed' => true,
    ], true);
    verify($result)->true();
  }

  public function testSenderAddressIsValidForResumeIfAuthorizedSender() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::never(),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(['email.com']),
      'getVerifiedSenderDomains' => Expected::never(),
      'isLastReloadFailed' => false,
    ]);
    verify($result)->true();
  }

  public function testSenderAddressIsNotValidForResumeIfUnauthorizedSender() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::never(),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(['email.com']),
      'getVerifiedSenderDomains' => Expected::never(),
      'isLastReloadFailed' => false,
    ]);
    verify($result)->false();
  }

  public function testSenderAddressIsNotValidForResumeIfThereAreNoDomainsAndReloadSucceeded() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once([]),
      'isLastReloadFailed' => false,
    ]);
    verify($result)->false();
  }

  public function testSenderAddressIsValidForResumeIfReloadFailed() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once([]),
      'isLastReloadFailed' => true,
    ]);
    verify($result)->true();
  }

  private function senderDomainControllerWithBridge(Bridge $bridge): AuthorizedSenderDomainController {
    return new AuthorizedSenderDomainController(
      $bridge,
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      $this->settings,
      $this->diContainer->get(Subscribers::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }

  private function isSenderAddressValidWithBackoff(string $context, Bridge $bridge): bool {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, null);
    set_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY, AuthorizedSenderDomainController::UPPER_LIMIT + 1);
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY, 1, 300);
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');
    $controller = $this->getControllerWithCustomMocks(['AuthorizedSenderDomainController' => $this->senderDomainControllerWithBridge($bridge)]);
    return $controller->isSenderAddressValid($newsletter, $context);
  }

  public function testSenderAddressRecheckHonoursReloadFailureBackoff() {
    $bridge = $this->make(Bridge::class, ['getRawSenderDomainData' => Expected::never()]);
    verify($this->isSenderAddressValidWithBackoff('recheck', $bridge))->true();
  }

  public function testSenderAddressResumeIgnoresReloadFailureBackoff() {
    $bridge = $this->make(Bridge::class, [
      'getRawSenderDomainData' => Expected::once([['domain' => 'email.com', 'domain_status' => 'verified', 'dns' => []]]),
    ]);
    verify($this->isSenderAddressValidWithBackoff('resume', $bridge))->false();
  }

  public function testSenderAddressRecheckUsesFreshDomainsWithBackoff() {
    $result = $this->isSenderAddressValidWithMock('recheck', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(function (bool $respectBackoff = false) {
        return $respectBackoff ? ['email.com'] : [];
      }),
      'getVerifiedSenderDomains' => Expected::never(),
      'isLastReloadFailed' => false,
    ]);
    verify($result)->false();
    $result = $this->isSenderAddressValidWithMock('recheck', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once(function (bool $respectBackoff = false) {
        return $respectBackoff ? ['email.com'] : [];
      }),
      'isLastReloadFailed' => false,
    ]);
    verify($result)->true();
  }

  public function testSenderAddressRecheckIsValidWhenReloadFailed() {
    $result = $this->isSenderAddressValidWithMock('recheck', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomainsIgnoringCache' => [],
      'isLastReloadFailed' => true,
    ]);
    verify($result)->true();
  }

  public function testSenderAddressIsValidForResumeIfRestrictionsDoNotApply() {
    $result = $this->isSenderAddressValidWithMock('resume', 'contact@unauthorized.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(false),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::never(),
    ]);
    verify($result)->true();
  }

  public function testSenderAddressForSendingUsesCachedDomains() {
    $result = $this->isSenderAddressValidWithMock('sending', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomains' => Expected::once(['email.com']),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::never(),
    ]);
    verify($result)->true();
  }

  public function testSenderAddressIsNotValidForSendingIfCachedDomainListIsEmpty() {
    $result = $this->isSenderAddressValidWithMock('sending', 'contact@email.com', [
      'isAuthorizedDomainRequiredForExistingCampaigns' => Expected::once(true),
      'getVerifiedSenderDomains' => Expected::once([]),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::never(),
      'isCacheAvailable' => Expected::once(true),
    ]);
    verify($result)->false();
  }

  public function testSenderAddressRequirementBoundaryForExistingCampaigns() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $this->settings->set(Bridge::SUBSCRIPTION_TYPE_SETTING_NAME, null);
    set_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY, [['domain' => 'email.com', 'domain_status' => 'verified']]);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');

    set_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY, AuthorizedSenderDomainController::UPPER_LIMIT);
    $senderDomainController = new AuthorizedSenderDomainController(
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      $this->settings,
      $this->diContainer->get(Subscribers::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $controller = $this->getControllerWithCustomMocks(['AuthorizedSenderDomainController' => $senderDomainController]);
    verify($controller->isSenderAddressValid($newsletter, 'sending'))->true();

    set_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY, AuthorizedSenderDomainController::UPPER_LIMIT + 1);
    verify($controller->isSenderAddressValid($newsletter, 'sending'))->false();

    delete_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY);
  }

  public function testSenderAddressIsValidForSendingIfThereIsNoCacheAndShopReturnsNothing() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
    set_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY, AuthorizedSenderDomainController::UPPER_LIMIT + 1);
    $bridge = $this->make(Bridge::class, ['getRawSenderDomainData' => null]);
    $senderDomainController = new AuthorizedSenderDomainController(
      $bridge,
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      $this->settings,
      $this->diContainer->get(Subscribers::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');

    $controller = $this->getControllerWithCustomMocks(['AuthorizedSenderDomainController' => $senderDomainController]);
    verify($controller->isSenderAddressValid($newsletter, 'sending'))->true();

    delete_transient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
    delete_transient(AuthorizedSenderDomainController::SENDER_DOMAINS_RELOAD_FAILED_KEY);
  }

  public function testSenderAddressIsValidForActivationIfNotACampaign() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::never(),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::never(),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter))->true();
  }

  public function testSenderAddressIsValidForActivationIfRestrictionsDoNotApply() {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);

    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'isAuthorizedDomainRequiredForNewCampaigns' => Expected::once(false),
      'getVerifiedSenderDomainsIgnoringCache' => Expected::never(),
    ]);

    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setSenderAddress('contact@unauthorized.com');

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    verify($controller->isSenderAddressValid($newsletter))->true();
  }

  public function testItSetsFromAddressInScheduledEmails() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $newsletter->setSenderAddress('invalid@email.com');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $this->settings->set('sender.address', '');
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $controller->setFromEmailAddress('authorized@email.com');
    verify($newsletter->getSenderAddress())->same('authorized@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    verify($newsletter->getSenderAddress())->same('authorized@email.com');
  }

  public function testItSetsFromAddressInAutomaticEmails() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSenderAddress('invalid@email.com');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $this->settings->set('sender.address', '');
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $controller->setFromEmailAddress('authorized@email.com');
    verify($newsletter->getSenderAddress())->same('authorized@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    verify($newsletter->getSenderAddress())->same('authorized@email.com');
  }

  public function testItDoesntSetFromAddressForSentEmails() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $newsletter->setSenderAddress('invalid@email.com');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $this->settings->set('sender.address', '');
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $controller->setFromEmailAddress('authorized@email.com');
    verify($newsletter->getSenderAddress())->same('invalid@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    verify($newsletter->getSenderAddress())->same('invalid@email.com');
  }

  public function testItSetsFromAddressForBundledSmallSenders() {
    $this->settings->set('sender.address', 'old@email.com');
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, ['invalid_sender_address' => 'old@email.com']);
    $this->setMailPoetSendingMethod();

    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'shouldSkipAuthorization' => Expected::once(true),
    ]);

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->setFromEmailAddress('any@unauthorized-domain.com');
    verify($this->settings->get('sender.address'))->same('any@unauthorized-domain.com');
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testSetsFromAddressThrowsForUnauthorizedEmail() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Email address 'invalid@email.com' is not authorized");
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $controller->setFromEmailAddress('invalid@email.com');
  }

  public function testItDoesNotCreateNewAuthorizedEmailAddressForAuthorizedEmails() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Email address is already authorized');

    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
    ];
    $controller = $this->getController($array);
    $controller->createAuthorizedEmailAddress('authorized@email.com');
  }

  public function testItDoesNotCreateNewAuthorizedEmailAddressForPendingEmails() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Email address is pending confirmation');

    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
    ];
    $controller = $this->getController($array);
    $controller->createAuthorizedEmailAddress('pending@email.com');
  }

  public function testItCreateNewAuthorizedEmailAddress() {
    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
    ];
    $response = ['status' => true];
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::exactly(2, $array),
      'createAuthorizedEmailAddress' => Expected::once($response),
    ]);
    $mocks = [
      'Bridge' => $bridgeMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $result = $controller->createAuthorizedEmailAddress('new-authorized@email.com');
    verify($result)->equals($response);
  }

  public function testItThrowsAnExceptionForReturnedArrayForCreateNewAuthorizedEmailAddress() {
    $errorMessage = 'some errors';
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage($errorMessage);

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once([]),
      'createAuthorizedEmailAddress' => Expected::once([
        'error' => $errorMessage,
        'message' => $errorMessage,
        'status' => Bridge\API::RESPONSE_STATUS_ERROR,
      ]),
    ]);
    $mocks = [
      'Bridge' => $bridgeMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->createAuthorizedEmailAddress('new-authorized@email.com');
  }

  public function testItReturnsTrueWhenAuthorizedForIsEmailAddressAuthorized() {
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $result = $controller->isEmailAddressAuthorized('authorized@email.com');
    verify($result)->equals(true);
  }

  public function testItReturnsFalseWhenNotAuthorizedForIsEmailAddressAuthorized() {
    $controller = $this->getController(['authorized' => ['authorized@email.com']]);
    $result = $controller->isEmailAddressAuthorized('pending@email.com');
    verify($result)->equals(false);
  }

  public function testItReturnsFalseWhenNoArrayForIsEmailAddressAuthorized() {
    $controller = $this->getController([]);
    $result = $controller->isEmailAddressAuthorized('pending@email.com');
    verify($result)->equals(false);
  }

  public function testItReturnsAPIDataForGetAllAuthorizedEmailAddresses() {
    $apiData = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
      'main' => 'main@email.com',
    ];

    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once($apiData),
    ]);

    // Should ignore cache, when API data is available
    $wpMock = $this->make(WPFunctions::class, [
      'setTransient' => true,
      'getTransient' => [
        'pending' => [],
        'authorized' => [],
        'main' => 'main@email.com',
      ],
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'WPFunctions' => $wpMock,
    ];

    $controller = $this->getControllerWithCustomMocks($mocks);
    $result = $controller->getAuthorizedEmailAddressesIgnoringCache(self::AUTHORIZED_EMAILS_TYPE_ALL);
    verify($result)->equals($apiData);
  }

  public function testItReturnsCachedDataIfAPIIsDownForGetAllAuthorizedEmailAddresses() {
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once(null),
    ]);

    $cacheData = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
      'main' => 'main@email.com',
    ];

    $wpMock = $this->make(WPFunctions::class, [
      'getTransient' => $cacheData,
      'setTransient' => true,
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'WPFunctions' => $wpMock,
    ];

    $controller = $this->getControllerWithCustomMocks($mocks);
    $result = $controller->getAuthorizedEmailAddressesIgnoringCache(self::AUTHORIZED_EMAILS_TYPE_ALL);
    verify($result)->equals($cacheData);
  }

  public function testItReadsAuthorizedEmailsFromCacheFirst() {
    $cached = ['authorized' => ['a@b.com'], 'pending' => [], 'main' => 'a@b.com'];
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::never()]);
    $wpMock = $this->make(WPFunctions::class, ['getTransient' => $cached, 'setTransient' => Expected::never()]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock, 'WPFunctions' => $wpMock]);
    verify($controller->getAuthorizedEmailAddresses())->equals(['a@b.com']);
    verify($controller->getAuthorizedEmailAddresses(self::AUTHORIZED_EMAILS_TYPE_ALL))->equals($cached);
  }

  public function testItCallsBridgeAndCachesWhenCacheIsEmpty() {
    $data = ['authorized' => ['a@b.com'], 'pending' => []];
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once($data)]);
    $wpMock = $this->make(WPFunctions::class, [
      'getTransient' => false,
      'setTransient' => Expected::once(true),
    ]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock, 'WPFunctions' => $wpMock]);
    verify($controller->getAuthorizedEmailAddresses())->equals(['a@b.com']);
  }

  public function testIgnoringCacheUpdatesTheCache() {
    $new = ['authorized' => ['new@b.com'], 'pending' => []];
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once($new)]);
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, ['authorized' => ['old@b.com'], 'pending' => []]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddressesIgnoringCache())->equals(['new@b.com']);
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY))->equals($new);
  }

  public function testIgnoringCacheFallsBackToCacheWhenBridgeFails() {
    $cached = ['authorized' => ['old@b.com'], 'pending' => []];
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(null)]);
    $wpMock = $this->make(WPFunctions::class, [
      'getTransient' => $cached,
      'setTransient' => Expected::once(function ($key) {
        verify($key)->same(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY);
        return true;
      }),
    ]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock, 'WPFunctions' => $wpMock]);
    verify($controller->getAuthorizedEmailAddressesIgnoringCache())->equals(['old@b.com']);
  }

  public function testItReturnsEmptyWhenBridgeFailsAndNoCache() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(null)]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddressesIgnoringCache())->equals([]);
  }

  public function testItCachesValidEmptyResponse() {
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once([]),
    ]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddresses())->equals([]);
    verify($controller->getAuthorizedEmailAddresses())->equals([]);
  }

  public function testFailedBridgeReadSetsBackoffAndLaterReadsSkipBridge() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(null)]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddresses())->equals([]);
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY))->notEmpty();

    $laterController = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($laterController->getAuthorizedEmailAddresses())->equals([]);
  }

  public function testUserActionsIgnoreEmailsBackoff() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY, 1, 300);
    $data = ['authorized' => ['a@b.com'], 'pending' => []];
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once($data)]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddressesIgnoringCache())->equals(['a@b.com']);
  }

  public function testSuccessfulBridgeReadDeletesEmailsBackoff() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY, 1, 300);
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized' => [], 'pending' => []])]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    $controller->getAuthorizedEmailAddressesIgnoringCache();
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY))->false();
  }

  public function testCachedEmailsAreUsedDuringBackoff() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY, 1, 300);
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, ['authorized' => ['a@b.com'], 'pending' => []]);
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::never()]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    verify($controller->getAuthorizedEmailAddresses())->equals(['a@b.com']);
  }

  public function testClearCacheRemovesTheBackoff() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY, 1, 300);
    $this->getControllerWithCustomMocks()->clearCache();
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY))->false();
  }

  public function testClearCacheRemovesTheTransient() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, ['authorized' => ['a@b.com']]);
    $this->getControllerWithCustomMocks()->clearCache();
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY))->false();
  }

  public function testCheckKeepsPreviousErrorWhenThereAreNoAuthorizedEmails() {
    $this->setMailPoetSendingMethod();
    $previous = ['invalid_sender_address' => 'old@email.com'];
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, $previous);
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, ['shouldSkipAuthorization' => false]);
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once(['authorized' => [], 'pending' => []]),
    ]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock, 'AuthorizedSenderDomainController' => $senderDomainMock]);
    verify($controller->checkAuthorizedEmailAddresses())->null();
    verify($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals($previous);
  }

  public function testCreateRefreshesTheCache() {
    $before = ['authorized' => [], 'pending' => []];
    $after = ['authorized' => [], 'pending' => ['new@email.com']];
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::exactly(2, function () use ($before, $after) {
        static $calls = 0;
        return ++$calls === 1 ? $before : $after;
      }),
      'createAuthorizedEmailAddress' => Expected::once(['status' => true]),
    ]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridgeMock]);
    $controller->createAuthorizedEmailAddress('new@email.com');
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY))->equals($after);
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  public function testCachedListWithPendingAddressIsRefreshedFromBridge() {
    $key = AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY;
    set_transient($key, ['authorized' => [], 'pending' => ['new@email.com']], WEEK_IN_SECONDS);
    $fresh = ['authorized' => ['new@email.com'], 'pending' => []];
    $controller = $this->getController($fresh);

    verify($controller->getAuthorizedEmailAddresses())->equals(['new@email.com']);
    verify(get_transient($key))->equals($fresh);
  }

  public function testCachedListWithoutPendingAddressIsNotRefreshed() {
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, ['authorized' => ['auth@email.com'], 'pending' => []], WEEK_IN_SECONDS);
    $controller = $this->getController(null);

    verify($controller->getAuthorizedEmailAddresses())->equals(['auth@email.com']);
  }

  public function testCachedListWithPendingAddressIsReturnedWhenBridgeFails() {
    $cached = ['authorized' => ['auth@email.com'], 'pending' => ['new@email.com']];
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, $cached, WEEK_IN_SECONDS);
    $bridge = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(null)]);
    $controller = $this->getControllerWithCustomMocks(['Bridge' => $bridge]);

    verify($controller->getAuthorizedEmailAddresses(self::AUTHORIZED_EMAILS_TYPE_ALL))->equals($cached);
    verify(get_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY))->notEmpty();
  }

  public function testCachedListWithPendingAddressSkipsBridgeDuringBackoff() {
    $cached = ['authorized' => ['auth@email.com'], 'pending' => ['new@email.com']];
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_KEY, $cached, WEEK_IN_SECONDS);
    set_transient(AuthorizedEmailsController::AUTHORIZED_EMAILS_RELOAD_FAILED_KEY, 1, 300);
    $controller = $this->getController(null);

    verify($controller->getAuthorizedEmailAddresses(self::AUTHORIZED_EMAILS_TYPE_ALL))->equals($cached);
  }

  private function getController($authorizedEmails) {
    if ($authorizedEmails === null) {
      $getEmailsExpectaton = Expected::never();
    } else {
      $getEmailsExpectaton = Expected::once($authorizedEmails);
    }
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => $getEmailsExpectaton]);

    $mocks = [
      'Bridge' => $bridgeMock,
    ];
    return $this->getControllerWithCustomMocks($mocks);
  }

  private function getControllerWithCustomMocks($data = []) {
    $bridgeMock = $data['Bridge'] ?? $this->diContainer->get(Bridge::class);
    $newslettersRepository = $data['NewslettersRepository'] ?? $this->diContainer->get(NewslettersRepository::class);
    $senderDomainController = $data['AuthorizedSenderDomainController'] ?? $this->diContainer->get(AuthorizedSenderDomainController::class);
    $wp = $data['WPFunctions'] ?? $this->diContainer->get(WPFunctions::class);

    return new AuthorizedEmailsController($this->settings, $bridgeMock, $newslettersRepository, $senderDomainController, $wp);
  }
}
