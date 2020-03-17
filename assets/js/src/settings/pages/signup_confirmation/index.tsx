import React from 'react';
import EnableSignupConfirmation from './enable_signup_confirmation';
import EmailSubject from './email_subject';
import EmailContent from './email_content';
import ConfirmationPage from './confirmation_page';

export default function SignupConfirmation() {
  return (
    <div className="mailpoet-settings-grid">
      <EnableSignupConfirmation />
      <EmailSubject />
      <EmailContent />
      <ConfirmationPage />
    </div>
  );
}
