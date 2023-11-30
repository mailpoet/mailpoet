<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Buttons implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();
    $typography = $parsedBlock['attrs']['style']['typography'] ?? [];
    $typography['fontSize'] = $typography['fontSize'] ?? $contentStyles['typography']['fontSize'];
    $parsedBlock['attrs']['style']['typography'] = $typography;
    $styles = wp_style_engine_get_styles($parsedBlock['attrs']['style'])['css'];
    $content = $this->renderButtonsInLayout($parsedBlock, $settingsController);
    $justify = $parsedBlock['attrs']['layout']['justifyContent'] ?? 'left';
    $styles .= 'text-align: ' . esc_attr($justify);

    $markup = $this->getMarkup();
    $markup = str_replace('{style}', $styles, $markup);
    $markup = str_replace('{align}', $justify, $markup);
    $markup = str_replace('{buttons}', $content, $markup);

    return $markup;
  }

  private function getMarkup(): string {
    // MS Outlook doesn't support style attribute in divs so we conditionally wrap the buttons in a table and repeat styles
    return '<!--[if mso | IE]><table align="{align}" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="" style="{style}" ><![endif]-->
        <div style="{style}">{buttons}</div>
    <!--[if mso | IE]></td></tr></table><![endif]-->';
  }

  private function renderButtonsInLayout(array $parsedBlock, SettingsController $settingsController): string {
    $innerBlocks = $this->computeWidthsForFlexLayout($parsedBlock, $settingsController);

    $blocksHtml = '<table class="layout-flex-wrapper" style="display:inline-block"><tbody><tr>';
    foreach ($innerBlocks as $key => $block) {
      $styles = [];
      if ($block['email_attrs']['layout_width'] ?? null) {
        $styles['width'] = $block['email_attrs']['layout_width'];
      }
      if ($key > 0) {
        $styles['padding-left'] = SettingsController::FLEX_GAP;
      }
      $blocksHtml .= '<td class="layout-flex-item" style="' . esc_html($settingsController->convertStylesToString($styles)) . '">' . render_block($block) . '</td>';
    }
    $blocksHtml .= '</tr></table>';
    return $blocksHtml;
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
