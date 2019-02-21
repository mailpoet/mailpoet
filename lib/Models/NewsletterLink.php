<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

/**
 * @property int $newsletter_id
 * @property int $queue_id
 * @property string $url
 * @property string $hash
 */
class NewsletterLink extends Model {
  public static $_table = MP_NEWSLETTER_LINKS_TABLE;

  /**
   * @param Newsletter $newsletter
   * @return \stdClass|null
   */
  static function findTopLinkForNewsletter(Newsletter $newsletter) {
    $link = self::selectExpr('links.*')
      ->selectExpr('count(*)', 'clicksCount')
      ->tableAlias('links')
      ->innerJoin(StatisticsClicks::$_table,
        array('clicks.link_id', '=', 'links.id'),
        'clicks')
      ->where('newsletter_id', $newsletter->id())
      ->groupBy('links.id')
      ->orderByDesc('clicksCount')
      ->limit(1)
      ->findOne();
    if (!$link) {
      return null;
    }
    return $link;
  }

}
