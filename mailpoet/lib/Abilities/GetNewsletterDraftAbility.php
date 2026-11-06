<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class GetNewsletterDraftAbility implements Ability {
  public const NAME = 'mailpoet/get-newsletter-draft';

  /** @var NewsletterDrafts */
  private $drafts;

  /** @var NewsletterDraftReader */
  private $reader;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDrafts $drafts,
    NewsletterDraftReader $reader,
    WPFunctions $wp
  ) {
    $this->drafts = $drafts;
    $this->reader = $reader;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Get MailPoet newsletter draft', 'mailpoet'),
      'description' => __('Reads a newsletter draft in the block format that mailpoet/create-newsletter-draft takes, with the version mailpoet/update-newsletter-draft needs. Find ids in mailpoet/get-newsletter-draft-options. If editable is false, reason says why. If it is still a draft, a person can change it in the editor at edit_url.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'required' => ['id'],
        'additionalProperties' => false,
        'properties' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'output_schema' => $this->getOutputSchema(),
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getReadOnlyMeta(),
    ];
  }

  public function hasPermission(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  /**
   * @param array|null $input
   * @return array|\WP_Error
   */
  public function execute($input = null) {
    $newsletter = $this->drafts->findNewsletter((int)((array)$input)['id']);
    if (!$newsletter) {
      return $this->drafts->getNotFoundError();
    }
    $blocks = $this->reader->read($this->drafts->getContent($newsletter));
    $reason = $this->drafts->getNotEditableReason($newsletter, $blocks);
    return array_merge($this->drafts->buildOutput($newsletter), [
      'preheader' => $newsletter->getPreheader(),
      'editable' => $reason === null,
      'reason' => $reason,
      'blocks' => $blocks,
    ]);
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'additionalProperties' => false,
      'properties' => array_merge($this->drafts->getOutputProperties(), [
        'preheader' => ['type' => 'string'],
        'editable' => ['type' => 'boolean'],
        'reason' => ['type' => ['string', 'null']],
        'blocks' => ['type' => ['array', 'null']],
      ]),
    ];
  }
}
