import React from 'react';
import Button from 'common/button/button';
import MailPoet from 'mailpoet';

type Props = {
  onProceed?: () => void;
}

export default ({ onProceed }: Props): JSX.Element => (
  <div className="mailpoet-offer-clearout-step-container">
    <p>{MailPoet.I18n.t('offerClearoutText1')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText2')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText3')}</p>
    <p>
      {onProceed && (
        <Button onClick={onProceed} variant="tertiary">
          {MailPoet.I18n.t('clearoutGotIt')}
        </Button>
      )}
      <Button target="_blank" href="https://clearout.io/?ref=mailpoet">
        {MailPoet.I18n.t('tryClearout')}
      </Button>
    </p>
  </div>
);
