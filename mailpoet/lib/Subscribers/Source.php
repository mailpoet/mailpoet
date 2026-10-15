<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers;

class Source {
  const FORM = 'form';
  const IMPORTED = 'imported';
  const ADMINISTRATOR = 'administrator';
  const API = 'api';
  const WORDPRESS_USER = 'wordpress_user';
  const WORDPRESS_USER_DELETED = 'wordpress_user_deleted';
  const WOOCOMMERCE_USER = 'woocommerce_user';
  const WOOCOMMERCE_CHECKOUT = 'woocommerce_checkout';
  const UNKNOWN = 'unknown';

  const ALL = [
    self::FORM,
    self::IMPORTED,
    self::ADMINISTRATOR,
    self::API,
    self::WORDPRESS_USER,
    self::WORDPRESS_USER_DELETED,
    self::WOOCOMMERCE_USER,
    self::WOOCOMMERCE_CHECKOUT,
    self::UNKNOWN,
  ];
}
