import React from 'react';
import MailPoet from 'mailpoet';

import Tabs from './tabs.jsx';

function YourPrivacy() {
  return (
    <div>
      <Tabs tab="yourPrivacy" />

      <p>{MailPoet.I18n.t('yourPrivacyContent1')}</p>
      <p>{MailPoet.I18n.t('yourPrivacyContent2')}</p>
      <p>{MailPoet.I18n.t('yourPrivacyContent3')}</p>

      <a target="_blank" rel="noreferrer noopener" href="https://www.mailpoet.com/privacy-notice/" className="button button-primary">{MailPoet.I18n.t('yourPrivacyButton')}</a>
    </div>
  );
}

export default YourPrivacy;
