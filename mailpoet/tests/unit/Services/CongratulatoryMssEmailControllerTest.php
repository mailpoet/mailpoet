<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;

class CongratulatoryMssEmailControllerTest extends \MailPoetUnitTest {
  /** @var Mailer&\PHPUnit\Framework\MockObject\MockObject */
  private $mailer;

  /** @var Renderer&\PHPUnit\Framework\MockObject\MockObject */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
  }

  private function createController(): CongratulatoryMssEmailController {
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($this->mailer);
    return new CongratulatoryMssEmailController(
      $mailerFactory,
      new MetaInfo(),
      $this->renderer
    );
  }

  public function testItUsesMailPoetSubject() {
    $this->mailer->expects($this->once())
      ->method('send')
      ->with(
        $this->callback(function ($newsletter) {
          return strpos($newsletter['subject'], 'MailPoet') !== false;
        }),
        $this->anything(),
        $this->anything()
      );
    $this->createController()->sendCongratulatoryEmail('test@example.com');
  }

  public function testItRendersHtmlAndTxtTemplates() {
    $this->renderer->expects($this->exactly(2))
      ->method('render')
      ->withConsecutive(
        ['emails/congratulatoryMssEmail.html'],
        ['emails/congratulatoryMssEmail.txt']
      );
    $this->mailer->expects($this->once())->method('send');
    $this->createController()->sendCongratulatoryEmail('test@example.com');
  }
}
