<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Layout;

use MailPoet\EmailEditor\Engine\SettingsController;

/**
 * This class provides functionality to render inner blocks of a block that supports reduced flex layout.
 */
class FlexLayoutRenderer {
  public function renderInnerBlocksInLayout(array $parsedBlock, SettingsController $settingsController): string {
    $innerBlocks = $this->computeWidthsForFlexLayout($parsedBlock, $settingsController);

    // MS Outlook doesn't support style attribute in divs so we conditionally wrap the buttons in a table and repeat styles
    $outputHtml = '<!--[if mso | IE]><table align="{align}" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="" style="{style}" ><![endif]-->
        <div style="{style}"><table class="layout-flex-wrapper" style="display:inline-block"><tbody><tr>';

    foreach ($innerBlocks as $key => $block) {
      $styles = [];
      if ($block['email_attrs']['layout_width'] ?? null) {
        $styles['width'] = $block['email_attrs']['layout_width'];
      }
      if ($key > 0) {
        $styles['padding-left'] = SettingsController::FLEX_GAP;
      }
      $outputHtml .= '<td class="layout-flex-item" style="' . esc_html($settingsController->convertStylesToString($styles)) . '">' . render_block($block) . '</td>';
    }
    $outputHtml .= '</tr></table></div>
    <!--[if mso | IE]></td></tr></table><![endif]-->';

    $styles = wp_style_engine_get_styles($parsedBlock['attrs']['style'])['css'];
    $justify = $parsedBlock['attrs']['layout']['justifyContent'] ?? 'left';
    $styles .= 'text-align: ' . esc_attr($justify);
    $outputHtml = str_replace('{style}', $styles, $outputHtml);
    $outputHtml = str_replace('{align}', $justify, $outputHtml);

    return $outputHtml;
  }

  private function computeWidthsForFlexLayout(array $parsedBlock, SettingsController $settingsController): array {
    $blocksCount = count($parsedBlock['innerBlocks']);
    $totalSetWidth = 0; // Total width set by user. Excludes items that have no width set
    $totalUsedWidth = 0; // Total width assuming items without set width would consume proportional width
    $parentWidth = $settingsController->parseNumberFromStringWithPixels($parsedBlock['email_attrs']['width'] ?? SettingsController::EMAIL_WIDTH);
    $flexGap = $settingsController->parseNumberFromStringWithPixels(SettingsController::FLEX_GAP);
    $innerBlocks = $parsedBlock['innerBlocks'] ?? [];

    foreach ($innerBlocks as $key => $block) {
      $blockWidthPercent = ($block['attrs']['width'] ?? 0) ? intval($block['attrs']['width']) : 0;
      $blockWidth = floor($parentWidth * ($blockWidthPercent / 100));
      $totalSetWidth += $blockWidth;
      // If width is not set, we assume it's 25% of the parent width
      $totalUsedWidth += $blockWidth ?: floor($parentWidth * (25 / 100));

      if (!$blockWidth) {
        $innerBlocks[$key]['email_attrs']['layout_width'] = null; // Will be rendered as auto
        continue;
      }
      // How many percent of width we will strip to keep some space fot the gap
      // Todo add more precise comment
      $widthGapReduction = $flexGap * ((100 - $blockWidthPercent) / 100);
      $innerBlocks[$key]['email_attrs']['layout_width'] = floor($blockWidth - $widthGapReduction) . 'px';
    }

    // When there is only one block, or percentage is set reasonably we don't need to adjust and just render as set by user
    if ($blocksCount <= 1 || ($totalSetWidth < $parentWidth)) {
      return $innerBlocks;
    }

    foreach ($innerBlocks as $key => $block) {
      $proportionalSpaceOverflow = $parentWidth / $totalUsedWidth;
      $blockWidth = $block['email_attrs']['layout_width'] ? $settingsController->parseNumberFromStringWithPixels($block['email_attrs']['layout_width']) : 0;
      $innerBlocks[$key]['email_attrs']['layout_width'] = $blockWidth ? intval(round($blockWidth * $proportionalSpaceOverflow)) . 'px' : null;
    }
    return $innerBlocks;
  }
}
