<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;

class WpTransactionalEmailManagerTest extends \MailPoetTest {
  /** @var WpTransactionalEmailManager */
  private $manager;

  /** @var WpTransactionalEmails */
  private $service;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    parent::_before();
    $this->manager = $this->diContainer->get(WpTransactionalEmailManager::class);
    $this->service = $this->diContainer->get(WpTransactionalEmails::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  private function assertNewsletter(?NewsletterEntity $newsletter): NewsletterEntity {
    self::assertInstanceOf(NewsletterEntity::class, $newsletter);
    return $newsletter;
  }

  public function testFindOrCreateReturnsNullForUnknownKind() {
    $newsletter = $this->manager->findOrCreate('not_a_kind');
    verify($newsletter)->null();
  }

  public function testFindOrCreateCreatesNewsletterWithExpectedType() {
    $newsletter = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_PASSWORD_RESET));

    verify($newsletter->getType())->equals(NewsletterEntity::TYPE_WP_TRANSACTIONAL_EMAIL);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($newsletter->getSubject())->stringContainsString('password');
  }

  public function testFindOrCreateIsIdempotent() {
    $first = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_NEW_USER));
    $second = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_NEW_USER));

    verify($first->getId())->equals($second->getId());
  }

  public function testFindOrCreateUsesDifferentNewslettersPerKind() {
    $reset = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_PASSWORD_RESET));
    $welcome = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_NEW_USER));

    verify($reset->getId())->notEquals($welcome->getId());
  }

  public function testSetActiveTogglesStatus() {
    $newsletter = $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_PASSWORD_CHANGE));
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);

    $this->manager->setActive(WpTransactionalEmails::KIND_PASSWORD_CHANGE, true);
    $this->newslettersRepository->refresh($newsletter);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_ACTIVE);
    verify($this->service->isActive(WpTransactionalEmails::KIND_PASSWORD_CHANGE))->true();

    $this->manager->setActive(WpTransactionalEmails::KIND_PASSWORD_CHANGE, false);
    $this->newslettersRepository->refresh($newsletter);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($this->service->isActive(WpTransactionalEmails::KIND_PASSWORD_CHANGE))->false();
  }

  public function testRestoreDefaultClearsTheMapping() {
    $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_EMAIL_CHANGE));
    verify($this->service->findByKind(WpTransactionalEmails::KIND_EMAIL_CHANGE))->instanceOf(NewsletterEntity::class);

    $result = $this->manager->restoreDefault(WpTransactionalEmails::KIND_EMAIL_CHANGE);
    verify($result)->true();

    verify($this->service->findByKind(WpTransactionalEmails::KIND_EMAIL_CHANGE))->null();
  }

  public function testListAllReturnsAllFourKinds() {
    $entries = $this->manager->listAll();

    verify($entries)->arrayCount(4);
    $kinds = array_map(static function ($entry) {
      return $entry['kind'];
    }, $entries);
    verify($kinds)->equals(WpTransactionalEmails::ALL_KINDS);
  }

  public function testListAllReportsCorrectStatusBuckets() {
    // password_reset: customized (active newsletter)
    $this->assertNewsletter($this->manager->findOrCreate(WpTransactionalEmails::KIND_PASSWORD_RESET));
    $this->manager->setActive(WpTransactionalEmails::KIND_PASSWORD_RESET, true);

    // new_user: draft (created but not active)
    $this->manager->findOrCreate(WpTransactionalEmails::KIND_NEW_USER);

    // email_change, password_change: default (no newsletter)

    $entries = $this->manager->listAll();
    $byKind = [];
    foreach ($entries as $entry) {
      $byKind[$entry['kind']] = $entry;
    }

    verify($byKind[WpTransactionalEmails::KIND_PASSWORD_RESET]['status'])->equals(WpTransactionalEmailManager::STATUS_CUSTOMIZED);
    verify($byKind[WpTransactionalEmails::KIND_NEW_USER]['status'])->equals(WpTransactionalEmailManager::STATUS_DRAFT);
    verify($byKind[WpTransactionalEmails::KIND_EMAIL_CHANGE]['status'])->equals(WpTransactionalEmailManager::STATUS_DEFAULT);
    verify($byKind[WpTransactionalEmails::KIND_PASSWORD_CHANGE]['status'])->equals(WpTransactionalEmailManager::STATUS_DEFAULT);
  }
}
