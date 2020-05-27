<?php

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $postId
 * @property string $updatedAt
 */
class NewsletterPost extends Model {
  public static $_table = MP_NEWSLETTER_POSTS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

}
