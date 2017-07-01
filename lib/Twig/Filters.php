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
      new \Twig_SimpleFilter(
        'replaceLink',
        array(
          $this,
          'replaceLink'
        )
      )
    );
  }

  function replaceLink($source, $link = false, $attributes = array()) {
    if(!$link) return $source;
    $attributes = array_map(function($key) use ($attributes) {
      if(is_bool($attributes[$key])) {
        return $attributes[$key] ? $key : '';
      }
      return sprintf('%s="%s"', $key, $attributes[$key]);
    }, array_keys($attributes));
    $source = str_replace(
      '[link]',
      sprintf(
        '<a %s href="%s">',
        join(' ', $attributes),
        $link
      ),
      $source
    );
    $source = str_replace('[/link]', '</a>', $source);
    return preg_replace('/\s+/', ' ', $source);
  }
}