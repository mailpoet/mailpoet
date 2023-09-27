<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\NotFoundException;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\UnexpectedValueException;

class EmailApiControllerTest extends \MailPoetTest {
  /** @var EmailApiController */
  private $emailApiController;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    $this->emailApiController = $this->diContainer->get(EmailApiController::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItGetsEmailDataFromNewsletterEntity(): void {
    $wpPostId = 6;
    $newsletter = (new NewsletterFactory())
      ->withSubject('New subject')
      ->withPreheader('New preheader')
      ->withWpPostId($wpPostId)
      ->create();

    $emailData = $this->emailApiController->getEmailData(['id' => $wpPostId]);
    expect($emailData['subject'])->equals('New subject');
    expect($emailData['preheader'])->equals('New preheader');
    expect($emailData['id'])->equals($newsletter->getId());
  }

  public function testItSaveEmailDataToNewsletterEntity(): void {
    $wpPostId = 5;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'New subject',
      'preheader' => 'New preheader',
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    expect($newsletter->getSubject())->equals('New subject');
    expect($newsletter->getPreheader())->equals('New preheader');
  }

  public function testItThrowsErrorWhenNewsletterDoesNotExist(): void {
    try {
      $this->emailApiController->saveEmailData([
        'id' => 999,
        'subject' => 'New subject',
        'preheader' => 'New preheader',
      ], new \WP_Post((object)['ID' => 5]));

      $this->fail('Incorrect state exception should have been thrown.');
    } catch (NotFoundException $exception) {
      expect($exception->getHttpStatusCode())->equals(APIResponse::STATUS_NOT_FOUND);
      expect($exception->getMessage())->stringContainsString('Newsletter was not found');
    }
  }

  public function testItThrowsErrorWhenNewsletterWpPostIdDoesNotMatchWpPostId(): void {
    $newsletter = (new NewsletterFactory())
      ->withWpPostId(1)
      ->create();

    try {
      $this->emailApiController->saveEmailData([
        'id' => $newsletter->getId(),
        'subject' => 'New subject',
        'preheader' => 'New preheader',
      ], new \WP_Post((object)['ID' => 2]));

      $this->fail('Incorrect state exception should have been thrown.');
    } catch (UnexpectedValueException $exception) {
      expect($exception->getHttpStatusCode())->equals(APIResponse::STATUS_BAD_REQUEST);
      expect($exception->getMessage())->stringContainsString('Newsletter ID does not match the post ID');
    }
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(NewsletterEntity::class);
  }
}
