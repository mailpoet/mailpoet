<?php

namespace MailPoet\Twig;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Helpscout\Beacon;
use MailPoetVendor\Twig\TwigFunction;

class Helpscout extends \MailPoetVendor\Twig\Extension\AbstractExtension {
  public function getFunctions() {
    return [
      new TwigFunction(
        'get_helpscout_data',
        [$this, 'getHelpscoutData'],
        ['is_safe' => ['all']]
      ),
    ];
  }

  public function getHelpscoutData() {
    return ContainerWrapper::getInstance()->get(Beacon::class)->getData();
  }
}
