<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\WPCOM\DotcomHelperFunctions;

class CongratulatoryMssEmailController {
  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var Renderer */
  private $renderer;

  /** @var DotcomHelperFunctions */
  private $dotcomHelperFunctions;

  public function __construct(
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    Renderer $renderer,
    DotcomHelperFunctions $dotcomHelperFunctions
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->renderer = $renderer;
    $this->dotcomHelperFunctions = $dotcomHelperFunctions;
  }

  public function sendCongratulatoryEmail(string $toEmailAddress) {
    $subject = $this->dotcomHelperFunctions->isGarden()
      ? _x('Your email sending is set up!', 'Subject of an email confirming that the email sending service works', 'mailpoet')
      : _x('Sending with MailPoet works!', 'Subject of an email confirming that MailPoet Sending Service works', 'mailpoet');
    $renderedNewsletter = [
      'subject' => $subject,
      'body' => [
        'html' => $this->renderer->render('emails/congratulatoryMssEmail.html'),
        'text' => $this->renderer->render('emails/congratulatoryMssEmail.txt'),
      ],
    ];

    $extraParams = [
      'meta' => $this->mailerMetaInfo->getSendingTestMetaInfo(),
    ];
    $this->mailerFactory->getDefaultMailer()->send($renderedNewsletter, $toEmailAddress, $extraParams);
  }
}
