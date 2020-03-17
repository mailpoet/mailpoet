import React from 'react';
import EnableSignupConfirmation from './enable_signup_confirmation';
import EmailSubject from './email_subject';

export default function SignupConfirmation() {
  return (
    <div className="mailpoet-settings-grid">
      <EnableSignupConfirmation />
      <EmailSubject />
    </div>
  );
}
