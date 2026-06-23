<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use WP_CLI;

class Cli {
  private CronCommand $cronCommand;

  public function __construct(
    CronCommand $cronCommand
  ) {
    $this->cronCommand = $cronCommand;
  }

  public function initialize(): void {
    if (!class_exists(WP_CLI::class)) {
      return;
    }

    WP_CLI::add_command('mailpoet cron', $this->cronCommand, [
      'shortdesc' => 'Manages MailPoet cron tasks',
    ]);
  }
}
