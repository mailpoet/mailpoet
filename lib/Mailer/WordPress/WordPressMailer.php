<?php

namespace MailPoet\Mailer\WordPress;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;

PHPMailerLoader::load();

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
    $this->Mailer = 'mail'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // Prepare everything (including the message) for sending.
    $this->preSend();

    $email = $this->getEmail();
    $address = $this->formatAddress($this->getToAddresses());
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $address]);
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getWordPressTransactionalMetaInfo($subscriber),
    ];

    $sendWithMailer = function ($mailer) use ($email, $address, $extraParams) {
      // we need to call Mailer::init() for every single WP e-mail to make sure reply-to is set
      $replyTo = $this->getReplyToAddress();
      $mailer->init(false, false, $replyTo);

      $result = $mailer->send($email, $address, $extraParams);

      // make sure Mailer::init() is called again to clear the reply-to address that was just set if Mailer is used in another context
      $mailer->mailerInstance = null;

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
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $email = [
      'subject' => $this->Subject,
      'body' => [],
    ];

    if (strpos($this->ContentType, 'text/plain') === 0) {
      $email['body']['text'] = $this->Body;
    } elseif (strpos($this->ContentType, 'text/html') === 0) {
      $text = @Html2Text::convert(strtolower($this->CharSet) === 'utf-8' ? $this->Body : utf8_encode($this->Body));
      $email['body']['text'] = $text;
      $email['body']['html'] = $this->Body;
    } elseif (strpos($this->ContentType, 'multipart/alternative') === 0) {
      $email['body']['text'] = $this->AltBody;
      $email['body']['html'] = $this->Body;
    } else {
      throw new \phpmailerException('Unsupported email content type has been used. Please use only text or HTML emails.');
    }
    return $email;
    // phpcs:enable
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

  private function getReplyToAddress() {
    $replyToAddress = false;
    $addresses = $this->getReplyToAddresses();

    if (!empty($addresses)) {
      // only one reply-to address supported by \MailPoet\Mailer
      $address = array_shift($addresses);
      $replyToAddress = [];

      if ($address[1]) {
        $replyToAddress['name'] = $address[1];
      }

      if ($address[0]) {
        $replyToAddress['address'] = $address[0];
      }
    }

    return $replyToAddress;
  }
}
