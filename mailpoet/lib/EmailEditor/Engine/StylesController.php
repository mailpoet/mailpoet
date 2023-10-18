<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class StylesController {
  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_WIDTH = '660px';

  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_BACKGROUND = '#cccccc';

  /**
   * Padding of the email in pixels.
   * @var string
   */
  const EMAIL_PADDING = '10px';

  /**
   * Default styles applied to the email. These are going to be replaced by style settings.
   * This is currently more af a proof of concept that we can apply styles to the email.
   * We will gradually replace these hardcoded values with styles saved as global styles or styles saved with the email.
   * @var array
   */
  const DEFAULT_EMAIL_CONTENT_STYLES = [
    'typography' => [
      'fontFamily' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
      'fontSize' => '16px',
    ],
    'h1' => [
      'typography' => [
        'fontSize' => '32px',
      ],
    ],
    'h2' => [
      'typography' => [
        'fontSize' => '24px',
      ],
    ],
    'h3' => [
      'typography' => [
        'fontSize' => '18px',
      ],
    ],
    'h4' => [
      'typography' => [
        'fontSize' => '16px',
      ],
    ],
    'h5' => [
      'typography' => [
        'fontSize' => '14px',
      ],
    ],
    'h6' => [
      'typography' => [
        'fontSize' => '12px',
      ],
    ],
  ];

  public function getEmailContentStyles(): array {
    return self::DEFAULT_EMAIL_CONTENT_STYLES;
  }

  /**
   * @return array{width: string, background: string, padding: array{bottom: string, left: string, right: string, top: string}}
   */
  public function getEmailLayoutStyles(): array {
    return [
      'width' => self::EMAIL_WIDTH,
      'background' => self::EMAIL_BACKGROUND,
      'padding' => [
        'bottom' => self::EMAIL_PADDING,
        'left' => self::EMAIL_PADDING,
        'right' => self::EMAIL_PADDING,
        'top' => self::EMAIL_PADDING,
      ],
    ];
  }
}
