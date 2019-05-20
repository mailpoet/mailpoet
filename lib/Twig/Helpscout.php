<?php

namespace MailPoet\Twig;

use MailPoetVendor\Twig\TwigFunction;

if (!defined('ABSPATH')) exit;

class Helpscout extends \MailPoetVendor\Twig\Extension\AbstractExtension {
  public function getFunctions() {
    return [
      new TwigFunction(
        'get_helpscout_data',
        '\MailPoet\Helpscout\Beacon::getData',
        ['is_safe' => ['all']]
      ),
    ];
  }
}
