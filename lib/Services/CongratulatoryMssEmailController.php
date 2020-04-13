<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;

class CongratulatoryMssEmailController {
  /** @var Mailer */
  private $mailer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var Renderer */
  private $renderer;

  public function __construct(
    Mailer $mailer,
    MetaInfo $mailerMetaInfo,
    Renderer $renderer
  ) {
    $this->mailer = $mailer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->renderer = $renderer;
  }

  public function sendCongratulatoryEmail(string $toEmailAddress) {
    $renderedNewsletter = [
      'subject' => _x('Sending with MailPoet works!', 'Subject of an email confirming that MailPoet Sending Service works', 'mailpoet'),
      'body' => [
        'html' => $this->renderer->render('emails/congratulatoryMssEmail.html'),
        'text' => $this->renderer->render('emails/congratulatoryMssEmail.txt'),
      ],
    ];

    $extraParams = [
      'meta' => $this->mailerMetaInfo->getSendingTestMetaInfo(),
    ];
    $this->mailer->send($renderedNewsletter, $toEmailAddress, $extraParams);
  }
}
