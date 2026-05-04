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
}
