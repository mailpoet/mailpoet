<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

class OrderNoteAddedTrigger implements Trigger {

  /** @var WordPress */
  protected $wp;

  /** @var WooCommerce */
  protected $woocommerce;

  public function __construct(
    WordPress $wp,
    WooCommerce $woocommerceHelper
  ) {
    $this->wp = $wp;
    $this->woocommerce = $woocommerceHelper;
  }

  public function getKey(): string {
    return 'woocommerce:order-note-added';
  }

  public function getName(): string {
    // translators: automation trigger title
    return __('Order note added', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'note_contains' => Builder::string()->default(''),
      'note_type' => Builder::string()->default('all'),
    ]);
  }

  public function getSubjectKeys(): array {
    return [
      OrderSubject::KEY,
      CustomerSubject::KEY,
      CommentSubject::KEY,
    ];
  }

  public function validate(StepValidationArgs $args): void {
    $triggerArgs = $args->getStep()->getArgs();
    $noteType = $triggerArgs['note_type'] ?? 'all';

    if (!in_array($noteType, ['all', 'customer', 'private'], true)) {
      throw new \InvalidArgumentException(
        sprintf('Invalid note_type "%s". Allowed values: all, customer, private', $noteType)
      );
    }
  }

  public function registerHooks(): void {
    $this->wp->addAction(
      'woocommerce_order_note_added',
      [
        $this,
        'handle',
      ],
      10,
      2
    );
  }

  public function handle(int $commentId, \WC_Order $order): void {
    $comment = get_comment($commentId);
    if (!$comment) {
      return;
    }

    $isCustomerNote = (bool)get_comment_meta($commentId, 'is_customer_note', true);
    $noteType = $isCustomerNote ? 'customer' : 'private';

    $this->wp->doAction(Hooks::TRIGGER, $this, [
      new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]),
      new Subject(CustomerSubject::KEY, ['customer_id' => $order->get_customer_id(), 'order_id' => $order->get_id()]),
      new Subject(CommentSubject::KEY, [
        'comment_id' => $commentId,
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'comment_content' => $comment->comment_content,
        'note_type' => $noteType,
      ]),
    ]);
  }

  public function isTriggeredBy(StepRunArgs $args): bool {
    $triggerArgs = $args->getStep()->getArgs();
    $configuredNoteContains = $triggerArgs['note_contains'] ?? '';
    $configuredNoteType = $triggerArgs['note_type'] ?? 'all';

    // Get the comment from the CommentPayload
    try {
      $commentPayload = $args->getSinglePayloadByClass(\MailPoet\Automation\Integrations\WordPress\Payloads\CommentPayload::class);
    } catch (\Exception $e) {
      return false;
    }

    $comment = $commentPayload->getComment();
    if (!$comment) {
      return false;
    }

    // Check note content filter
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if (($configuredNoteContains !== '') && (stripos($comment->comment_content, $configuredNoteContains) === false)) {
      return false;
    }

    // Check note type filter
    if ($configuredNoteType !== 'all') {
      $commentSubjectEntry = $args->getSingleSubjectEntry(CommentSubject::KEY);
      $subjectArgs = $commentSubjectEntry->getSubjectData()->getArgs();
      $actualNoteType = $subjectArgs['note_type'] ?? null;
      if ($actualNoteType !== $configuredNoteType) {
        return false;
      }
    }

    return true;
  }
}
