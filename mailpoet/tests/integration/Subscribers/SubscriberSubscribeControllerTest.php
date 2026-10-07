<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Util\Fixtures;
use DateTimeImmutable;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag;
use MailPoet\UnexpectedValueException;

class SubscriberSubscribeControllerTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var SubscriberSubscribeController */
  private $subscribeController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var FieldNameObfuscator */
  private $obfuscator;

  /** @var string */
  private $obfuscatedEmail;

  /** @var string */
  private $obfuscatedSegments;

  /** @var string */
  private $obfuscatedFirstName;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->obfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $this->obfuscatedEmail = $this->obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $this->obfuscator->obfuscate('segments');
    $this->obfuscatedFirstName = $this->obfuscator->obfuscate('first_name');
    $this->subscribeController = $this->diContainer->get(SubscriberSubscribeController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  /** @var int[] */
  private $createdPostIds = [];

  /** @var callable|null */
  private $captchaLimitFilter = null;

  public function _after() {
    foreach ($this->createdPostIds as $postId) {
      wp_delete_post($postId, true);
    }
    $this->createdPostIds = [];
    unset($GLOBALS['post']);
    $this->settings->set('captcha', []);
    if ($this->captchaLimitFilter) {
      remove_filter('mailpoet_captcha_session_limit', $this->captchaLimitFilter);
      $this->captchaLimitFilter = null;
      unset($_SERVER['REMOTE_ADDR']);
      global $wpdb;
      $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%MAILPOET\\_captcha\\_sessions\\_%'");
      wp_cache_flush();
    }
    add_filter('mailpoet_behavioral_signals_looks_human', '__return_true');
    parent::_after();
  }

  /**
   * @dataProvider dataForUnusableCaptchaSessionIds
   */
  public function testBuiltInCaptchaReplacesSessionIdsItDoesNotKnow(string $suppliedId): void {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'captcha' . rand(0, 100000) . '@example.com';

    $meta = $this->subscribeController->subscribe($this->getCaptchaSubmission($form, $segment, $email, [
      'captcha_session_id' => $suppliedId,
      'captcha' => 'abc',
    ]));

    verify($meta['show_captcha'])->true();
    $newId = $meta['captcha_session_id'];
    verify($newId)->notEquals($suppliedId);
    verify($captchaSession->isValidId($newId))->true();
    verify($captchaSession->exists($suppliedId))->false();
    $stash = $captchaSession->getFormData($newId);
    verify($stash[$this->obfuscatedEmail])->equals($email);
    verify($stash)->arrayHasNotKey('captcha');
    $this->assertNull($this->subscribersRepository->findOneBy(['email' => $email]));
    $captchaSession->reset($newId);
  }

  public function dataForUnusableCaptchaSessionIds(): array {
    return [
      'too short' => ['short'],
      'too long' => [str_repeat('a', 33)],
      'invalid characters' => [str_repeat('a', 31) . '-'],
      'well formed but unknown' => [str_repeat('b', 32)],
    ];
  }

  /**
   * @dataProvider dataForCaptchaTypesUsingSessions
   */
  public function testItStartsANewChallengeWhenTheSessionIdIsNotAString(?string $captchaType): void {
    $this->settings->set('captcha', ['type' => $captchaType]);
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'captcha' . rand(0, 100000) . '@example.com';

    $meta = $this->subscribeController->subscribe($this->getCaptchaSubmission($form, $segment, $email, [
      'captcha_session_id' => ['not', 'a', 'string'],
      'captcha' => 'abc',
    ]));

    verify($meta['show_captcha'])->true();
    verify($captchaSession->isValidId($meta['captcha_session_id']))->true();
    $this->assertNull($this->subscribersRepository->findOneBy(['email' => $email]));
    $captchaSession->reset($meta['captcha_session_id']);
  }

  public function dataForCaptchaTypesUsingSessions(): array {
    return [
      'built-in' => [CaptchaConstants::TYPE_BUILTIN],
      'disabled' => [CaptchaConstants::TYPE_DISABLED],
    ];
  }

  public function testBuiltInCaptchaSubscribesAfterTheSessionWasReplaced(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'captcha' . rand(0, 100000) . '@example.com';

    $meta = $this->subscribeController->subscribe($this->getCaptchaSubmission($form, $segment, $email, [
      'captcha_session_id' => str_repeat('c', 32),
      'captcha' => 'abc',
    ]));
    $newId = $meta['captcha_session_id'];
    $phrase = $captchaSession->getCaptchaHash($newId)['phrase'] ?? null;
    $this->assertNotEmpty($phrase);

    $result = $this->subscribeController->subscribe([
      'form_id' => $form->getId(),
      'captcha_session_id' => $newId,
      'captcha' => $phrase,
      'behavioral_signals' => $this->getHumanSignals(),
    ]);

    verify($result)->arrayHasNotKey('error');
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $captchaSession->reset($newId);
  }

  public function testBuiltInCaptchaSubscribesAfterSignalsAskedForANewChallenge(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    // The test environment treats every submission as human. Use the production check.
    remove_filter('mailpoet_behavioral_signals_looks_human', '__return_true');
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'captcha' . rand(0, 100000) . '@example.com';

    $submission = $this->getCaptchaSubmission($form, $segment, $email, []);
    unset($submission['behavioral_signals']);
    $first = $this->subscribeController->subscribe($submission);
    $firstPhrase = $captchaSession->getCaptchaHash($first['captcha_session_id'])['phrase'];

    $second = $this->subscribeController->subscribe([
      'form_id' => $form->getId(),
      'captcha_session_id' => $first['captcha_session_id'],
      'captcha' => $firstPhrase,
    ]);
    verify($second['show_captcha'])->true();
    verify($second['captcha_session_id'])->notEquals($first['captcha_session_id']);
    $secondPhrase = $captchaSession->getCaptchaHash($second['captcha_session_id'])['phrase'];

    $third = $this->subscribeController->subscribe([
      'form_id' => $form->getId(),
      'captcha_session_id' => $second['captcha_session_id'],
      'captcha' => $secondPhrase,
      'behavioral_signals' => $this->getHumanSignals(),
    ]);

    verify($third)->arrayHasNotKey('error');
    $this->assertInstanceOf(SubscriberEntity::class, $this->subscribersRepository->findOneBy(['email' => $email]));
    $captchaSession->reset($first['captcha_session_id']);
    $captchaSession->reset($second['captcha_session_id']);
  }

  public function testASolvedChallengeCannotBeReusedAfterSignalsAskedForAnotherOne(): void {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_DISABLED]);
    // The test environment treats every submission as human. Use the production check.
    remove_filter('mailpoet_behavioral_signals_looks_human', '__return_true');
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'captcha' . rand(0, 100000) . '@example.com';

    $implausibleSignals = array_merge($this->getHumanSignals(), ['time_ms' => 100]);
    $submission = $this->getCaptchaSubmission($form, $segment, $email, []);
    $submission['behavioral_signals'] = $implausibleSignals;
    $first = $this->subscribeController->subscribe($submission);
    verify($first['show_captcha'])->true();
    $sessionId = $first['captcha_session_id'];
    $phrase = $captchaSession->getCaptchaHash($sessionId)['phrase'];

    $solved = [
      'form_id' => $form->getId(),
      'captcha_session_id' => $sessionId,
      'captcha' => $phrase,
      'behavioral_signals' => $implausibleSignals,
    ];
    $second = $this->subscribeController->subscribe($solved);
    verify($second['show_captcha'])->true();
    verify($second['captcha_session_id'])->notEquals($sessionId);
    verify($captchaSession->getCaptchaHash($sessionId))->false();

    $replay = $this->subscribeController->subscribe($solved);
    verify($replay['error'])->equals('Please fill in the CAPTCHA.');
    verify($replay['captcha_session_id'])->notEquals($sessionId);
    $this->assertNull($this->subscribersRepository->findOneBy(['email' => $email]));
    $captchaSession->reset($sessionId);
    $captchaSession->reset($second['captcha_session_id']);
    $captchaSession->reset($replay['captcha_session_id']);
  }

  public function testBuiltInCaptchaRejectsNewSessionsOverTheSourceLimit(): void {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $submission = $this->getCaptchaSubmission($form, $segment, 'limit@example.com', []);

    $this->limitCaptchaSessions(1);
    $first = $this->subscribeController->subscribe($submission);
    verify($first['show_captcha'])->true();

    try {
      $this->subscribeController->subscribe($submission);
      $this->fail('Expected the session limit to be reached.');
    } catch (UnexpectedValueException $e) {
      verify($e->getMessage())->equals('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.');
    }
    $captchaSession->reset($first['captcha_session_id']);
  }

  public function testItReportsTheSourceLimitWhenSignalsNeedANewChallenge(): void {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    remove_filter('mailpoet_behavioral_signals_looks_human', '__return_true');
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $submission = $this->getCaptchaSubmission($form, $segment, 'limit@example.com', []);
    unset($submission['behavioral_signals']);

    $this->limitCaptchaSessions(1);
    $first = $this->subscribeController->subscribe($submission);
    $phrase = $captchaSession->getCaptchaHash($first['captcha_session_id'])['phrase'];

    $second = $this->subscribeController->subscribe([
      'form_id' => $form->getId(),
      'captcha_session_id' => $first['captcha_session_id'],
      'captcha' => $phrase,
    ]);

    verify($second['error'])->equals('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.');
    verify($second)->arrayHasNotKey('show_captcha');
    $captchaSession->reset($first['captcha_session_id']);
  }

  public function testItRedirectsToPublishedSuccessPage(): void {
    $pageId = $this->createPage('publish');
    $meta = $this->subscribeWithSuccessPage($pageId);
    verify($meta['redirect_url'])->equals(get_permalink($pageId));
  }

  public function testItRedirectsToPrivateSuccessPage(): void {
    $pageId = $this->createPage('private');
    $meta = $this->subscribeWithSuccessPage($pageId);
    verify($meta['redirect_url'])->equals(get_permalink($pageId));
  }

  public function testItDoesNotRedirectToDraftSuccessPage(): void {
    $meta = $this->subscribeWithSuccessPage($this->createPage('draft'));
    verify($meta)->arrayHasNotKey('redirect_url');
  }

  public function testItDoesNotRedirectToTrashedSuccessPage(): void {
    $pageId = $this->createPage('publish');
    wp_trash_post($pageId);
    $meta = $this->subscribeWithSuccessPage($pageId);
    verify($meta)->arrayHasNotKey('redirect_url');
  }

  public function testItDoesNotRedirectToDeletedSuccessPage(): void {
    $pageId = $this->createPage('publish');
    wp_delete_post($pageId, true);
    $meta = $this->subscribeWithSuccessPage($pageId);
    verify($meta)->arrayHasNotKey('redirect_url');
  }

  public function testItDoesNotRedirectWhenSuccessPageHasNoPermalink(): void {
    $pageId = $this->createPage('publish');
    add_filter('page_link', '__return_empty_string');
    try {
      $meta = $this->subscribeWithSuccessPage($pageId);
    } finally {
      remove_filter('page_link', '__return_empty_string');
    }
    verify($meta)->arrayHasNotKey('redirect_url');
  }

  public function testItDoesNotRedirectToCurrentPostWhenSuccessPageIsNotSet(): void {
    $GLOBALS['post'] = get_post($this->createPage('publish'));
    $meta = $this->subscribeWithSuccessPage(0);
    verify($meta)->arrayHasNotKey('redirect_url');
  }

  public function testItCanSubscribeSubscriberWithoutConfirmation(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber)->instanceOf(SubscriberEntity::class);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItCanSubscribeSubscriberWithConfirmation(): void {
    $this->settings->set('signup_confirmation.enabled', true);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber)->instanceOf(SubscriberEntity::class);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItReturnsInfoAboutErrorWhenConfirmationEmailFails(): void {
    $confirmationEmailMailerMock = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationEmailMailerMock->method('sendConfirmationEmailOnce')
      ->willThrowException(new \Exception('Confirmation email error'));
    $subscriberActions = $this->getServiceWithOverrides(SubscriberActions::class, ['confirmationEmailMailer' => $confirmationEmailMailerMock]);
    $subscriberController = $this->getServiceWithOverrides(SubscriberSubscribeController::class, ['subscriberActions' => $subscriberActions]);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $result = $subscriberController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($result)->arrayHasKey('error');
    verify($result['error'])->equals('Confirmation email error');
  }

  public function testItThrottlesRapidSameIpResubmitBeforeUpdatingExistingSubscriber(): void {
    wp_set_current_user(0);
    $_SERVER['REMOTE_ADDR'] = '203.0.113.' . rand(1, 254);
    $this->settings->set('signup_confirmation.enabled', true);

    $confirmationEmailMailerMock = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationEmailMailerMock->expects($this->once())
      ->method('sendConfirmationEmailOnce')
      ->willReturn(true);
    $subscriberActions = $this->getServiceWithOverrides(SubscriberActions::class, ['confirmationEmailMailer' => $confirmationEmailMailerMock]);
    $subscriberController = $this->getServiceWithOverrides(SubscriberSubscribeController::class, ['subscriberActions' => $subscriberActions]);

    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'throttled-resubmit-' . rand(0, 10000) . '@example.com';
    $data = [
      $this->obfuscatedEmail => $email,
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];

    $firstResult = $subscriberController->subscribe($data);
    $this->assertArrayNotHasKey('error', $firstResult);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $lastConfirmationEmailSentAt = new DateTimeImmutable('2026-04-27 10:00:00');
    $subscriber->setConfirmationsCount(2);
    $subscriber->setLastConfirmationEmailSentAt($lastConfirmationEmailSentAt);
    $subscriber->setUnconfirmedData('{"first_name":"Original"}');
    $this->subscribersRepository->flush();

    $secondResult = $subscriberController->subscribe($data);

    verify($secondResult['refresh_captcha'])->true();
    verify($secondResult['error'])->stringContainsString('You need to wait');
    $this->subscribersRepository->refresh($subscriber);
    verify($subscriber->getConfirmationsCount())->equals(2);
    verify($subscriber->getLastConfirmationEmailSentAt())->equals($lastConfirmationEmailSentAt);
    verify($subscriber->getUnconfirmedData())->equals('{"first_name":"Original"}');
  }

  public function testItReturnsHelpfulErrorWhenUnconfirmedSubscriberReachedConfirmationEmailLimit(): void {
    $this->settings->set('signup_confirmation.enabled', true);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'confirmation-limit-' . rand(0, 10000) . '@example.com';
    (new SubscriberFactory())
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $result = $this->subscribeController->subscribe([
      $this->obfuscatedEmail => $email,
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ]);

    verify($result['error'])->equals(__('We\'ve already sent you a confirmation email. Please check your inbox or spam folder.', 'mailpoet'));
  }

  public function testItResubscribesExistingUnconfirmedSubscriber(): void {
    $this->settings->set('signup_confirmation.enabled', true);
    $confirmationEmailMailerMock = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationEmailMailerMock->expects($this->once())
      ->method('sendConfirmationEmailOnce')
      ->with($this->isInstanceOf(SubscriberEntity::class), null, null, true)
      ->willReturn(true);
    $subscriberActions = $this->getServiceWithOverrides(SubscriberActions::class, ['confirmationEmailMailer' => $confirmationEmailMailerMock]);
    $subscriberController = $this->getServiceWithOverrides(SubscriberSubscribeController::class, ['subscriberActions' => $subscriberActions]);

    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $email = 'unconfirmed-resubscribe-' . rand(0, 10000) . '@example.com';
    $subscriber = (new SubscriberFactory())
      ->withEmail($email)
      ->withFirstName('Original')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCountConfirmations(1)
      ->create();

    $result = $subscriberController->subscribe([
      $this->obfuscatedEmail => $email,
      $this->obfuscatedFirstName => 'Updated',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ]);

    $this->assertArrayNotHasKey('error', $result);
    $this->subscribersRepository->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($subscriber->getFirstName())->equals('Original');
    verify($subscriber->getUnconfirmedData())->equals(json_encode([
      'email' => $email,
      'first_name' => 'Updated',
    ]));
  }

  public function testItCanSubscribeSubscriberWithCustomField(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $customField = $this->createCustomField('Custom Field');
    $form = $this->createForm($segment, [$customField]);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
      'cf_' . $customField->getId() => 'field value',
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber)->instanceOf(SubscriberEntity::class);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberCustomFields = $this->subscriberCustomFieldRepository->findBy(['subscriber' => $subscriber]);
    verify($subscriberCustomFields)->arrayCount(1);
    $subscriberCustomField = reset($subscriberCustomFields);
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField);
    verify($subscriberCustomField)->instanceOf(SubscriberCustomFieldEntity::class);
    verify($subscriberCustomField->getSubscriber())->equals($subscriber);
    verify($subscriberCustomField->getCustomField())->equals($customField);
    verify($subscriberCustomField->getValue())->equals($data['cf_' . $customField->getId()]);
  }

  public function testItCanSubscribeSubscriberWithTags(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $tag = (new Tag())->withName('My Tag')->create();
    $form = $this->createForm($segment, [], [$tag]);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertEquals(SubscriberEntity::STATUS_SUBSCRIBED, $subscriber->getStatus());
    $this->assertCount(1, $subscriber->getSubscriberTags());
    $this->assertNotNull($subscriber->getSubscriberTag($tag));
  }

  public function testItAddsFormTagsToAlreadySubscribedSubscriberWithConfirmationEnabled(): void {
    $this->settings->set('signup_confirmation.enabled', true);
    $confirmationEmailMailerMock = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationEmailMailerMock->expects($this->never())
      ->method('sendConfirmationEmailOnce');
    $subscriberActions = $this->getServiceWithOverrides(SubscriberActions::class, ['confirmationEmailMailer' => $confirmationEmailMailerMock]);
    $subscriberController = $this->getServiceWithOverrides(SubscriberSubscribeController::class, ['subscriberActions' => $subscriberActions]);

    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $tag = (new Tag())->withName('Already Subscribed Tag')->create();
    $form = $this->createForm($segment, [], [$tag]);
    $email = 'already-subscribed-tags-' . rand(0, 10000) . '@example.com';
    $subscriber = (new SubscriberFactory())
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $subscriberController->subscribe([
      $this->obfuscatedEmail => $email,
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ]);

    $this->subscribersRepository->refresh($subscriber);
    $this->assertEquals(SubscriberEntity::STATUS_SUBSCRIBED, $subscriber->getStatus());
    $this->assertCount(1, $subscriber->getSubscriberTags());
    $this->assertNotNull($subscriber->getSubscriberTag($tag));
  }

  public function testItDoesNotSubscribeThroughDisabledForm(): void {
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $form->setStatus(FormEntity::STATUS_DISABLED);
    $this->entityManager->flush();

    $this->assertSubscriptionRejected($form, $segment);
  }

  public function testItDoesNotSubscribeThroughTrashedForm(): void {
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $form->setDeletedAt(new DateTimeImmutable());
    $this->entityManager->flush();

    $this->assertSubscriptionRejected($form, $segment);
  }

  private function createPage(string $status): int {
    $pageId = wp_insert_post(['post_type' => 'page', 'post_title' => 'Success', 'post_status' => $status]);
    $this->createdPostIds[] = $pageId;
    return $pageId;
  }

  private function subscribeWithSuccessPage(int $pageId): array {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);
    $form->setSettings(array_merge($form->getSettings() ?? [], ['on_success' => 'page', 'success_page' => $pageId]));
    $this->entityManager->flush();
    return $this->subscribeController->subscribe([
      $this->obfuscatedEmail => 'redirect' . rand(0, 100000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ]);
  }

  private function limitCaptchaSessions(int $limit): void {
    $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
    $this->captchaLimitFilter = function () use ($limit) {
      return $limit;
    };
    add_filter('mailpoet_captcha_session_limit', $this->captchaLimitFilter);
  }

  private function getCaptchaSubmission(FormEntity $form, SegmentEntity $segment, string $email, array $captchaData): array {
    return array_merge([
      $this->obfuscatedEmail => $email,
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
      'behavioral_signals' => $this->getHumanSignals(),
    ], $captchaData);
  }

  private function getHumanSignals(): array {
    return [
      'time_ms' => 5000,
      'mm_count' => 5,
      'kd_count' => 5,
      'scroll_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ];
  }

  private function assertSubscriptionRejected(FormEntity $form, SegmentEntity $segment): void {
    $email = 'inactive-form-' . rand(0, 10000) . '@example.com';
    try {
      $this->subscribeController->subscribe([
        $this->obfuscatedEmail => $email,
        $this->obfuscatedSegments => [$segment->getId()],
        'form_id' => $form->getId(),
      ]);
      $this->fail('Expected NotFoundException was not thrown.');
    } catch (NotFoundException $e) {
      $this->assertSame('Please specify a valid form ID.', $e->getMessage());
    }
    $this->assertNull($this->subscribersRepository->findOneBy(['email' => $email]));
  }

  /**
   * @param CustomFieldEntity[] $customFields
   * @param TagEntity[] $tags
   */
  private function createForm(
    SegmentEntity $segment,
    array $customFields = [],
    array $tags = []
  ): FormEntity {
    $form = new FormEntity('Form' . rand(0, 10000));
    $body = Fixtures::get('form_body_template');
    // Add segment selection block
    $body[] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => $segment->getId()]],
      ],
    ];
    foreach ($customFields as $customField) {
      $body[] = [
        'type' => $customField->getType(),
        'name' => $customField->getName(),
        'id' => $customField->getId(),
        'params' => $customField->getParams(),
      ];
    }
    $tagNames = [];
    foreach ($tags as $tag) {
      $tagNames[] = $tag->getName();
    }

    $form->setBody($body);
    $form->setSettings([
      'segments_selected_by' => 'user',
      'segments' => [$segment->getId()],
      'tags' => $tagNames,
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }

  private function createCustomField(string $name): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $customField->setName($name);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }
}
