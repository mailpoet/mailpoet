import { PanelBody, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Badge, Stack } from '@wordpress/ui';
import { UseSenderFields } from '../shared/use-sender-fields';
import { FreeMailSenderNotice } from './free-mail-sender-notice';
import { SenderAuthorizationNotice } from './sender-authorization-notice';
import {
  hasSenderAuthorizationIssue,
  isFreeMailSenderAddress,
} from './sender-notice-utils';

type SenderControlsProps = {
  senderFields: UseSenderFields;
};

function SenderPanelTitle({
  senderFields,
  isOpen,
}: {
  senderFields: UseSenderFields;
  isOpen: boolean;
}): JSX.Element {
  const needsAttention =
    senderFields.hasValidationErrors ||
    isFreeMailSenderAddress(senderFields.senderAddress) ||
    hasSenderAuthorizationIssue(senderFields);

  if (isOpen || (!needsAttention && !senderFields.senderAddress)) {
    return <span>{__('Sender', 'mailpoet')}</span>;
  }

  return (
    <Stack
      direction="row"
      gap="xs"
      justify={needsAttention ? 'space-between' : 'start'}
      className="mailpoet-send-panel__sender-title"
    >
      <span>{__('Sender:', 'mailpoet')}</span>
      {needsAttention ? (
        <Badge
          className="mailpoet-send-panel__sender-title-badge"
          intent="medium"
        >
          {__('Needs attention', 'mailpoet')}
        </Badge>
      ) : (
        <span className="mailpoet-send-panel__sender-title-email">
          {senderFields.senderAddress}
        </span>
      )}
    </Stack>
  );
}

export function SenderControls({
  senderFields,
}: SenderControlsProps): JSX.Element {
  const [isOpen, setIsOpen] = useState(false);

  const title = (
    <SenderPanelTitle senderFields={senderFields} isOpen={isOpen} />
  );

  return (
    <PanelBody
      title={title as unknown as string}
      opened={isOpen}
      onToggle={setIsOpen}
    >
      <Stack direction="column" gap="lg">
        <TextControl
          className={
            senderFields.senderNameError
              ? 'mailpoet-send-panel__field-error'
              : ''
          }
          data-automation-id="email_send_panel_sender_name"
          help={senderFields.senderNameError}
          label={__('"From" name', 'mailpoet')}
          onChange={senderFields.setSenderName}
          placeholder={__('John Doe', 'mailpoet')}
          value={senderFields.senderName}
        />
        <div className="mailpoet-send-panel__sender-address-field">
          <TextControl
            className={
              senderFields.senderAddressError
                ? 'mailpoet-send-panel__field-error'
                : ''
            }
            data-automation-id="email_send_panel_sender_address"
            help={senderFields.senderAddressError}
            label={__('"From" email address', 'mailpoet')}
            onBlur={senderFields.validateSenderAddress}
            onChange={senderFields.setSenderAddress}
            placeholder={__('john.doe@email.com', 'mailpoet')}
            type="email"
            value={senderFields.senderAddress}
          />
          <div className="mailpoet-send-panel__sender-warning">
            <FreeMailSenderNotice senderFields={senderFields} />
            <SenderAuthorizationNotice senderFields={senderFields} />
          </div>
        </div>
        <TextControl
          data-automation-id="email_send_panel_reply_to_name"
          label={__('"Reply-to" name', 'mailpoet')}
          onChange={senderFields.setReplyToName}
          placeholder={__('John Doe', 'mailpoet')}
          value={senderFields.replyToName}
        />
        <TextControl
          className={
            senderFields.replyToAddressError
              ? 'mailpoet-send-panel__field-error'
              : ''
          }
          data-automation-id="email_send_panel_reply_to_address"
          help={senderFields.replyToAddressError}
          label={__('"Reply-to" email address', 'mailpoet')}
          onBlur={senderFields.validateReplyToAddress}
          onChange={senderFields.setReplyToAddress}
          placeholder={__('john.doe@email.com', 'mailpoet')}
          type="email"
          value={senderFields.replyToAddress}
        />
      </Stack>
    </PanelBody>
  );
}
