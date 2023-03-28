<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Stub\Expected;
use InvalidArgumentException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoetVendor\Carbon\Carbon;

class AuthorizedEmailsControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->newsletterFactory = new NewsletterFactory();
  }

  public function testItResetsAuthorisedEmailsErrorIfMssIsNotActive() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING, 'Error');
    $controller = $this->getController($authorizedEmailsFromApi = null);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItSetsProperErrorForOldUsers() {
    $this->settings->set('installed_at', '2018-03-04');
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  public function testItSetsProperErrorForInvalidDefaultSender() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->equals(['invalid_sender_address' => 'invalid@email.com']);
  }

  public function testItSetEmptyErrorWhenDefaultSenderAddressIsCorrect() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
  }

  public function testItSetEmptyErrorWhenDomainIsVerifiedButSenderAddressIsNotAuthorized() {
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'not-valid-auth@email.com');
    $this->setMailPoetSendingMethod();

    $authorizedEmails = ['auth@email.com'];
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once($authorizedEmails),
    ]);

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->checkAuthorizedEmailAddresses();
    expect($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING))->null();
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
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    expect($error)->null();
  }

  public function testItResetsUnauthorizedErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect($error)->null();
  }

  public function testItResetsUnauthorizedErrorInMailerLogWhenDomainIsVerified() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();

    $authorizedEmails = ['auth@email.com'];
    $bridgeMock = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => Expected::once($authorizedEmails),
    ]);

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
    ]);

    $mocks = [
      'Bridge' => $bridgeMock,
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);

    $controller->checkAuthorizedEmailAddresses();

    $error = MailerLog::getError();
    expect($error)->null();
  }

  public function testItDoesNotResetOtherErrorInMailerLog() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_SEND, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'auth@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect(is_array($error));
    if (is_array($error)) {
      expect($error['operation'])->equals(MailerError::OPERATION_SEND);
    }
  }

  public function testItDoesNotResetMailerLogItErrorPersists() {
    $log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, 'message');
    MailerLog::updateMailerLog($log);
    $this->settings->set('installed_at', new Carbon());
    $this->settings->set('sender.address', 'invalid@email.com');
    $this->setMailPoetSendingMethod();
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = MailerLog::getError();
    expect(is_array($error));
    if (is_array($error)) {
      expect($error['operation'])->equals(MailerError::OPERATION_AUTHORIZATION);
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
    $controller = $this->getController($authorizedEmailsFromApi = ['auth@email.com']);
    $controller->checkAuthorizedEmailAddresses();
    $error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    expect(count($error['invalid_senders_in_newsletters']))->equals(1);
    expect($error['invalid_senders_in_newsletters'][0]['newsletter_id'])->equals($newsletter->getId());
    expect($error['invalid_senders_in_newsletters'][0]['sender_address'])->equals('invalid@email.com');
    expect($error['invalid_senders_in_newsletters'][0]['subject'])->equals('Subject');
  }

  public function testItSetsFromAddressInSettings() {
    $this->settings->set('sender.address', '');
    $controller = $this->getController(['authorized@email.com']);
    $controller->setFromEmailAddress('authorized@email.com');
    expect($this->settings->get('sender.address'))->same('authorized@email.com');
  }

  public function testItSetsFromAddressInSettingsWhenDomainIsVerified() {
    $this->settings->set('sender.address', '');

    $verifiedDomains = ['email.com'];
    $senderDomainMock = $this->make(AuthorizedSenderDomainController::class, [
      'getVerifiedSenderDomainsIgnoringCache' => Expected::once($verifiedDomains),
    ]);

    $mocks = [
      'AuthorizedSenderDomainController' => $senderDomainMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $controller->setFromEmailAddress('authorized@email.com');
    expect($this->settings->get('sender.address'))->same('authorized@email.com');
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
    $controller = $this->getController(['authorized@email.com']);
    $controller->setFromEmailAddress('authorized@email.com');
    expect($newsletter->getSenderAddress())->same('authorized@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    expect($newsletter->getSenderAddress())->same('authorized@email.com');
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
    $controller = $this->getController(['authorized@email.com']);
    $controller->setFromEmailAddress('authorized@email.com');
    expect($newsletter->getSenderAddress())->same('authorized@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    expect($newsletter->getSenderAddress())->same('authorized@email.com');
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
    $controller = $this->getController(['authorized@email.com']);
    $controller->setFromEmailAddress('authorized@email.com');
    expect($newsletter->getSenderAddress())->same('invalid@email.com');

    // refresh from DB and check again
    $this->entityManager->refresh($newsletter);
    expect($newsletter->getSenderAddress())->same('invalid@email.com');
  }

  public function testSetsFromAddressThrowsForUnauthorizedEmail() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Email address 'invalid@email.com' is not authorized");
    $controller = $this->getController(['authorized@email.com']);
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
      'getAuthorizedEmailAddresses' => Expected::once($array),
      'createAuthorizedEmailAddress' => Expected::once($response),
    ]);
    $mocks = [
      'Bridge' => $bridgeMock,
    ];
    $controller = $this->getControllerWithCustomMocks($mocks);
    $result = $controller->createAuthorizedEmailAddress('new-authorized@email.com');
    expect($result)->equals($response);
  }

  public function testItThrowsAnExceptionForReturnedArrayForCreateNewAuthorizedEmailAddress() {
    $errorMessage = 'some errors';
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage($errorMessage );

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
    $array = ['authorized@email.com'];
    $controller = $this->getController($array);
    $result = $controller->isEmailAddressAuthorized('authorized@email.com');
    expect($result)->equals(true);
  }

  public function testItReturnsFalseWhenNotAuthorizedForIsEmailAddressAuthorized() {
    $array = ['authorized@email.com'];
    $controller = $this->getController($array);
    $result = $controller->isEmailAddressAuthorized('pending@email.com');
    expect($result)->equals(false);
  }

  public function testItReturnsFalseWhenNoArrayForIsEmailAddressAuthorized() {
    $controller = $this->getController([]);
    $result = $controller->isEmailAddressAuthorized('pending@email.com');
    expect($result)->equals(false);
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

    return new AuthorizedEmailsController($this->settings, $bridgeMock, $newslettersRepository, $senderDomainController);
  }
}
