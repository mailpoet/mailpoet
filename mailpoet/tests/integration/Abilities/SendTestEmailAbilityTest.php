<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use Codeception\Stub\Expected;
use MailPoet\Abilities\SendTestEmailAbility;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\InvalidStateException;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class SendTestEmailAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/send-test-email';

  public function _before(): void {
    AbilitiesTestHelper::enableDraftAbilities();
    wp_set_current_user((int)$this->tester->createWordPressUser('draft-tester@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::disableDraftAbilities();
    MailerLog::resetMailerLog();
    wp_set_current_user(0);
    $this->tester->deleteCreatedUsers();
    parent::_after();
  }

  public function testItIsRegisteredAsAWriteToolThatIsNotDestructive(): void {
    $ability = $this->ability();

    verify($ability->get_meta()['annotations'])->equals(['readonly' => false, 'destructive' => false, 'idempotent' => false]);
    // MCP clients load every description into the model's context on each request.
    $this->assertLessThanOrEqual(500, strlen($ability->get_description()));
  }

  public function testItSendsTheDraftOnlyToTheCurrentUser(): void {
    $id = $this->createDraft('Test me');
    $sent = [];

    $result = $this->withMailer(Expected::once(function ($newsletter, $recipient) use (&$sent) {
      $sent = ['subject' => $newsletter['subject'], 'html' => $newsletter['body']['html'], 'recipient' => $recipient];
      return ['response' => true];
    }))->execute(['id' => $id]);

    verify($result)->equals(['id' => $id, 'subject' => 'Test me', 'sent_to' => 'draft-tester@example.com']);
    verify($sent['recipient'])->equals('draft-tester@example.com');
    verify($sent['subject'])->equals('Test me');
    verify($sent['html'])->stringContainsString('Hello from the draft');
    $this->entityManager->clear();
    $newsletter = $this->diContainer->get(NewslettersRepository::class)->findOneById($id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($this->diContainer->get(SendingQueuesRepository::class)->findAll())->empty();
  }

  public function testItRejectsAnotherRecipient(): void {
    $id = $this->createDraft('Test me');

    $result = $this->ability()->execute(['id' => $id, 'recipient' => 'someone-else@example.com']);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_input');
  }

  public function testItSendsADraftWhoseContentAgentsCannotEdit(): void {
    $id = $this->createDraft('Test me');
    wp_update_post(['ID' => $this->getWpPostId($id), 'post_content' => '<!-- wp:paragraph --><p>Only the editor can change this</p><!-- /wp:paragraph -->']);

    $result = $this->withMailer(Expected::once(['response' => true]))->execute(['id' => $id]);

    $this->assertIsArray($result);
    verify($result['sent_to'])->equals('draft-tester@example.com');
  }

  public function testItSendsNothingForANewsletterThatIsNotADraft(): void {
    $sent = $this->createDraft('Sent');
    $this->getNewsletter($sent)->setStatus(NewsletterEntity::STATUS_SENT);
    $scheduled = $this->createDraft('Scheduled');
    $this->getNewsletter($scheduled)->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $queued = (int)(new Newsletter())->withDraftStatus()->withWpPostId($this->getWpPostId($this->createDraft('Queued')))->withSendingQueue()->create()->getId();

    $ability = $this->withMailer(Expected::never());
    foreach ([$sent, $scheduled, $queued] as $id) {
      $result = $ability->execute(['id' => $id]);
      $this->assertInstanceOf(\WP_Error::class, $result);
      verify($result->get_error_code())->equals('mailpoet_not_a_draft');
    }
  }

  public function testItSendsNothingForOtherEmails(): void {
    $legacy = (int)(new Newsletter())->withDraftStatus()->create()->getId();
    $automation = $this->createDraft('Automation');
    $this->getNewsletter($automation)->setType(NewsletterEntity::TYPE_AUTOMATION);
    $this->entityManager->flush();

    $ability = $this->withMailer(Expected::never());
    foreach ([$legacy, $automation, 999999] as $id) {
      $result = $ability->execute(['id' => $id]);
      $this->assertInstanceOf(\WP_Error::class, $result);
      verify($result->get_error_code())->equals('mailpoet_draft_not_found');
    }
  }

  public function testUsersWhoCannotManageEmailsAreRefused(): void {
    $id = $this->createDraft('Test me');
    wp_set_current_user((int)$this->tester->createWordPressUser('draft-author@example.com', 'author'));

    $result = $this->ability()->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_permissions');
  }

  public function testItExplainsAFailedSend(): void {
    $id = $this->createDraft('Test me');
    $error = new MailerError(MailerError::OPERATION_CONNECT, MailerError::LEVEL_HARD, 'Could not connect to SMTP host. Mail to john@example.org was not accepted.');

    $result = $this->withMailer(Expected::once(['response' => false, 'error' => $error]))->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_test_email_not_sent');
    verify($result->get_error_message())->stringContainsString('Could not connect to SMTP host');
    verify($result->get_error_message())->stringNotContainsString('john@example.org');
    verify($result->get_error_message())->stringContainsString('was not accepted. An administrator can check the sender and the sending method');
  }

  public function testItExplainsAMailerThatCannotBeBuilt(): void {
    $id = $this->createDraft('Test me');

    $result = $this->withMailerFactory(new InvalidStateException('Sender name and email are not configured.'))->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_test_email_not_sent');
    verify($result->get_error_message())->stringStartsWith('Sender name and email are not configured. An administrator can check');
  }

  public function testItHidesUnexpectedErrors(): void {
    $id = $this->createDraft('Test me');

    $result = $this->withMailerFactory(new \RuntimeException('Internal details'))->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_message())->stringNotContainsString('Internal details');
    verify($result->get_error_message())->stringContainsString('MailPoet logs');
  }

  public function testItSaysWhenThePlanLimitIsReached(): void {
    $id = $this->createDraft('Test me');
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(SubscribersFeature::MSS_KEY_STATE, Bridge::KEY_VALID);
    $settings->set(SubscribersFeature::MSS_EMAIL_VOLUME_LIMIT_SETTING_KEY, 10);
    $settings->set(SubscribersFeature::MSS_EMAILS_SENT_SETTING_KEY, 11);
    // The message the MailPoet Sending Service error mapper builds for this case.
    $message = 'You have reached the subscriber limit of your plan. Please <a href="https://account.mailpoet.com/account/" target="_blank">upgrade your plan</a>, or <a href="https://www.mailpoet.com/support/">contact our support team</a> if you have any questions.<br/>';
    $error = new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message);

    $result = $this->withMailer(Expected::once(['response' => false, 'error' => $error]))->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_message())->stringContainsString('Please upgrade your plan, or contact our support team if you have any questions. The subscriber or email limit');
    verify($result->get_error_message())->stringNotContainsString('<');
  }

  public function testItSaysWhenSendingIsPaused(): void {
    $id = $this->createDraft('Test me');
    MailerLog::pauseSending(MailerLog::getMailerLog());
    $error = new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, 'Sending failed');

    $result = $this->withMailer(Expected::once(['response' => false, 'error' => $error]))->execute(['id' => $id]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_message())->stringContainsString('Sending failed. Sending is paused.');
  }

  private function createDraft(string $subject): int {
    $result = wp_get_ability('mailpoet/create-newsletter-draft');
    $this->assertInstanceOf(\WP_Ability::class, $result);
    $created = $result->execute([
      'subject' => $subject,
      'blocks' => [['type' => 'paragraph', 'text' => 'Hello from the draft']],
    ]);
    $this->assertIsArray($created);
    $this->assertIsInt($created['id']);
    return $created['id'];
  }

  private function getNewsletter(int $id): NewsletterEntity {
    $newsletter = $this->diContainer->get(NewslettersRepository::class)->findOneById($id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    return $newsletter;
  }

  private function getWpPostId(int $id): int {
    return (int)$this->getNewsletter($id)->getWpPostId();
  }

  private function ability(): \WP_Ability {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability;
  }

  /**
   * The ability with a real preview controller and renderer that sends through a mocked mailer.
   *
   * @param mixed $send
   */
  private function withMailer($send): SendTestEmailAbility {
    $mailer = $this->makeEmpty(Mailer::class, ['send' => $send]);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    return $this->withPreviewController($mailerFactory);
  }

  private function withMailerFactory(\Throwable $error): SendTestEmailAbility {
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willThrowException($error);
    return $this->withPreviewController($mailerFactory);
  }

  /** @param MailerFactory&\PHPUnit\Framework\MockObject\MockObject $mailerFactory */
  private function withPreviewController($mailerFactory): SendTestEmailAbility {
    $sendPreviewController = $this->getServiceWithOverrides(SendPreviewController::class, ['mailerFactory' => $mailerFactory]);
    return $this->getServiceWithOverrides(SendTestEmailAbility::class, ['sendPreviewController' => $sendPreviewController]);
  }
}
