<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Shortcodes\Categories\CategoryInterface;
use MailPoet\Newsletter\Shortcodes\Categories\Date;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Categories\Newsletter;
use MailPoet\Newsletter\Shortcodes\Categories\Site;
use MailPoet\Newsletter\Shortcodes\Categories\Subscriber;
use MailPoet\Util\Helpers;
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

  /** @var Site */
  private $siteCategory;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Date $dateCategory,
    Link $linkCategory,
    Newsletter $newsletterCategory,
    Subscriber $subscriberCategory,
    Site $siteCategory,
    WPFunctions $wp
  ) {
    $this->dateCategory = $dateCategory;
    $this->linkCategory = $linkCategory;
    $this->newsletterCategory = $newsletterCategory;
    $this->subscriberCategory = $subscriberCategory;
    $this->siteCategory = $siteCategory;
    $this->wp = $wp;
  }

  public function setNewsletter(?NewsletterEntity $newsletter = null): void {
    $this->newsletter = $newsletter;
  }

  public function setSubscriber(?SubscriberEntity $subscriber = null): void {
    $this->subscriber = $subscriber;
  }

  public function setQueue(?SendingQueueEntity $queue = null): void {
    $this->queue = $queue;
  }

  public function setWpUserPreview(bool $wpUserPreview): void {
    $this->wpUserPreview = $wpUserPreview;
  }

  public function extract($content, $categories = false) {
    if (is_array($categories)) {
      $normalizedCategories = array_values(array_filter(
        array_map(
          static fn($v): string => is_scalar($v) ? preg_quote((string)$v, '/') : '',
          $categories
        ),
        static fn(string $v): bool => $v !== ''
      ));
      $categories = $normalizedCategories ? implode('|', $normalizedCategories) : false;
    } else {
      $categories = false;
    }
    // match: [category:shortcode] or [category|category|...:shortcode]
    // dot not match: [category://shortcode] - avoids matching http/ftp links
    $regex = sprintf(
      '/\[%s:(?!\/\/).*?\]/i',
      ($categories) ? '(?:' . $categories . ')' : '(?:\w+)'
    );
    preg_match_all($regex, (string)$content, $shortcodes);
    $shortcodes = $shortcodes[0];
    return (count($shortcodes)) ?
      array_values(array_unique($shortcodes)) :
      false;
  }

  /**
   * Parse a MailPoet-style shortcode.
   * The syntax is [category:action | argument:argument_value], it can have a single argument.
   */
  public function match($shortcode) {
    preg_match(
      '/\[(?P<category>\w+)?:(?P<action>\w+)(?:.*?\|.*?(?P<argument>\w+):(?P<argument_value>.*?))?\]/',
      $shortcode,
      $match
    );
    // If argument exists, copy it to the arguments array
    if (!empty($match['argument'])) {
      $match['arguments'] = [$match['argument'] => isset($match['argument_value']) ? $match['argument_value'] : ''];
    }
    return $match;
  }

  /**
   * Parse a WordPress-style shortcode.
   * The syntax is [category:action arg1="value1" arg2="value2"], it can have multiple arguments.
   */
  public function matchWPShortcode($shortcode) {
    // Decode HTML entities in case the shortcode came from HTML content (e.g., &quot; -> ")
    $shortcode = html_entity_decode($shortcode, ENT_QUOTES, 'UTF-8');

    $atts = $this->wp->shortcodeParseAtts(trim($shortcode, '[]/'));
    if (empty($atts[0])) {
      return [];
    }
    $shortcodeName = $atts[0];
    list($category, $action) = explode(':', $shortcodeName);
    $shortcodeDetails = [];
    $shortcodeDetails['category'] = $category;
    $shortcodeDetails['action'] = $action;
    $shortcodeDetails['arguments'] = [];
    foreach ($atts as $attrName => $attrValue) {
      if (is_numeric($attrName)) {
        continue; // Skip unnamed attributes
      }
      // Strip surrounding quotes from attribute values
      $cleanValue = trim($attrValue, '"\' ');
      $shortcodeDetails['arguments'][$attrName] = $cleanValue;
      // Make a shortcut to the first argument
      if (!isset($shortcodeDetails['argument'])) {
        $shortcodeDetails['argument'] = $attrName;
        $shortcodeDetails['argument_value'] = $cleanValue;
      }
    }
    return $shortcodeDetails;
  }

  public function process($shortcodes, $content = '') {
    $processedShortcodes = [];
    foreach ($shortcodes as $shortcode) {
      $shortcodeDetails = $this->match($shortcode);
      if (empty($shortcodeDetails)) {
        // Wrong MailPoet shortcode syntax, try to parse as a native WP shortcode
        $shortcodeDetails = $this->matchWPShortcode($shortcode);
      }
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
        '';
      $shortcodeDetails['arguments'] = !empty($shortcodeDetails['arguments']) ?
        $shortcodeDetails['arguments'] : [];

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
        $customShortcode = $this->wp->applyFilters(
          'mailpoet_newsletter_shortcode',
          $shortcode,
          $this->newsletter,
          $this->subscriber,
          $this->queue,
          $content,
          $shortcodeDetails['arguments'],
          $this->wpUserPreview
        );
        $processedShortcodes[] = ($customShortcode === $shortcode) ?
          false :
          $customShortcode;
      }

    }
    return $processedShortcodes;
  }

  /**
   * @param bool $isPlainText Content that is never parsed as HTML, such as a subject line,
   *   so values are inserted as they are even where the text looks like a tag.
   */
  public function replace($content, $contentSource = null, $categories = null, bool $isPlainText = false) {
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
    $content = (string)$content;
    $shortcodesInsideTags = $isPlainText ? [] : $this->findShortcodesInsideTags($content, $shortcodes);
    if (!$shortcodesInsideTags) {
      return str_replace($shortcodes, $processedShortcodes, $content);
    }
    // Text around the tagged positions is replaced chunk by chunk, so shortcode-like
    // text inside an encoded value is never replaced a second time.
    $result = '';
    $cursor = 0;
    foreach ($shortcodesInsideTags as $position => $index) {
      if ($position < $cursor) {
        continue;
      }
      $result .= str_replace($shortcodes, $processedShortcodes, substr($content, $cursor, $position - $cursor));
      $result .= $this->encodeForAttribute((string)$processedShortcodes[$index]);
      $cursor = $position + strlen($shortcodes[$index]);
    }
    return $result . str_replace($shortcodes, $processedShortcodes, substr($content, $cursor));
  }

  /**
   * @param string[] $shortcodes
   * @return array<int, int> shortcode index by its position in $content, in position order
   */
  private function findShortcodesInsideTags(string $content, array $shortcodes): array {
    $occurrences = [];
    foreach ($shortcodes as $index => $shortcode) {
      $position = strpos($content, $shortcode);
      while ($position !== false) {
        $occurrences[$position] = $index;
        $position = strpos($content, $shortcode, $position + strlen($shortcode));
      }
    }
    ksort($occurrences);
    $tags = $this->findTags($content, (int)array_key_last($occurrences));
    $tagIndex = 0;
    $found = [];
    foreach ($occurrences as $position => $index) {
      while (isset($tags[$tagIndex]) && $tags[$tagIndex][1] < $position) {
        $tagIndex++;
      }
      if (isset($tags[$tagIndex]) && $tags[$tagIndex][0] < $position) {
        $found[$position] = $index;
      }
    }
    return $found;
  }

  /**
   * Lists start tags beginning before $until as [offset of "<", offset of ">"] pairs,
   * scanning from the start the way a browser does. A start tag is "<" followed by a
   * letter, so text such as "<3" or "< $50" is not one. Sending joins the subject,
   * HTML and plain-text body with Helpers::DIVIDER, so each part is scanned on its
   * own and a stray "<" in the subject cannot shift where tags are found in the HTML.
   *
   * @return array<int, array{int, int}>
   */
  private function findTags(string $content, int $until): array {
    $tags = [];
    $offset = 0;
    foreach (explode(Helpers::DIVIDER, $content) as $part) {
      $lastGreaterThan = strrpos($part, '>');
      $start = strpos($part, '<');
      while ($start !== false && $start < $lastGreaterThan && $offset + $start < $until) {
        $end = ctype_alpha(substr($part, $start + 1, 1)) ? $this->findTagEnd($part, $start + 1) : null;
        if ($end !== null) {
          $tags[] = [$offset + $start, $offset + $end];
        }
        $start = strpos($part, '<', $end ?? $start + 1);
      }
      $offset += strlen($part) + strlen(Helpers::DIVIDER);
      if ($offset > $until) {
        break;
      }
    }
    return $tags;
  }

  /**
   * Returns the offset of the ">" that closes the tag, or null when the tag is never
   * closed. As in a browser, a quote opens an attribute value only right after "=",
   * and a ">" inside a quoted value does not close the tag.
   */
  private function findTagEnd(string $content, int $cursor): ?int {
    $length = strlen($content);
    while ($cursor < $length) {
      $cursor += strcspn($content, '"\'>', $cursor);
      if ($cursor >= $length) {
        return null;
      }
      if ($content[$cursor] === '>') {
        return $cursor;
      }
      $beforeQuote = $cursor - 1;
      while (ctype_space($content[$beforeQuote])) {
        $beforeQuote--;
      }
      if ($content[$beforeQuote] !== '=') {
        $cursor++;
        continue;
      }
      $closingQuote = strpos($content, $content[$cursor], $cursor + 1);
      if ($closingQuote === false) {
        return null;
      }
      $cursor = $closingQuote + 1;
    }
    return null;
  }

  private function encodeForAttribute(string $value): string {
    // Ampersands are left alone: values such as URLs must stay byte-identical, and
    // an ampersand cannot end an attribute value.
    return strtr($value, [
      '"' => '&quot;',
      "'" => '&#039;',
      '<' => '&lt;',
      '>' => '&gt;',
    ]);
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
    } elseif ($category === 'site') {
      return $this->siteCategory;
    }
    return null;
  }
}
