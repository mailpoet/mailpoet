<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Config\Env;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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

  private PatternsController $patternsController;

  private TemplatesController $templatesController;

  private EntityManager $entityManager;

  private WPFunctions $wpFunctions;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SettingsController $settings,
    WordPress $wp,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository,
    PatternsController $patternsController,
    TemplatesController $templatesController,
    WPFunctions $wpFunctions,
    EntityManager $entityManager
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
    $this->patternsController = $patternsController;
    $this->templatesController = $templatesController;
    $this->entityManager = $entityManager;
    $this->wpFunctions = $wpFunctions;
  }

  /**
   * Create an email from a template and store it in the database
   *
   * @param array $data Email data including subject, preheader, etc.
   * @return int|null The ID of the created email or null if the email couldn't be created
   */
  public function createEmail(array $data = []): ?int {
    $newsletter = $this->createNewsletterEntity($data);

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
   * Create a block editor email from a pattern and store it in the database.
   *
   * This method creates a WordPress post with the pattern content and links it
   * to a NewsletterEntity for use with the block email editor.
   *
   * @param array $data Email data including:
   *   - 'pattern' (string, required): The pattern name (e.g., 'welcome-email-content')
   *   - 'subject' (string, optional): Email subject
   *   - 'preheader' (string, optional): Email preheader
   *   - 'sender_name' (string, optional): Sender name
   *   - 'sender_address' (string, optional): Sender email address
   * @param string|null $templateSlug The slug of the template to associate with the email.
   * @return array{email_id: int, email_wp_post_id: int}|null The IDs of the created email and WP post, or null if creation failed
   */
  public function createBlockEditorEmail(array $data, ?string $templateSlug = null): ?array {
    $patternName = $data['pattern'] ?? null;
    if (!$patternName) {
      return null;
    }

    // Get pattern content
    $patternContent = $this->patternsController->getPatternContent($patternName);
    if ($patternContent === null) {
      return null;
    }

    // Create a WordPress post with the pattern content
    // The meta_input flag prevents other plugins from creating duplicate newsletters
    $postId = $this->wpFunctions->wpInsertPost([
      'post_content' => $patternContent,
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_author' => $this->wpFunctions->getCurrentUserId(),
      'post_title' => $data['subject'] ?? __('New Email', 'mailpoet'),
      'meta_input' => ['_mailpoet_is_automation_email' => '1'],
    ]);

    if (!is_int($postId) || $postId <= 0) {
      return null;
    }

    // Set the email template to associate the template wrapper (header, footer, unsubscribe links)
    $templateSlug = $templateSlug ?: $this->templatesController->getDefaultTemplateSlug();
    $this->wpFunctions->updatePostMeta($postId, '_wp_page_template', $templateSlug);

    // Create a new newsletter entity
    try {
      $wpPost = $this->entityManager->getReference(WpPostEntity::class, $postId);
      $newsletter = $this->createNewsletterEntity($data, $wpPost);

      $this->newslettersRepository->persist($newsletter);
      $this->newslettersRepository->flush();
    } catch (\Throwable $e) {
      $this->wpFunctions->wpDeletePost($postId, true);
      throw $e;
    }

    $emailId = $newsletter->getId();
    if ($emailId === null) {
      $this->wpFunctions->wpDeletePost($postId, true);
      return null;
    }

    return [
      'email_id' => $emailId,
      'email_wp_post_id' => $postId,
    ];
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
   * Create a NewsletterEntity with common automation email properties.
   *
   * @param array $data Email data including subject, preheader, sender_name, sender_address
   * @param WpPostEntity|null $wpPost Optional WP post to associate with the newsletter
   * @return NewsletterEntity The created newsletter entity (not persisted)
   */
  private function createNewsletterEntity(array $data, ?WpPostEntity $wpPost = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSubject($data['subject'] ?? '');
    $newsletter->setPreheader($data['preheader'] ?? '');
    $newsletter->setSenderName($data['sender_name'] ?? $this->getDefaultSenderName());
    $newsletter->setSenderAddress($data['sender_address'] ?? $this->getDefaultSenderAddress());
    $newsletter->setHash(Security::generateHash());

    if ($wpPost !== null) {
      $newsletter->setWpPost($wpPost);
    }

    return $newsletter;
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
