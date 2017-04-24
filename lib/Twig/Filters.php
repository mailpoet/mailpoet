<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Filters extends \Twig_Extension {

  function getName() {
    return 'filters';
  }

  function getFilters() {
    return array(
      new \Twig_SimpleFilter(
        'intval',
        'intval'
      ),
    );
  }
}