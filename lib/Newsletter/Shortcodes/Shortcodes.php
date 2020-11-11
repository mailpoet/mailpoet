<?php

namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Shortcodes\Categories\CategoryInterface;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Categories\Newsletter;
use MailPoet\Newsletter\Shortcodes\Categories\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class Shortcodes {
  /** @var NewsletterEntity|null */
  private $newsletter;

  /** @var SubscriberEntity|null */
  private $subscriber;

  /** @var SendingQueueEntity|null */
  private $queue;

  /** @var bool */
  private $wpUserPreview = false;

  /** @var Date */
  private $dateCategory;

  /** @var Link */
  private $linkCategory;

  /** @var Newsletter */
  private $newsletterCategory;

  /** @var Subscriber */
  private $subscriberCategory;

  public function __construct(
    Date $dateCategory,
    Link $linkCategory,
    Newsletter $newsletterCategory,
    Subscriber $subscriberCategory
  ) {
    $this->dateCategory = $dateCategory;
    $this->linkCategory = $linkCategory;
    $this->newsletterCategory = $newsletterCategory;
    $this->subscriberCategory = $subscriberCategory;
  }

  public function setNewsletter(NewsletterEntity $newsletter): void {
    $this->newsletter = $newsletter;
  }

  public function setSubscriber(SubscriberEntity $subscriber = null): void {
    $this->subscriber = $subscriber;
  }

  public function setQueue(SendingQueueEntity $queue): void {
    $this->queue = $queue;
  }

  public function setWpUserPreview(bool $wpUserPreview): void {
    $this->wpUserPreview = $wpUserPreview;
  }

  public function extract($content, $categories = false) {
    $categories = (is_array($categories)) ? implode('|', $categories) : false;
    // match: [category:shortcode] or [category|category|...:shortcode]
    // dot not match: [category://shortcode] - avoids matching http/ftp links
    $regex = sprintf(
      '/\[%s:(?!\/\/).*?\]/i',
      ($categories) ? '(?:' . $categories . ')' : '(?:\w+)'
    );
    preg_match_all($regex, $content, $shortcodes);
    $shortcodes = $shortcodes[0];
    return (count($shortcodes)) ?
      array_unique($shortcodes) :
      false;
  }

  public function match($shortcode) {
    preg_match(
      '/\[(?P<category>\w+)?:(?P<action>\w+)(?:.*?\|.*?(?P<argument>\w+):(?P<argument_value>.*?))?\]/',
      $shortcode,
      $match
    );
    return $match;
  }

  public function process($shortcodes, $content = '') {
    $processedShortcodes = [];
    foreach ($shortcodes as $shortcode) {
      $shortcodeDetails = $this->match($shortcode);
      $shortcodeDetails['shortcode'] = $shortcode;
      $shortcodeDetails['category'] = !empty($shortcodeDetails['category']) ?
        $shortcodeDetails['category'] :
        '';
      $shortcodeDetails['action'] = !empty($shortcodeDetails['action']) ?
        $shortcodeDetails['action'] :
        '';
      $shortcodeDetails['action_argument'] = !empty($shortcodeDetails['argument']) ?
        $shortcodeDetails['argument'] :
        '';
      $shortcodeDetails['action_argument_value'] = !empty($shortcodeDetails['argument_value']) ?
        $shortcodeDetails['argument_value'] :
        false;

      $category = strtolower($shortcodeDetails['category']);
      $categoryClass = $this->getCategoryObject($category);
      if ($categoryClass instanceof CategoryInterface) {
        $processedShortcodes[] = $categoryClass->process(
          $shortcodeDetails,
          $this->newsletter,
          $this->subscriber,
          $this->queue,
          $content,
          $this->wpUserPreview
        );
      } else {
        $customShortcode = WPFunctions::get()->applyFilters(
          'mailpoet_newsletter_shortcode',
          $shortcode,
          $this->newsletter,
          $this->subscriber,
          $this->queue,
          $content,
          $this->wpUserPreview
        );
        $processedShortcodes[] = ($customShortcode === $shortcode) ?
          false :
          $customShortcode;
      }

    }
    return $processedShortcodes;
  }

  public function replace($content, $contentSource = null, $categories = null) {
    $shortcodes = $this->extract($content, $categories);
    if (!$shortcodes) {
      return $content;
    }
    // if content contains only shortcodes (e.g., [newsletter:post_title]) but their processing
    // depends on some other content (e.g., "post_id" inside a rendered newsletter),
    // then we should use that content source when processing shortcodes
    $processedShortcodes = $this->process(
      $shortcodes,
      ($contentSource) ? $contentSource : $content
    );
    $shortcodes = array_intersect_key($shortcodes, $processedShortcodes);
    return str_replace($shortcodes, $processedShortcodes, $content);
  }

  private function getCategoryObject($category): ?CategoryInterface {
    if ($category === 'link') {
      return $this->linkCategory;
    } elseif ($category === 'date') {
      return $this->dateCategory;
    } elseif ($category === 'newsletter') {
      return $this->newsletterCategory;
    } elseif ($category === 'subscriber') {
      return $this->subscriberCategory;
    }
    return null;
  }
}
