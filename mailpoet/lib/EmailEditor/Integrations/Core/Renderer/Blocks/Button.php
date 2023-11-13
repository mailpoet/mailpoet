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
    $buttonDom = new \DOMDocument();
    $buttonDom->loadHTML($parsedBlock['innerHTML']);
    $buttonLink = $buttonDom->getElementsByTagName('a')->item(0);

    if (!$buttonLink instanceof \DOMElement || !$buttonLink->attributes) {
      return '';
    }

    $markup = $this->getMarkup();

    // Add Link Text
    $markup = str_replace('{linkText}', $buttonLink->textContent ?: '', $markup);
    $markup = str_replace('{linkUrl}', $buttonLink->getAttribute('href') ?: '#', $markup);

    // Width
    $markup = str_replace('{width}', $parsedBlock['email_attrs']['width'] ?? '100%', $markup);

    // Background
    $bgColor = $parsedBlock['attrs']['style']['color']['background'] ?? 'transparent';
    $markup = str_replace('{backgroundColor}', $bgColor, $markup);

    // Styles attributes
    $wrapperStyles = [
      "background: $bgColor",
      'cursor: auto',
    ];
    $linkStyles = [
      "background: $bgColor",
      'display: inline-block',
      'line-height: 120%',
      'margin: 0',
      'mso-padding-alt: 0px',
      'text-decoration: none',
      'text-transform: none',
    ];

    // Border
    if ($parsedBlock['attrs']['style']['border'] ?? '') {
      $wrapperStyles[] = wp_style_engine_get_styles(['border' => $parsedBlock['attrs']['style']['border']])['css'];
      $wrapperStyles[] = 'border-style: solid';
    }

    // Spacing
    if ($parsedBlock['attrs']['style']['spacing']['padding'] ?? '') {
      $padding = $parsedBlock['attrs']['style']['spacing']['padding'];
      $wrapperStyles[] = "mso-padding-alt: {$padding['top']} {$padding['right']} {$padding['bottom']} {$padding['left']}";
      $linkStyles[] = "padding: {$padding['top']} {$padding['right']} {$padding['bottom']} {$padding['left']}";
    }

    // Font
    $contentStyles = $settingsController->getEmailContentStyles();
    $linkStyles[] = "font-family: {$contentStyles['typography']['fontFamily']}";
    $linkStyles[] = "font-size: {$contentStyles['typography']['fontSize']}";
    if ($parsedBlock['attrs']['style']['typography']) {
      $linkStyles[] = wp_style_engine_get_styles(['typography' => $parsedBlock['attrs']['style']['typography']])['css'];
    }
    if ($parsedBlock['attrs']['style']['color']['text'] ?? '') {
      $linkStyles[] = "color: {$parsedBlock['attrs']['style']['color']['text']}";
    }

    // Escaping
    $linkStyles = array_map('esc_attr', $linkStyles);
    $wrapperStyles = array_map('esc_attr', $wrapperStyles);

    $markup = str_replace('{linkStyles}', join(';', $linkStyles) . ';', $markup);
    $markup = str_replace('{wrapperStyles}', join(';', $wrapperStyles) . ';', $markup);

    return $markup;
  }

  private function getMarkup(): string {
    return '<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;width:{width};">
        <tr>
          <td align="center" bgcolor="{backgroundColor}" role="presentation" style="{wrapperStyles}" valign="middle">
            <a href="{linkUrl}" style="{linkStyles}" target="_blank">{linkText}</a>
          </td>
        </tr>
      </table>';
  }
}
