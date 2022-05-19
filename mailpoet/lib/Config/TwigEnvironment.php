<?php

namespace MailPoet\Config;

use MailPoetVendor\Twig\Environment;

class TwigEnvironment extends Environment {


  private $templateClassPrefix = '__TwigTemplate_';

  public function getTemplateClass(string $name, int $index = null): string {
    return $this->templateClassPrefix . \hash('sha256', $name) . (null === $index ? '' : '___' . $index);
  }
}
