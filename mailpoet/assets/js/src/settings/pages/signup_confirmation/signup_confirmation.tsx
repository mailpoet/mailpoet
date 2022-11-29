import { SaveButton } from 'settings/components';
import { EnableSignupConfirmation } from './enable_signup_confirmation';
import { EmailSubject } from './email_subject';
import { EmailContent } from './email_content';
import { ConfirmationPage } from './confirmation_page';
import { ConfirmationEmailCustomizer } from './confirmation_email_customizer';

export function SignupConfirmation() {
  return (
    <div className="mailpoet-settings-grid">
      <EnableSignupConfirmation />
      <ConfirmationEmailCustomizer />
      <EmailSubject />
      <EmailContent />
      <ConfirmationPage />
      <SaveButton />
    </div>
  );
}
