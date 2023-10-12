<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

class StylesController {
  /**
   * Width of the email in pixels.
   * @var int
   */
  const EMAIL_WIDTH = 660;

  /**
   * Width of the email in pixels.
   * @var string
   */
  const EMAIL_BACKGROUND = '#cccccc';

  /**
   * Padding of the email in pixels.
   * @var int
   */
  const EMAIL_PADDING = 10;

  /**
   * Default styles applied to the email. These are going to be replaced by style settings.
   * This is currently more af a proof of concept that we can apply styles to the email.
   * We will gradually replace these hardcoded values with styles saved as global styles or styles saved with the email.
   * @var string
   */
  const DEFAULT_EMAIL_CONTENT_STYLES = "
      body { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; }
      p { font-size: 16px; }
      h1 { font-size: 32px; }
      h2 { font-size: 24px; }
      h3 { font-size: 18px; }
      h4 { font-size: 16px; }
      h5 { font-size: 14px; }
      h6 { font-size: 12px; }
  ";

  public function getEmailContentStyles(): string {
    return self::DEFAULT_EMAIL_CONTENT_STYLES;
  }

  /**
   * @return array{width: int, background: string, padding: array{bottom: int, left: int, right: int, top: int}}
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
