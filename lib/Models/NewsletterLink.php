<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterLink extends Model {
  public static $_table = MP_NEWSLETTER_LINKS_TABLE;

  static function findTopLinkForNewsletter(Newsletter $newsletter) {
    return self::selectExpr('links.*')
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
  }

}
