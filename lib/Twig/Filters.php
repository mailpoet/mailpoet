<?php

namespace MailPoet\Twig;

use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFilter;

class Filters extends AbstractExtension {

  function getName() {
    return 'filters';
  }

  function getFilters() {
    return [
      new TwigFilter(
        'intval',
        'intval'
      ),
      new TwigFilter(
        'replaceLinkTags',
        'MailPoet\Util\Helpers::replaceLinkTags'
      ),
    ];
  }
}
