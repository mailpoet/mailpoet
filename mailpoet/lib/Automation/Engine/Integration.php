<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

interface Integration {
  public function register(Registry $registry): void;
}
