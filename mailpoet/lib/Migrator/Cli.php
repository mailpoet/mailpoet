<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use WP_CLI;

class Cli {
  /** @var Migrator */
  private $migrator;

  public function __construct(
    Migrator $migrator
  ) {
    $this->migrator = $migrator;
  }

  public function initialize(): void {
    if (!class_exists(WP_CLI::class)) {
      return;
    }

    WP_CLI::add_command('mailpoet:migrations:run', [$this->migrator, 'run'], [
      'shortdesc' => 'Runs MailPoet database migrations',
    ]);
  }
}
