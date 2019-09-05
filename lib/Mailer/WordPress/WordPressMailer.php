<?php

namespace MailPoet\Mailer\WordPress;

// Load PHPMailer class, so we can subclass it.
use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;

if (!class_exists('PHPMailer')) {
  require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

class WordPressMailer extends \PHPMailer {

  /** @var Mailer */
  private $mailer;

  function __construct(Mailer $mailer) {
    parent::__construct(true);
    $this->mailer = $mailer;
  }

  function send() {
    // We need this so that the \PHPMailer class will correctly prepare all the headers.
    $this->Mailer = 'mail';

    // Prepare everything (including the message) for sending.
    if (!$this->preSend()) {
      return false;
    }

    $result = $this->mailer->send($this->getEmail(), $this->formatAddress($this->getToAddresses()));

    if ($result['response']) {
      return true;
    } else {
      throw new \phpmailerException($result['error']);
    }
  }

  private function getEmail() {
    $email = [
      'subject' => $this->Subject,
      'body' => [],
    ];

    if ($this->ContentType === 'text/plain') {
      $email['body']['text'] = $this->Body;
    } elseif ($this->ContentType === 'text/html') {
      $text = @Html2Text::convert(strtolower($this->CharSet) === 'utf-8' ? $this->Body : utf8_encode($this->Body));
      $email['body']['text'] = $text;
      $email['body']['html'] = $this->Body;
    } else {
      throw new \phpmailerException('Unsupported email content type has been used. Please use only text or HTML emails.');
    }
    return $email;
  }

  private function formatAddress($wordpress_address) {
    $data = $wordpress_address[0];
    $result = [
      'address' => $data[0],
    ];
    if (!empty($data[1])) {
      $result['full_name'] = $data[1];
    }
    return $result;
  }

}

// TODO test different mailers with html && text emails
