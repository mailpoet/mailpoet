<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator\Generators;

interface Generator {
  public function generate();

  public function runBefore();

  public function runAfter();
}
