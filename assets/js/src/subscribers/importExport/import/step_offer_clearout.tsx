import React from 'react';
import Button from 'common/button/button';
import MailPoet from 'mailpoet';

type Props = {
  history: History,
}

export default ({ history }: Props) => (
  <div className="mailpoet-offer-clearout-step__container">
    <p>{MailPoet.I18n.t('offerClearoutText1')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText2')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText3')}</p>
    <p>
      <Button
        dimension="small"
        variant="dark"
        target="_blank"
        href="https://clearout.io/?ref=mailpoet"
      >
        {MailPoet.I18n.t('tryClearout')}
      </Button>
      <Button
        onClick={() => (history.push('step_method_selection'))}
        variant="link-dark"
        dimension="small"
      >
        {MailPoet.I18n.t('clearoutGotIt')}
      </Button>
    </p>
  </div>
);
