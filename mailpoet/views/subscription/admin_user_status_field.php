<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

use MailPoet\Entities\SubscriberEntity;

/**
 * Template for the subscriber status field in WordPress admin
 *
 * @var bool $confirmationEnabled
 * @var string $defaultStatus
 */

// phpcs:disable Generic.Files.InlineHTML.Found
?>
<table class="form-table">
  <tr>
    <th scope="row">
      <label for="mailpoet_subscriber_status">
        <?php echo esc_html__('MailPoet Subscriber Status', 'mailpoet'); ?>
      </label>
    </th>
    <td>
      <select name="mailpoet_subscriber_status" id="mailpoet_subscriber_status">
        <option value="<?php echo esc_attr(SubscriberEntity::STATUS_SUBSCRIBED); ?>">
          <?php echo esc_html__('Subscribed', 'mailpoet'); ?>
        </option>

        <?php if ($confirmationEnabled) : ?>
        <option value="<?php echo esc_attr(SubscriberEntity::STATUS_UNCONFIRMED); ?>" selected="selected">
          <?php echo esc_html__('Unconfirmed (will receive a confirmation email)', 'mailpoet'); ?>
        </option>
        <?php endif; ?>

        <option value="<?php echo esc_attr(SubscriberEntity::STATUS_UNSUBSCRIBED); ?>" 
                  <?php echo !$confirmationEnabled ? 'selected="selected"' : ''; ?>>
          <?php echo esc_html__('Unsubscribed', 'mailpoet'); ?>
        </option>
      </select>
    </td>
  </tr>
</table>
<?php
// phpcs:enable Generic.Files.InlineHTML.Found 