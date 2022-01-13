<?php

namespace MailPoet\Models;

use MailPoet\Entities\NewsletterLinkEntity;

/**
 * @deprecated This model is deprecated. Use \MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository and
 * respective Doctrine entities instead. This class can be removed after 2022-04-27.
 */
class NewsletterLink extends Model {
  public static $_table = MP_NEWSLETTER_LINKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const UNSUBSCRIBE_LINK_SHORT_CODE = NewsletterLinkEntity::UNSUBSCRIBE_LINK_SHORT_CODE;
  const INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE = NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE;

  /**
   * @deprecated This is here for displaying the deprecation warning for properties.
   */
  public function __get($key) {
    self::deprecationError('property "' . $key . '"');
    return parent::__get($key);
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for static calls.
   */
  public static function __callStatic($name, $arguments) {
    self::deprecationError($name);
    return parent::__callStatic($name, $arguments);
  }

  private static function deprecationError($methodName) {
    trigger_error(
      'Calling ' . $methodName . ' is deprecated and will be removed. Use \MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository and respective Doctrine entities instead.',
      E_USER_DEPRECATED
    );
  }
}
