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
    // url is a temporary data tag that will be further replaced with
    // the proper track API URL during sending
    $url = Links::DATA_TAG_OPEN;
    $openTrackingImage = sprintf(
      '<img alt="" class="" src="%s"/>',
      $url
    );
    self::appendToDomNodes($template, $openTrackingImage);
    return $DOM->__toString();
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
