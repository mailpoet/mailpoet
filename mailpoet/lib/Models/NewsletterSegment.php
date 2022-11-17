<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $segmentId
 * @property string $updatedAt
 */
class NewsletterSegment extends Model {
  public static $_table = MP_NEWSLETTER_SEGMENT_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
}
