<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks;

use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes\LatestPosts;
use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes\PoweredByMailpoet;

class BlockTypesController {
  private $poweredByMailPoet;
  private LatestPosts $latestPosts;

  public function __construct(
    PoweredByMailpoet $poweredByMailPoet,
    LatestPosts $latestPosts
  ) {
    $this->poweredByMailPoet = $poweredByMailPoet;
    $this->latestPosts = $latestPosts;
  }

  public function initialize(): void {
    $this->poweredByMailPoet->initialize();
    $this->latestPosts->initialize();
  }
}
