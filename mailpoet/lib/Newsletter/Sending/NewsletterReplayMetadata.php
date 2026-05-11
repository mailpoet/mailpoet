<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

class NewsletterReplayMetadata {
  public const LATEST_NEWSLETTER_REPLAY = 'latest_newsletter_replay';
  public const REPLAY_SOURCE_NEWSLETTER_ID = 'replay_source_newsletter_id';
  public const REPLAY_SOURCE_QUEUE_ID = 'replay_source_queue_id';
  public const REPLAY_SOURCE_TASK_ID = 'replay_source_task_id';
  public const REPLAY_SOURCE_PROCESSED_AT = 'replay_source_processed_at';
  public const REPLAY_SEGMENT_ID = 'replay_segment_id';
  public const REPLAY_SUBSCRIBER_ID = 'replay_subscriber_id';
  public const AUTOMATION = 'automation';

  public static function isLatestNewsletterReplayMeta(?array $meta): bool {
    return ($meta[self::LATEST_NEWSLETTER_REPLAY] ?? false) === true;
  }

  public static function getMetaLikePattern(): string {
    return '%' . self::LATEST_NEWSLETTER_REPLAY . '%';
  }
}
