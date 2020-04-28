<?php

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $queueId
 * @property string $url
 * @property string $hash
 * @property int|null $clicksCount
 */
class NewsletterLink extends Model {
  public static $_table = MP_NEWSLETTER_LINKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const UNSUBSCRIBE_LINK_SHORT_CODE = '[link:subscription_unsubscribe_url]';
  const INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE = '[link:subscription_instant_unsubscribe_url]';
}
