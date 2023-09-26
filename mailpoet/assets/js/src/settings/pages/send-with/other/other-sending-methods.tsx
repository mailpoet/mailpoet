import { useSetting } from 'settings/store/hooks';
import { SendingMethod } from './sending-method';
import { SPF } from './spf';
import { TestSending } from './test-sending';
import { ActivateOrCancel } from './activate-or-cancel';
import { PHPMailFields } from './php-mail-fields';
import { SmtpFields } from './smtp-fields';
import { AmazonSesFields } from './amazon-ses-fields';
import { SendGridFields } from './sendgrid-fields';

export function OtherSendingMethods() {
  const [method] = useSetting('mta', 'method');
  return (
    <div className="mailpoet-settings-grid">
      <SendingMethod />
      {method === 'PHPMail' && <PHPMailFields />}
      {method === 'SMTP' && <SmtpFields />}
      {method === 'AmazonSES' && <AmazonSesFields />}
      {method === 'SendGrid' && <SendGridFields />}
      <SPF />
      <TestSending />
      <ActivateOrCancel />
    </div>
  );
}
