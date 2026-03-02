<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\WPCOM\DotcomHelperFunctions;

class CongratulatoryMssEmailControllerTest extends \MailPoetUnitTest {
  /** @var Mailer&\PHPUnit\Framework\MockObject\MockObject */
  private $mailer;

  /** @var Renderer&\PHPUnit\Framework\MockObject\MockObject */
  private $renderer;

  /** @var DotcomHelperFunctions&\PHPUnit\Framework\MockObject\MockObject */
  private $dotcomHelperFunctions;

  public function _before() {
    parent::_before();
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->dotcomHelperFunctions = $this->createMock(DotcomHelperFunctions::class);
  }

  private function createController(): CongratulatoryMssEmailController {
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($this->mailer);
    return new CongratulatoryMssEmailController(
      $mailerFactory,
      new MetaInfo(),
      $this->renderer,
      $this->dotcomHelperFunctions
    );
  }

  public function testItUsesMailPoetSubjectWhenNotGarden() {
    $this->dotcomHelperFunctions->method('isGarden')->willReturn(false);
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

  public function testItUsesNeutralSubjectWhenGarden() {
    $this->dotcomHelperFunctions->method('isGarden')->willReturn(true);
    $this->mailer->expects($this->once())
      ->method('send')
      ->with(
        $this->callback(function ($newsletter) {
          return strpos($newsletter['subject'], 'MailPoet') === false
            && strpos($newsletter['subject'], 'email sending') !== false;
        }),
        $this->anything(),
        $this->anything()
      );
    $this->createController()->sendCongratulatoryEmail('test@example.com');
  }

  public function testItRendersHtmlAndTxtTemplates() {
    $this->dotcomHelperFunctions->method('isGarden')->willReturn(false);
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
