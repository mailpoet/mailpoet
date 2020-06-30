<?php

namespace MailPoet\Form\Templates;

interface Template {
  public function getName(): string;

  public function getBody(): array;

  public function getSettings(): array;

  public function getStyles(): string;
}
