<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;

class Source {

  const FORM = 'form';
  const IMPORTED = 'imported';
  const ADMINISTRATOR = 'administrator';
  const API = 'api';
  const WORDPRESS_USER = 'wordpress_user';
  const UNKNOWN = 'unknown';

  private static $allowed_sources = array(
    Source::FORM,
    Source::IMPORTED,
    Source::ADMINISTRATOR,
    Source::API,
    Source::WORDPRESS_USER,
    Source::UNKNOWN,
  );

  static function setSource(Subscriber $subscriber, $source) {
    if((isset($subscriber->source)) && ($subscriber->source !== Source::UNKNOWN)) {
      // we don't want to override source
      return $subscriber;
    }
    if(!in_array($source, Source::$allowed_sources)) {
      throw new \InvalidArgumentException('Invalid source "' . $source . '""');
    }
    $subscriber->set('source', $source);
    return $subscriber;
  }

}