<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Mailer\WordPress;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Mailer\Methods\AmazonSES;
use MailPoet\Mailer\Methods\MailerMethod;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Mailer\Methods\SendGrid;
use MailPoet\Subscribers\ApiSourcePluginResolver;
use MailPoet\Subscribers\SourceUrlResolver;
use MailPoet\Subscribers\SubscribersRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

PHPMailerLoader::load();

class WordPressMailer extends PHPMailer {
  // The Sending Service rejects a message with more addresses in To, CC or BCC.
  private const MSS_MAX_RECIPIENTS_PER_FIELD = 5;
  // Limits across To, CC and BCC together, from the Amazon SES and SendGrid v2 API docs.
  private const AMAZON_SES_MAX_RECIPIENTS_PER_EMAIL = 50;
  private const SENDGRID_MAX_RECIPIENTS_PER_EMAIL = 1000;

  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ApiSourcePluginResolver */
  private $sourcePluginResolver;

  /** @var SourceUrlResolver */
  private $sourceUrlResolver;

  public function __construct(
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    SubscribersRepository $subscribersRepository,
    ApiSourcePluginResolver $sourcePluginResolver,
    SourceUrlResolver $sourceUrlResolver
  ) {
    parent::__construct(true);
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->subscribersRepository = $subscribersRepository;
    $this->sourcePluginResolver = $sourcePluginResolver;
    $this->sourceUrlResolver = $sourceUrlResolver;
  }

  public function send() {
    // We need this so that the PHPMailer class will correctly prepare all the headers.
    $originalMailer = $this->Mailer; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->Mailer = 'mail'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // Prepare everything (including the message) for sending.
    $this->preSend();

    $email = $this->getEmail();
    $toAddresses = $this->getToAddresses();
    $address = $this->formatAddress($toAddresses);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $address['address']]);
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getWordPressTransactionalMetaInfo(
        $subscriber,
        $this->sourcePluginResolver->resolve(),
        $this->sourceUrlResolver->resolve()
      ),
    ];
    try {
      // we need to build fresh mailer for every single WP e-mail to make sure reply-to is set
      $replyTo = $this->getReplyToAddress();
      $mailer = $this->mailerFactory->buildMailer(null, null, $replyTo);
      $recipients = array_filter($this->getExtraRecipients($toAddresses, $mailer->mailerMethod));
      if ($recipients) {
        $extraParams['recipients'] = $recipients;
      }
      $result = $mailer->send($email, $address, $extraParams);
      if (!$result['response']) {
        throw new \Exception($result['error']->getMessage());
      }
    } catch (\Exception $ePrimary) {
      // In case the sending using MailPoet's mailer fails continue with sending using original parent PHPMailer::sent method.
      // But if anything fails we still want tho throw the error from the primary MailPoet mailer.
      try {
        // Restore original settings for mailer. Some sites may use SMTP and we needed to reset it to mail
        $this->Mailer = $originalMailer; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return parent::send();
      } catch (\Exception $eFallback) {
        throw new PHPMailerException($ePrimary->getMessage(), $ePrimary->getCode(), $ePrimary);
      }
    }
    return true;
  }

  /**
   * The To addresses after the first, and the CC and BCC addresses, cut to what the sending method accepts.
   * @param array<int, array{0: string, 1?: string}> $toAddresses
   * @return array{to: array<int, array{address: string, name: string}>, cc: array<int, array{address: string, name: string}>, bcc: array<int, array{address: string, name: string}>}
   */
  private function getExtraRecipients(array $toAddresses, ?MailerMethod $mailerMethod): array {
    $lists = [
      'to' => array_slice($toAddresses, 1),
      'cc' => $this->getCcAddresses(),
      'bcc' => $this->getBccAddresses(),
    ];
    if ($mailerMethod instanceof MailPoet) {
      // The main recipient counts toward To.
      return [
        'to' => $this->limitRecipients('to', $lists['to'], self::MSS_MAX_RECIPIENTS_PER_FIELD - 1),
        'cc' => $this->limitRecipients('cc', $lists['cc'], self::MSS_MAX_RECIPIENTS_PER_FIELD),
        'bcc' => $this->limitRecipients('bcc', $lists['bcc'], self::MSS_MAX_RECIPIENTS_PER_FIELD),
      ];
    }
    $maxPerEmail = $this->getMaxRecipientsPerEmail($mailerMethod);
    $remaining = $maxPerEmail === null ? PHP_INT_MAX : $maxPerEmail - 1;
    $result = [];
    $dropped = 0;
    foreach ($lists as $field => $addresses) {
      $kept = array_slice($addresses, 0, $remaining);
      $dropped += count($addresses) - count($kept);
      $remaining -= count($kept);
      $result[$field] = array_map([$this, 'formatAddressEntry'], $kept);
    }
    if ($dropped > 0) {
      $this->logDroppedRecipients(['dropped' => $dropped, 'limit' => $maxPerEmail]);
    }
    return $result;
  }

  private function getMaxRecipientsPerEmail(?MailerMethod $mailerMethod): ?int {
    if ($mailerMethod instanceof AmazonSES) {
      return self::AMAZON_SES_MAX_RECIPIENTS_PER_EMAIL;
    }
    if ($mailerMethod instanceof SendGrid) {
      return self::SENDGRID_MAX_RECIPIENTS_PER_EMAIL;
    }
    return null;
  }

  /**
   * Cuts a recipient list to $limit addresses and logs how many were dropped (never the addresses).
   * @param array<int, array{0: string, 1?: string}> $addresses
   * @return array<int, array{address: string, name: string}>
   */
  private function limitRecipients(string $field, array $addresses, int $limit): array {
    $dropped = count($addresses) - $limit;
    if ($dropped > 0) {
      $this->logDroppedRecipients(['field' => $field, 'dropped' => $dropped, 'limit' => $limit]);
    }
    return array_map([$this, 'formatAddressEntry'], array_slice($addresses, 0, $limit));
  }

  /**
   * @param array<string, string|int|null> $context Counts only, never addresses.
   */
  private function logDroppedRecipients(array $context): void {
    LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_SENDING)->warning(
      'Transactional email recipients over the sending method limit were dropped',
      $context
    );
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
      // @phpstan-ignore-next-line mb_convert_encoding will not return false, when we use mb_list_encodings(). It contains special encoding 'pass' ensuring it will always succeed.
      $text = @Html2Text::convert(strtolower($this->CharSet) === 'utf-8' ? $this->Body : mb_convert_encoding($this->Body, 'UTF-8', mb_list_encodings()));
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

  /**
   * @param array{0: string, 1?: string} $data PHPMailer address entry: [email, name]
   * @return array{address: string, name: string}
   */
  private function formatAddressEntry(array $data): array {
    return [
      'address' => $data[0],
      'name' => $data[1] ?? '',
    ];
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
