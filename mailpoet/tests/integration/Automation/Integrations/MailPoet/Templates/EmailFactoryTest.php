<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Templates\EmailFactory;
use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoetTest;
use PHPUnit\Framework\MockObject\MockObject;

class EmailFactoryTest extends MailPoetTest {
  /** @var EmailFactory */
  private $emailFactory;

  /** @var WordPress */
  private $wp;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SettingsController|MockObject */
  private $settings;

  /** @var string */
  private $tempDir;

  public function _before(): void {
    parent::_before();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->settings = $this->getMockBuilder(SettingsController::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->wp = new WordPress();
    $this->emailFactory = new EmailFactory($this->newslettersRepository, $this->settings, $this->wp);

    // Create a temporary directory for test templates
    $this->tempDir = Env::$tempPath . '/mailpoet_test_templates_' . uniqid();
    mkdir($this->tempDir);
    $this->emailFactory->setTemplatesDirectory($this->tempDir);
  }

  public function _after(): void {
    parent::_after();
    // Clean up temporary directory
    if (is_dir($this->tempDir)) {
      $files = glob("$this->tempDir/*.*");
      if ($files !== false) {
        array_map('unlink', $files);
      }
      rmdir($this->tempDir);
    }
  }

  public function testItCreatesEmailWithCustomSettings(): void {
    $customData = [
      'subject' => 'Custom Subject',
      'preheader' => 'Custom Preheader',
      'sender_name' => 'Custom Sender',
      'sender_address' => 'custom@example.com',
      'content' => ['html' => '<p>Custom content</p>'],
    ];

    $emailId = $this->emailFactory->createEmail($customData);

    $this->assertIsInt($emailId);
    $this->assertGreaterThan(0, $emailId);

    // Verify the created newsletter
    $newsletter = $this->newslettersRepository->findOneById($emailId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertEquals(NewsletterEntity::TYPE_AUTOMATION, $newsletter->getType());
    $this->assertEquals('Custom Subject', $newsletter->getSubject());
    $this->assertEquals('Custom Preheader', $newsletter->getPreheader());
    $this->assertEquals('Custom Sender', $newsletter->getSenderName());
    $this->assertEquals('custom@example.com', $newsletter->getSenderAddress());
    $this->assertEquals(['html' => '<p>Custom content</p>'], $newsletter->getBody());
  }

  public function testItCreatesEmailWithTemplate(): void {
    $templateName = 'test-template';
    $templateContent = ['html' => '<p>Template content</p>'];
    $templateFile = $this->tempDir . '/' . $templateName . '.json';
    file_put_contents($templateFile, json_encode(['body' => $templateContent]));

    // Set up default sender settings
    $this->settings->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(
        ['sender.name', ''],
        ['sender.address', '']
      )
      ->willReturnOnConsecutiveCalls(
        'Default Sender',
        'default@example.com'
      );

    $emailId = $this->emailFactory->createEmail(['template' => $templateName]);

    $this->assertIsInt($emailId);
    $this->assertGreaterThan(0, $emailId);

    // Verify the created newsletter
    $newsletter = $this->newslettersRepository->findOneById($emailId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertEquals(NewsletterEntity::TYPE_AUTOMATION, $newsletter->getType());
    $this->assertEquals('', $newsletter->getSubject());
    $this->assertEquals('', $newsletter->getPreheader());
    $this->assertEquals('Default Sender', $newsletter->getSenderName());
    $this->assertEquals('default@example.com', $newsletter->getSenderAddress());
    $this->assertEquals($templateContent, $newsletter->getBody());
  }

  public function testItHandlesTemplateLoading(): void {
    $unsafePath = Env::$file; // Main plugin file
    $this->assertNull($this->emailFactory->loadTemplate($unsafePath));

    $nonExistentPath = 'non-existent-template';
    $this->assertNull($this->emailFactory->loadTemplate($nonExistentPath));

    $templateName = 'valid-template';
    $templateContent = ['html' => '<p>Valid template content</p>'];
    $templateFile = $this->tempDir . '/' . $templateName . '.json';
    file_put_contents($templateFile, json_encode(['body' => $templateContent]));

    $loadedContent = $this->emailFactory->loadTemplate($templateName);
    $this->assertEquals($templateContent, $loadedContent);
  }

  public function testItManagesTemplateDirectory(): void {
    $customDir = '/custom/templates/dir';

    $this->emailFactory->setTemplatesDirectory($customDir);
    $this->assertEquals($customDir, $this->emailFactory->getTemplatesDirectory());

    $this->emailFactory->setTemplatesDirectory(Env::$libPath . '/Automation/Integrations/MailPoet/Templates/EmailTemplates');
    $this->assertEquals(Env::$libPath . '/Automation/Integrations/MailPoet/Templates/EmailTemplates', $this->emailFactory->getTemplatesDirectory());
  }
}
