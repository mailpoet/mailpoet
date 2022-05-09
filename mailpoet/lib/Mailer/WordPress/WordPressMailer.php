<?php

namespace MailPoet\Mailer\WordPress;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Subscribers\SubscribersRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

PHPMailerLoader::load();

class WordPressMailer extends PHPMailer {
  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  private $fallbackMailerConfig = [
    'method' => Mailer::METHOD_PHPMAIL,
  ];

  public function __construct(
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    SubscribersRepository $subscribersRepository
  ) {
    parent::__construct(true);
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function send() {
    // We need this so that the PHPMailer class will correctly prepare all the headers.
    $this->Mailer = 'mail'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // Prepare everything (including the message) for sending.
    $this->preSend();

    $email = $this->getEmail();
    $address = $this->formatAddress($this->getToAddresses());
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $address]);
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getWordPressTransactionalMetaInfo($subscriber),
    ];

    $sendWithMailer = function ($useFallback) use ($email, $address, $extraParams) {
      // we need to call Mailer::init() for every single WP e-mail to make sure reply-to is set
      $replyTo = $this->getReplyToAddress();
      if ($useFallback) {
        $mailer = $this->mailerFactory->buildMailer($this->fallbackMailerConfig, null, $replyTo);
      } else {
        $mailer = $this->mailerFactory->buildMailer(null, null, $replyTo);
      }
      $result = $mailer->send($email, $address, $extraParams);

      if (!$result['response']) {
        throw new \Exception($result['error']->getMessage());
      }
    };

    try {
      $sendWithMailer($useFallback = false);
    } catch (\Exception $e) {
      try {
        $sendWithMailer($useFallback = true);
      } catch (\Exception $fallbackMailerException) {
        // throw exception passing the original (primary mailer) error
        throw new PHPMailerException($e->getMessage(), $e->getCode(), $e);
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
      throw new PHPMailerException('Unsupported email content type has been used. Please use only text or HTML emails.');
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

  private function getReplyToAddress(): ?array {
    $replyToAddress = null;
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
