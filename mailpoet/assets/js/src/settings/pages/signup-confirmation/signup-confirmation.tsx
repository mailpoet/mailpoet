import { SaveButton } from 'settings/components';
import { EnableSignupConfirmation } from './enable-signup-confirmation';
import { EmailSubject } from './email-subject';
import { EmailContent } from './email-content';
import { ConfirmationPage } from './confirmation-page';
import { ConfirmationEmailCustomizer } from './confirmation-email-customizer';
import { DeleteUnconfirmedSubscribers } from './delete-unconfirmed-subscribers';

export function SignupConfirmation() {
  return (
    <div className="mailpoet-settings-grid">
      <EnableSignupConfirmation />
      <ConfirmationEmailCustomizer />
      <EmailSubject />
      <EmailContent />
      <ConfirmationPage />
      <DeleteUnconfirmedSubscribers />
      <SaveButton />
    </div>
  );
}
