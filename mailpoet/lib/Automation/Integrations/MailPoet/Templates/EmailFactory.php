<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\WordPress;
use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;

class EmailFactory {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SettingsController */
  private $settings;

  /** @var string */
  private $templatesDirectory;

  /** @var WordPress */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SettingsController $settings,
    WordPress $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->templatesDirectory = Env::$libPath . '/Automation/Integrations/MailPoet/Templates/EmailTemplates';
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
    $newsletter->setSubject($data['subject'] ?? '');
    $newsletter->setPreheader($data['preheader'] ?? '');
    $newsletter->setSenderName($data['sender_name'] ?? $this->getDefaultSenderName());
    $newsletter->setSenderAddress($data['sender_address'] ?? $this->getDefaultSenderAddress());

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
      return null;
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
    return $this->templatesDirectory . '/' . $sanitizedTemplateName . '.json';
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
   * @param string $directory The directory where templates are stored
   * @return self
   */
  public function setTemplatesDirectory(string $directory): self {
    $this->templatesDirectory = $directory;
    return $this;
  }

  /**
   * Get the templates directory
   *
   * @return string The directory where templates are stored
   */
  public function getTemplatesDirectory(): string {
    return $this->templatesDirectory;
  }
}
