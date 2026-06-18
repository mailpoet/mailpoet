import {
  PanelBody,
  TextControl,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SenderEmailAddressWarning } from 'common/sender-email-address-warning';
import { UseSenderFields } from '../shared/use-sender-fields';

type SenderControlsProps = {
  senderFields: UseSenderFields;
};

export function SenderControls({
  senderFields,
}: SenderControlsProps): JSX.Element {
  return (
    <PanelBody title={__('Sender', 'mailpoet')} initialOpen>
      <VStack spacing={3}>
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
            <SenderEmailAddressWarning
              emailAddress={senderFields.senderAddress}
              mssActive={window.mailpoet_mss_active}
              isEmailAuthorized={senderFields.isSenderAddressAuthorized}
              showSenderDomainWarning={
                senderFields.showSenderDomainWarning &&
                !senderFields.senderAddressError
              }
              isPartiallyVerifiedDomain={senderFields.isPartiallyVerifiedDomain}
              senderRestrictions={window.mailpoet_sender_restrictions}
              onSuccessfulEmailOrDomainAuthorization={
                senderFields.handleSuccessfulAuthorization
              }
            />
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
      </VStack>
    </PanelBody>
  );
}
