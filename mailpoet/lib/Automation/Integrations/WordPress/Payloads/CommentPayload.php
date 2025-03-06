<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress\Payloads;

use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\WordPress;

class CommentPayload implements Payload {
  /** @var int */
  private $commentId;

  /** @var \WP_Comment|null */
  private $comment;

  /** @var WordPress */
  protected $wp;

  public function __construct(
    int $commentId,
    WordPress $wp
  ) {
    $this->commentId = $commentId;
    $this->wp = $wp;
  }

  public function getCommentId(): int {
    return $this->commentId;
  }

  public function getComment(): ?\WP_Comment {
    if ($this->comment === null) {
      $comment = $this->wp->getComment($this->commentId);
      $this->comment = $comment instanceof \WP_Comment ? $comment : null;
    }
    return $this->comment;
  }
}
