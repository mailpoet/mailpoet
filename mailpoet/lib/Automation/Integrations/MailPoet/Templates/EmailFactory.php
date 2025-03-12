<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Security;

class EmailFactory {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SettingsController */
  private $settings;

  /** @var string|null */
  protected $templatesDirectory;

  /** @var WordPress */
  private $wp;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SettingsController $settings,
    WordPress $wp,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
  }

  /**
   * Create an email from a template and store it in the database
   *
   * @param array $data Email data including subject, preheader, etc.
   * @return int|null The ID of the created email or null if the email couldn't be created
   */
  public function createEmail(array $data = []): ?int {
    // Create a new newsletter entity
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSubject($data['subject'] ?? '');
    $newsletter->setPreheader($data['preheader'] ?? '');
    $newsletter->setSenderName($data['sender_name'] ?? $this->getDefaultSenderName());
    $newsletter->setSenderAddress($data['sender_address'] ?? $this->getDefaultSenderAddress());
    $newsletter->setHash(Security::generateHash());

    // Set content if provided
    if (isset($data['content'])) {
      $newsletter->setBody($data['content']);
    } elseif (isset($data['template'])) {
      $template = $this->loadTemplate($data['template']);
      if ($template) {
        $newsletter->setBody($template);
      }
    }

    // Save the newsletter to the database
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();

    // Return the newsletter ID
    return $newsletter->getId();
  }

  /**
   * Sets automation and step ID for all email steps in an automation
   *
   * @param Automation $automation The automation object
   * @return void
   */
  public function setAutomationIdForEmails(Automation $automation): void {
    // Skip if automation ID is not set
    try {
      $automationId = $automation->getId();
    } catch (\Exception $e) {
      return;
    }

    $steps = $automation->getSteps();
    $emailSteps = array_filter($steps, function($step) {
      return $step->getKey() === 'mailpoet:send-email';
    });

    foreach ($emailSteps as $step) {
      $args = $step->getArgs();
      $emailId = $args['email_id'] ?? null;

      if ($emailId) {
        $newsletter = $this->newslettersRepository->findOneById($emailId);
        if (!$newsletter) {
          continue;
        }

        $existingAutomationId = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID);
        $existingStepId = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID);

        if ($existingAutomationId === (string)$automationId && $existingStepId === $step->getId()) {
          continue;
        }

        $this->storeNewsletterOption(
          $newsletter,
          NewsletterOptionFieldEntity::NAME_AUTOMATION_ID,
          (string)$automationId
        );

        $this->storeNewsletterOption(
          $newsletter,
          NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID,
          $step->getId()
        );

        $this->newslettersRepository->flush();
      }
    }
  }

  /**
   * Stores a newsletter option
   *
   * @param NewsletterEntity $newsletter The newsletter entity
   * @param string $optionName The name of the option
   * @param string|null $optionValue The value of the option
   * @return void
   */
  private function storeNewsletterOption(NewsletterEntity $newsletter, string $optionName, ?string $optionValue = null): void {
    if (!$optionValue || !$this->newsletterOptionsRepository || !$this->newsletterOptionFieldsRepository) {
      return;
    }

    $existingOption = $newsletter->getOption($optionName);
    if ($existingOption && $existingOption->getValue() === $optionValue) {
      return; // Skip if option already exists with the same value
    }

    $field = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => $optionName,
      'newsletterType' => $newsletter->getType(),
    ]);

    if (!$field) {
      return;
    }

    // If option exists but with different value, update it
    if ($existingOption) {
      $existingOption->setValue($optionValue);
      return;
    }

    // Otherwise create a new option
    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue($optionValue);
    $this->newsletterOptionsRepository->persist($option);
    $newsletter->getOptions()->add($option);
  }

  /**
   * Get the default sender name from settings
   */
  private function getDefaultSenderName(): string {
    return $this->settings->get('sender.name', '');
  }

  /**
   * Get the default sender address from settings
   */
  private function getDefaultSenderAddress(): string {
    return $this->settings->get('sender.address', '');
  }

  /**
   * Load a template from a file
   *
   * @param string $templateName The name of the template file (without .json extension)
   * @return array|null The template body or null if the template doesn't exist
   */
  public function loadTemplate(string $templateName): ?array {
    $templatePath = $this->getTemplatePath($templateName);

    if (!file_exists($templatePath)) {
      throw new NotFoundException('Template not found: ' . $templateName);
    }
    return $this->fetchEmailTemplate($templatePath);
  }

  /**
   * Get the path to a template file
   *
   * @param string $templateName The name of the template file (without .json extension)
   * @return string The full path to the template file
   */
  protected function getTemplatePath(string $templateName): string {
    $sanitizedTemplateName = $this->wp->sanitizeFileName($templateName);
    return $this->getTemplatesDirectory() . '/' . $sanitizedTemplateName . '.json';
  }

  /**
   * Fetch email template from a file
   *
   * @param string $templatePath The path to the template file
   * @return array|null The template body or null if the template couldn't be loaded
   */
  private function fetchEmailTemplate(string $templatePath): ?array {
    $templateString = file_get_contents($templatePath);
    if ($templateString === false) {
      return null;
    }

    $templateArr = json_decode((string)$templateString, true);
    if (!is_array($templateArr) || !isset($templateArr['body'])) {
      return null;
    }

    return $templateArr['body'];
  }

  /**
   * Set the templates directory
   *
   * @param string|null $directory The directory where templates are stored
   * @return self
   */
  public function setTemplatesDirectory(?string $directory): self {
    $this->templatesDirectory = $directory;
    return $this;
  }

  /**
   * Get the templates directory
   *
   * @return string The directory where templates are stored
   */
  public function getTemplatesDirectory(): string {
    return $this->templatesDirectory
      ?: Env::$libPath . '/Automation/Integrations/MailPoet/Templates/EmailTemplates';
  }
}
