<?php

namespace MailPoet\Mailer\WordPress;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;

if (!class_exists('PHPMailer')) {
  require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

class WordPressMailer extends \PHPMailer {

  /** @var Mailer */
  private $mailer;

  /** @var Mailer */
  private $fallbackMailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    Mailer $mailer,
    Mailer $fallbackMailer,
    MetaInfo $mailerMetaInfo,
    SubscribersRepository $subscribersRepository
  ) {
    parent::__construct(true);
    $this->mailer = $mailer;
    $this->fallbackMailer = $fallbackMailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function send() {
    // We need this so that the \PHPMailer class will correctly prepare all the headers.
    $this->mailer = 'mail';

    // Prepare everything (including the message) for sending.
    $this->preSend();

    $email = $this->getEmail();
    $address = $this->formatAddress($this->getToAddresses());
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $address]);
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getWordPressTransactionalMetaInfo($subscriber),
    ];

    $sendWithMailer = function ($mailer) use ($email, $address, $extraParams) {
      $result = $mailer->send($email, $address, $extraParams);
      if (!$result['response']) {
        throw new \Exception($result['error']->getMessage());
      }
    };

    try {
      $sendWithMailer($this->mailer);
    } catch (\Exception $e) {
      try {
        $sendWithMailer($this->fallbackMailer);
      } catch (\Exception $fallbackMailerException) {
        // throw exception passing the original (primary mailer) error
        throw new \phpmailerException($e->getMessage(), $e->getCode(), $e);
      }
    }
    return true;
  }

  private function getEmail() {
    $email = [
      'subject' => $this->subject,
      'body' => [],
    ];

    if ($this->contentType === 'text/plain') {
      $email['body']['text'] = $this->body;
    } elseif ($this->contentType === 'text/html') {
      $text = @Html2Text::convert(strtolower($this->charSet) === 'utf-8' ? $this->body : utf8_encode($this->body));
      $email['body']['text'] = $text;
      $email['body']['html'] = $this->body;
    } else {
      throw new \phpmailerException('Unsupported email content type has been used. Please use only text or HTML emails.');
    }
    return $email;
  }

  private function formatAddress($wordpressAddress) {
    $data = $wordpressAddress[0];
    $result = [
      'address' => $data[0],
    ];
    if (!empty($data[1])) {
      $result['full_name'] = $data[1];
    }
    return $result;
  }

}
