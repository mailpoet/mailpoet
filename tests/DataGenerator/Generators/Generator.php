<?php

namespace MailPoet\Test\DataGenerator\Generators;

interface Generator {
  public function generate();

  public function runBefore();

  public function runAfter();
}
