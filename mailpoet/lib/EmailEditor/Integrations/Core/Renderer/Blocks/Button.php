<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

/**
 * Renders a button block.
 * @see https://www.activecampaign.com/blog/email-buttons
 * @see https://documentation.mjml.io/#mj-button
 */

class Button implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    // Don't render empty buttons
    if (empty($parsedBlock['innerHTML'])) {
      return '';
    }
    $buttonDom = new \DOMDocument();
    $buttonDom->loadHTML($parsedBlock['innerHTML']);
    $buttonLink = $buttonDom->getElementsByTagName('a')->item(0);

    if (!$buttonLink instanceof \DOMElement) {
      return '';
    }

    $markup = $this->getMarkup();

    // Add Link Text
    $markup = str_replace('{linkText}', $buttonLink->textContent ?: '', $markup);
    $markup = str_replace('{linkUrl}', $buttonLink->getAttribute('href') ?: '#', $markup);

    // Width
    // Parent block prepares container with proper width. If the width is set let's use full width of the container
    // otherwise let's use auto width.
    $width = 'auto';
    if (($parsedBlock['attrs']['width'] ?? null)) {
      $width = '100%';
    }
    $markup = str_replace('{width}', $width, $markup);

    // Background
    $bgColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'transparent';
    $markup = str_replace('{backgroundColor}', $bgColor, $markup);

    // Styles attributes
    $wrapperStyles = [
      'background' => $bgColor,
      'cursor' => 'auto',
      'word-break' => 'break-word',
      'box-sizing' => 'border-box',
    ];
    $linkStyles = [
      'display' => 'block',
      'line-height' => '120%',
      'margin' => '0',
      'mso-padding-alt' => '0px',
    ];

    // Border
    if ($parsedBlock['attrs']['style']['border'] ?? '') {
      $wrapperStyles = array_merge($wrapperStyles, wp_style_engine_get_styles(['border' => $parsedBlock['attrs']['style']['border']])['declarations']);
    }
    if ($parsedBlock['attrs']['style']['border']['width'] ?? '') {
      $wrapperStyles['border-style'] = 'solid';
    } else {
      $wrapperStyles['border'] = 'none';
    }

    // Spacing
    if ($parsedBlock['attrs']['style']['spacing']['padding'] ?? '') {
      $padding = $parsedBlock['attrs']['style']['spacing']['padding'];
      $wrapperStyles['mso-padding-alt'] = "{$padding['top']} {$padding['right']} {$padding['bottom']} {$padding['left']}";
      $linkStyles['padding'] = "{$padding['top']} {$padding['right']} {$padding['bottom']} {$padding['left']}";
    }

    // Typography
    $typography = $parsedBlock['attrs']['style']['typography'] ?? [];
    $typography['textDecoration'] = $typography['textDecoration'] ?? ($parsedBlock['email_attrs']['text-decoration'] ?? 'inherit');
    $linkStyles = array_merge($linkStyles, wp_style_engine_get_styles(['typography' => $typography])['declarations']);
    if ($parsedBlock['attrs']['style']['color']['text'] ?? '') {
      $linkStyles['color'] = $parsedBlock['attrs']['style']['color']['text'];
    }

    // Escaping
    $wrapperStyles = array_map('esc_attr', $wrapperStyles);
    $linkStyles = array_map('esc_attr', $linkStyles);
    // Font family may contain single quotes
    $contentStyles = $settingsController->getEmailContentStyles();
    $linkStyles['font-family'] = str_replace('&#039;', "'", esc_attr("{$contentStyles['typography']['fontFamily']}"));

    $markup = str_replace('{linkStyles}', $settingsController->convertStylesToString($linkStyles), $markup);
    $markup = str_replace('{wrapperStyles}', $settingsController->convertStylesToString($wrapperStyles), $markup);

    return $markup;
  }

  private function getMarkup(): string {
    return '<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;border-collapse:separate;line-height:100%;width:{width};">
        <tr>
          <td align="center" bgcolor="{backgroundColor}" role="presentation" style="{wrapperStyles}" valign="middle">
            <a href="{linkUrl}" style="{linkStyles}" target="_blank">{linkText}</a>
          </td>
        </tr>
      </table>';
  }
}
