<?php

namespace MailPoet\Twig;

if (!defined('ABSPATH')) exit;

class Filters extends \MailPoetVendor\Twig_Extension {

  function getName() {
    return 'filters';
  }

  function getFilters() {
    return array(
      new \MailPoetVendor\Twig_SimpleFilter(
        'intval',
        'intval'
      ),
      new \MailPoetVendor\Twig_SimpleFilter(
        'replaceLinkTags',
        'MailPoet\Util\Helpers::replaceLinkTags'
      )
    );
  }
}
