<?php

namespace MailPoet\Doctrine\Validator;

use MailPoetVendor\Symfony\Contracts\Translation\TranslatorTrait;

class Translator implements \MailPoetVendor\Symfony\Component\Translation\TranslatorInterface {

  use TranslatorTrait;

  public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null) {
    return $this->trans($id, ['%count%' => $number] + $parameters, $domain, $locale);
  }
}
