<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Templates\EmailFactory;
use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
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

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var string */
  private $tempDir;

  public function _before(): void {
    parent::_before();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->settings = $this->getMockBuilder(SettingsController::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Set up default sender settings
    $this->settings->method('get')
      ->will($this->returnValueMap([
        ['sender.name', '', 'Default Sender'],
        ['sender.address', '', 'default@example.com'],
      ]));

    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
    $this->newsletterOptionFieldsRepository = $this->diContainer->get(NewsletterOptionFieldsRepository::class);
    $this->wp = new WordPress();
    $this->emailFactory = new EmailFactory(
      $this->newslettersRepository,
      $this->settings,
      $this->wp,
      $this->newsletterOptionsRepository,
      $this->newsletterOptionFieldsRepository
    );

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

  public function testItHandlesTemplateLoadingForWrongPath(): void {
    $unsafePath = Env::$file; // Main plugin file
    $this->expectException(\MailPoet\Automation\Engine\Exceptions\NotFoundException::class);
    $this->emailFactory->loadTemplate($unsafePath);
  }

  public function testItHandlesTemplateLoadingForNonExistentTemplate(): void {
    $nonExistentPath = 'non-existent-template';
    $this->expectException(\MailPoet\Automation\Engine\Exceptions\NotFoundException::class);
    $this->emailFactory->loadTemplate($nonExistentPath);
  }

  public function testItHandlesTemplateLoadingForValidTemplate(): void {
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

    $this->emailFactory->setTemplatesDirectory(null);
    $this->assertEquals(Env::$libPath . '/Automation/Integrations/MailPoet/Templates/EmailTemplates', $this->emailFactory->getTemplatesDirectory());
  }

  public function testItSetsAutomationIdForEmails(): void {
    $emailId1 = $this->emailFactory->createEmail([
      'subject' => 'Test Email 1',
      'content' => ['html' => '<p>Test content 1</p>'],
    ]);

    $emailId2 = $this->emailFactory->createEmail([
      'subject' => 'Test Email 2',
      'content' => ['html' => '<p>Test content 2</p>'],
    ]);

    $step1 = new Step(
      'step-1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => $emailId1],
      [],
      null
    );

    $step2 = new Step(
      'step-2',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => $emailId2],
      [],
      null
    );

    $step3 = new Step(
      'step-3',
      Step::TYPE_ACTION,
      'some-other-action',
      [],
      [],
      null
    );

    $rootStep = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [
        new NextStep('step-1'),
        new NextStep('step-2'),
        new NextStep('step-3'),
      ],
      null
    );

    $steps = [
      'root' => $rootStep,
      'step-1' => $step1,
      'step-2' => $step2,
      'step-3' => $step3,
    ];

    $automation = new Automation(
      'Test Automation',
      $steps,
      wp_get_current_user(),
      123
    );

    $this->emailFactory->setAutomationIdForEmails($automation);

    $this->entityManager->clear();

    // Email 1
    $newsletter1 = $this->newslettersRepository->findOneById($emailId1);
    $this->assertNotNull($newsletter1, 'Newsletter 1 not found');
    $options1 = $newsletter1->getOptions();

    $foundAutomationId = false;
    $foundStepId = false;

    foreach ($options1 as $option) {
      $optionField = $option->getOptionField();
      $this->assertNotNull($optionField, 'Option field is null');
      $fieldName = $optionField->getName();

      if ($fieldName === NewsletterOptionFieldEntity::NAME_AUTOMATION_ID) {
        $foundAutomationId = true;
        $this->assertEquals('123', $option->getValue());
      }

      if ($fieldName === NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID) {
        $foundStepId = true;
        $this->assertEquals('step-1', $option->getValue());
      }
    }

    $this->assertTrue($foundAutomationId);
    $this->assertTrue($foundStepId);

    // Email 2
    $newsletter2 = $this->newslettersRepository->findOneById($emailId2);
    $this->assertNotNull($newsletter2, 'Newsletter 2 not found');
    $options2 = $newsletter2->getOptions();

    $foundAutomationId = false;
    $foundStepId = false;

    foreach ($options2 as $option) {
      $optionField = $option->getOptionField();
      $this->assertNotNull($optionField, 'Option field is null');
      $fieldName = $optionField->getName();

      if ($fieldName === NewsletterOptionFieldEntity::NAME_AUTOMATION_ID) {
        $foundAutomationId = true;
        $this->assertEquals('123', $option->getValue());
      }

      if ($fieldName === NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID) {
        $foundStepId = true;
        $this->assertEquals('step-2', $option->getValue());
      }
    }

    $this->assertTrue($foundAutomationId);
    $this->assertTrue($foundStepId);
  }

  public function testItHandlesMissingNewsletterWhenSettingAutomationIdForEmails(): void {
    $step = new Step(
      'step-1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => 99999], // Non-existent email ID
      [],
      null
    );

    $rootStep = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [new NextStep('step-1')],
      null
    );

    $steps = [
      'root' => $rootStep,
      'step-1' => $step,
    ];

    $automation = new Automation(
      'Test Automation',
      $steps,
      wp_get_current_user(),
      123
    );

    // This should not throw an exception
    $this->emailFactory->setAutomationIdForEmails($automation);
  }

  public function testItHandlesAutomationWithNoId(): void {
    // Create an automation with no ID
    $step = new Step(
      'step-1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => 1], // Any email ID
      [],
      null
    );

    $rootStep = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [new NextStep('step-1')],
      null
    );

    $steps = [
      'root' => $rootStep,
      'step-1' => $step,
    ];

    $automation = new Automation(
      'Test Automation',
      $steps,
      wp_get_current_user(),
      null // No ID
    );

    // This should not throw an exception
    $this->emailFactory->setAutomationIdForEmails($automation);
  }

  public function testItSkipsEmailsWithCorrectAutomationId(): void {
    $emailId = $this->emailFactory->createEmail([
      'subject' => 'Test Email',
      'content' => ['html' => '<p>Test content</p>'],
    ]);

    $step = new Step(
      'step-1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => $emailId],
      [],
      null
    );

    $rootStep = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [new NextStep('step-1')],
      null
    );

    $steps = [
      'root' => $rootStep,
      'step-1' => $step,
    ];

    $automation = new Automation(
      'Test Automation',
      $steps,
      wp_get_current_user(),
      123
    );

    // Set automation ID for the first time
    $this->emailFactory->setAutomationIdForEmails($automation);

    // Get the newsletter and verify options are set
    $newsletter = $this->newslettersRepository->findOneById($emailId);
    $this->assertNotNull($newsletter);

    $automationIdOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID);
    $stepIdOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID);

    $this->assertNotNull($automationIdOption);
    $this->assertNotNull($stepIdOption);
    $this->assertEquals('123', $automationIdOption->getValue());
    $this->assertEquals('step-1', $stepIdOption->getValue());

    // Mock the repository to track if flush is called
    $repositoryMock = $this->createMock(NewslettersRepository::class);
    $repositoryMock->expects($this->never())
      ->method('flush');

    // Replace the repository in the email factory with our mock
    $reflectionProperty = new \ReflectionProperty(EmailFactory::class, 'newslettersRepository');
    $reflectionProperty->setAccessible(true);
    $originalRepository = $reflectionProperty->getValue($this->emailFactory);
    $reflectionProperty->setValue($this->emailFactory, $repositoryMock);

    // Call setAutomationIdForEmails again - should skip the email
    $this->emailFactory->setAutomationIdForEmails($automation);

    // Restore the original repository
    $reflectionProperty->setValue($this->emailFactory, $originalRepository);
  }

  public function testItUpdatesEmailsWithDifferentAutomationId(): void {
    $emailId = $this->emailFactory->createEmail([
      'subject' => 'Test Email',
      'content' => ['html' => '<p>Test content</p>'],
    ]);

    $step = new Step(
      'step-1',
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      ['email_id' => $emailId],
      [],
      null
    );

    $rootStep = new Step(
      'root',
      Step::TYPE_ROOT,
      'root',
      [],
      [new NextStep('step-1')],
      null
    );

    $steps = [
      'root' => $rootStep,
      'step-1' => $step,
    ];

    // Create first automation with ID 123
    $automation1 = new Automation(
      'Test Automation 1',
      $steps,
      wp_get_current_user(),
      123
    );

    // Set automation ID for the first time
    $this->emailFactory->setAutomationIdForEmails($automation1);

    // Create second automation with ID 456
    $automation2 = new Automation(
      'Test Automation 2',
      $steps,
      wp_get_current_user(),
      456
    );

    // Set automation ID again with different automation
    $this->emailFactory->setAutomationIdForEmails($automation2);

    // Get the newsletter and verify options are updated
    $newsletter = $this->newslettersRepository->findOneById($emailId);
    $this->assertNotNull($newsletter);

    $automationIdOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID);
    $this->assertNotNull($automationIdOption);
    $this->assertEquals('456', $automationIdOption->getValue());
  }
}
