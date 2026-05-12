<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WpTransactionalEmailManager {
  const STATUS_DEFAULT = 'default';
  const STATUS_CUSTOMIZED = 'customized';
  const STATUS_DRAFT = 'draft';

  /** @var WpTransactionalEmails */
  private $wpTransactionalEmails;

  /** @var WpTransactionalEmailTemplates */
  private $templates;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WpTransactionalEmails $wpTransactionalEmails,
    WpTransactionalEmailTemplates $templates,
    NewslettersRepository $newslettersRepository,
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    $this->wpTransactionalEmails = $wpTransactionalEmails;
    $this->templates = $templates;
    $this->newslettersRepository = $newslettersRepository;
    $this->entityManager = $entityManager;
    $this->wp = $wp;
  }

  public function findOrCreate(string $kind): ?NewsletterEntity {
    if (!$this->wpTransactionalEmails->isValidKind($kind)) {
      return null;
    }
    $existing = $this->wpTransactionalEmails->findByKind($kind);
    if ($existing !== null) {
      return $existing;
    }
    return $this->createForKind($kind);
  }

  public function setActive(string $kind, bool $active): bool {
    $newsletter = $this->wpTransactionalEmails->findByKind($kind);
    if ($newsletter === null) {
      return false;
    }
    $newsletter->setStatus($active ? NewsletterEntity::STATUS_ACTIVE : NewsletterEntity::STATUS_DRAFT);
    $this->newslettersRepository->flush();
    return true;
  }

  public function restoreDefault(string $kind): bool {
    if (!$this->wpTransactionalEmails->isValidKind($kind)) {
      return false;
    }
    $newsletter = $this->wpTransactionalEmails->findByKind($kind);
    if ($newsletter === null) {
      $this->wpTransactionalEmails->clearNewsletterId($kind);
      return true;
    }
    $newsletter->setDeletedAt(Carbon::now()->millisecond(0));
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->newslettersRepository->flush();
    $this->wpTransactionalEmails->clearNewsletterId($kind);
    return true;
  }

  /**
   * @return array<int, array{kind: string, status: string, newsletter_id: int|null, subject: string|null, edit_url: string|null, updated_at: string|null}>
   */
  public function listAll(): array {
    $result = [];
    foreach (WpTransactionalEmails::ALL_KINDS as $kind) {
      $newsletter = $this->wpTransactionalEmails->findByKind($kind);
      $result[] = [
        'kind' => $kind,
        'status' => $this->resolveStatus($newsletter),
        'newsletter_id' => $newsletter ? (int)$newsletter->getId() : null,
        'subject' => $newsletter ? $newsletter->getSubject() : null,
        'edit_url' => $newsletter ? $this->getEditUrl($newsletter) : null,
        'updated_at' => ($newsletter && $newsletter->getUpdatedAt() !== null) ? $newsletter->getUpdatedAt()->format('c') : null,
      ];
    }
    return $result;
  }

  public function getEditUrl(NewsletterEntity $newsletter): string {
    $postId = $newsletter->getWpPostId();
    if (!$postId) {
      return '';
    }
    return $this->wp->adminUrl('post.php?post=' . $postId . '&action=edit');
  }

  private function createForKind(string $kind): ?NewsletterEntity {
    $subject = $this->templates->getSubject($kind);
    $content = $this->templates->getContent($kind);

    $postId = $this->wp->wpInsertPost([
      'post_content' => $content,
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_author' => $this->wp->getCurrentUserId(),
      'post_title' => $subject,
    ], true);

    if ($this->wp->isWpError($postId) || !is_int($postId) || $postId <= 0) {
      return null;
    }

    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_WP_TRANSACTIONAL_EMAIL);
    $newsletter->setSubject($subject);
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setWpPost($this->entityManager->getReference(WpPostEntity::class, $postId));
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();

    $newsletterId = $newsletter->getId();
    if ($newsletterId !== null) {
      $this->wpTransactionalEmails->setNewsletterId($kind, (int)$newsletterId);
    }

    return $newsletter;
  }

  private function resolveStatus(?NewsletterEntity $newsletter): string {
    if ($newsletter === null) {
      return self::STATUS_DEFAULT;
    }
    return $newsletter->getStatus() === NewsletterEntity::STATUS_ACTIVE
      ? self::STATUS_CUSTOMIZED
      : self::STATUS_DRAFT;
  }
}
