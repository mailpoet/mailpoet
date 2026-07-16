<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\PostProcess;

use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

class OpenTracking {
  public static function process($template) {
    $DOM = new pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->select('body');
    self::appendToDomNodes($template, self::getTrackingImageHtml());
    return $DOM->__toString();
  }

  public static function getTrackingImageHtml(): string {
    // url is a temporary data tag that will be further replaced with
    // the proper track API URL during sending
    return sprintf('<img alt="" class="" src="%s"/>', Links::DATA_TAG_OPEN);
  }

  /**
   * Removes the whole open-tracking <img> element, not just the data tag —
   * leaving an empty src would make email clients resolve the base URL or
   * show a broken image.
   *
   * CNIL/Garante: for subscribers without tracking consent the read
   * operation must not happen at all on future emails, so the pixel never
   * ships. We first try an exact match on getTrackingImageHtml(); if pQuery
   * re-serialised the tag (attribute order, self-closing style) we fall back
   * to a regex that removes any <img> whose src is the open data tag.
   */
  public static function removeTrackingImage(string $template): string {
    $exact = str_replace(self::getTrackingImageHtml(), '', $template);
    if ($exact !== $template) {
      return $exact;
    }
    // Fallback: remove the whole <img …> element that carries the open data
    // tag as its src, regardless of attribute order/quoting/self-closing.
    $pattern = '/<img\b[^>]*\bsrc=("|\')' . preg_quote(Links::DATA_TAG_OPEN, '/') . '\1[^>]*>/i';
    $result = preg_replace($pattern, '', $template);
    return is_string($result) ? $result : $template;
  }

  public static function addTrackingImage() {
    WPFunctions::get()->addFilter(Renderer::FILTER_POST_PROCESS, function ($template) {
      return OpenTracking::process($template);
    });
    return true;
  }

  private static function appendToDomNodes($template, $openTrackingImage): void {
    // Preserve backward compatibility with pQuery::html()
    // by processing an array of DomNodes
    if (!empty($template)) {
      $template = is_array($template) ? $template : [$template];
      array_map(
        function ($item) use ($openTrackingImage) {
          $itemHtml = $item->toString(true, true, 1);
          $item->html($itemHtml . $openTrackingImage);
          return $item;
        },
        $template
      );
    }
  }
}
