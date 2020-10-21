import React from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';

function YourPrivacy() {
  return (
    <>
      <p>{MailPoet.I18n.t('yourPrivacyContent1')}</p>
      <p>{MailPoet.I18n.t('yourPrivacyContent2')}</p>
      <p>{MailPoet.I18n.t('yourPrivacyContent3')}</p>

      <Button dimension="small" target="_blank" rel="noreferrer noopener" href="https://www.mailpoet.com/privacy-notice/">
        {MailPoet.I18n.t('yourPrivacyButton')}
      </Button>
    </>
  );
}

export default YourPrivacy;
