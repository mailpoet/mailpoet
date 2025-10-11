<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Settings;

use MailPoet\Subscription;
use MailPoet\WP\Functions as WPFunctions;

class Pages {
  const PAGE_SUBSCRIPTIONS = 'subscriptions';
  const PAGE_CAPTCHA = 'captcha';
  const PAGE_TITLE = 'MailPoet Page';

  public function __construct() {
  }

  public function init() {
    WPFunctions::get()->registerPostType('mailpoet_page', [
      'labels' => [
        'name' => self::PAGE_TITLE,
        'singular_name' => self::PAGE_TITLE,
      ],
      'public' => true,
      'has_archive' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'rewrite' => false,
      'show_in_nav_menus' => false,
      'can_export' => false,
      'publicly_queryable' => true,
      'exclude_from_search' => true,
      'capability_type' => 'page',
    ]);

    WPFunctions::get()->addFilter('next_post_link', [$this, 'disableNavigationLinks']);
    WPFunctions::get()->addFilter('previous_post_link', [$this, 'disableNavigationLinks']);
  }

  public function disableNavigationLinks($output) {
    if (is_singular('mailpoet_page')) {
      return ''; // Return an empty string to remove navigation links
    }
    return $output;
  }

  public static function createMailPoetPage($postName) {
    WPFunctions::get()->removeAllActions('pre_post_update');
    WPFunctions::get()->removeAllActions('save_post');
    WPFunctions::get()->removeAllActions('wp_insert_post');

    $id = WPFunctions::get()->wpInsertPost([
      'post_status' => 'publish',
      'post_type' => 'mailpoet_page',
      'post_author' => 1,
      'post_content' => '[mailpoet_page]',
      'post_title' => self::PAGE_TITLE,
      'post_name' => $postName,
    ]);

    return ((int)$id > 0) ? (int)$id : false;
  }

  public static function getMailPoetPage($postName) {
    $wp = WPFunctions::get();
    $pages = $wp->getPosts([
      'posts_per_page' => 1,
      'orderby' => 'date',
      'order' => 'DESC',
      'post_type' => 'mailpoet_page',
      'post_name__in' => [$postName],
    ]);

    $page = null;
    if (!empty($pages)) {
      $page = array_shift($pages);
      if (strpos($page->post_content, '[mailpoet_page]') === false) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $page = null;
      }
    }
    return $page;
  }

  public static function getMailPoetPages() {
    return WPFunctions::get()->getPosts([
      'post_type' => 'mailpoet_page',
      'post_name__in' => [self::PAGE_SUBSCRIPTIONS],
    ]);
  }

  /**
   * @param int $id
   *
   * @return bool
   */
  public static function isMailpoetPage($id) {
    $mailpoetPages = static::getMailPoetPages();
    foreach ($mailpoetPages as $mailpoetPage) {
      if ($mailpoetPage->ID === $id) {
        return true;
      }
    }
    return false;
  }

  public static function getAll() {
    $allPages = array_merge(
      static::getMailPoetPages(),
      WPFunctions::get()->getPages()
    );

    $pages = [];
    foreach ($allPages as $page) {
      $pages[] = static::getPageData($page);
    }

    return $pages;
  }

  public static function getPageData($page) {
    $subscriptionUrlFactory = Subscription\SubscriptionUrlFactory::getInstance();
    $captchaUrlFactory = \MailPoet\DI\ContainerWrapper::getInstance()->get(\MailPoet\Captcha\CaptchaUrlFactory::class);
    return [
      'id' => $page->ID,
      'title' => $page->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'url' => [
        'unsubscribe' => $subscriptionUrlFactory->getSubscriptionUrl($page, 'unsubscribe'),
        'manage' => $subscriptionUrlFactory->getSubscriptionUrl($page, 'manage'),
        'confirm' => $subscriptionUrlFactory->getSubscriptionUrl($page, 'confirm'),
        'confirm_unsubscribe' => $subscriptionUrlFactory->getSubscriptionUrl($page, 'confirm_unsubscribe'),
        're_engagement' => $subscriptionUrlFactory->getSubscriptionUrl($page, 're_engagement'),
        'captcha' => $captchaUrlFactory->getCaptchaPreviewUrl($page),
      ],
    ];
  }
}
