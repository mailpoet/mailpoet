<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Mailer\WordPress\PHPMailerLoader;
use MailPoet\WP\Functions as WPFunctions;
use PHPMailer\PHPMailer\PHPMailer;

class SubscriberLimitNotificationNativeMailer {

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function send(string $recipient, string $subject, string $htmlBody, string $textBody): bool {
    global $phpmailer;

    PHPMailerLoader::load();
    $previousMailer = $phpmailer ?? null;
    $setAltBody = static function($mailer) use ($textBody): void {
      if ($mailer instanceof PHPMailer) {
        $mailer->AltBody = $textBody; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      }
    };

    try {
      $phpmailer = new PHPMailer(true);
      $this->wp->addAction('phpmailer_init', $setAltBody, PHP_INT_MAX, 1);
      return $this->wp->wpMail(
        $recipient,
        $subject,
        $htmlBody,
        ['Content-Type: text/html; charset=UTF-8']
      );
    } finally {
      $this->wp->removeAction('phpmailer_init', $setAltBody, PHP_INT_MAX);
      $phpmailer = $previousMailer;
    }
  }
}
