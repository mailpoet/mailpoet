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
  private $fallback_mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscribersRepository */
  private $subscribers_repository;

  public function __construct(
    Mailer $mailer,
    Mailer $fallback_mailer,
    MetaInfo $mailerMetaInfo,
    SubscribersRepository $subscribers_repository
  ) {
    parent::__construct(true);
    $this->mailer = $mailer;
    $this->fallback_mailer = $fallback_mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->subscribers_repository = $subscribers_repository;
  }

  public function send() {
    // We need this so that the \PHPMailer class will correctly prepare all the headers.
    $this->Mailer = 'mail';

    // Prepare everything (including the message) for sending.
    $this->preSend();

    $email = $this->getEmail();
    $address = $this->formatAddress($this->getToAddresses());
    $subscriber = $this->subscribers_repository->findOneBy(['email' => $address]);
    $extra_params = [
      'meta' => $this->mailerMetaInfo->getWordPressTransactionalMetaInfo($subscriber),
    ];

    $send_with_mailer = function ($mailer) use ($email, $address, $extra_params) {
      $result = $mailer->send($email, $address, $extra_params);
      if (!$result['response']) {
        throw new \Exception($result['error']->getMessage());
      }
    };

    try {
      $send_with_mailer($this->mailer);
    } catch (\Exception $e) {
      try {
        $send_with_mailer($this->fallback_mailer);
      } catch (\Exception $fallback_mailer_exception) {
        // throw exception passing the original (primary mailer) error
        throw new \phpmailerException($e->getMessage(), $e->getCode(), $e);
      }
    }
    return true;
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
