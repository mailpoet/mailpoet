import { useHistory, useParams } from 'react-router-dom';
import { external, Icon } from '@wordpress/icons';
import { Heading } from 'common/typography/heading/heading';
import { MailPoet } from 'mailpoet';
import { List } from 'common/typography/list/list';
import { Button } from 'common';
import { OwnEmailServiceNote } from './own_email_service_note';

const mailpoetAccountUrl = MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
  MailPoet.subscribersCount,
  MailPoet.currentWpUserEmail,
  'starter',
  {
    ref: 'plugin-wizard',
    utm_source: 'plugin',
    utm_medium: 'onboarding',
    utm_campaign: 'purchase',
  },
);

type MSSStepFirstPartPropType = {
  subscribersCount: number;
  finishWizard: (redirect_url?: string) => void;
};

function MSSStepFirstPart({
  subscribersCount,
  finishWizard,
}: MSSStepFirstPartPropType): JSX.Element {
  const history = useHistory();
  const { step } = useParams<{ step: string }>();

  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFirstPartSubtitle')}</p>
      <div className="mailpoet-gap" />

      <div className="mailpoet-welcome-wizard-mss-list">
        <List>
          <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
          {subscribersCount < 1000 ? (
            <li>{MailPoet.I18n.t('welcomeWizardMSSList3Free')}</li>
          ) : (
            <li>{MailPoet.I18n.t('welcomeWizardMSSList3Paid')}</li>
          )}
        </List>
      </div>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        isFullWidth
        href={mailpoetAccountUrl}
        target="_blank"
        rel="noopener noreferrer"
        onClick={(event) => {
          event.preventDefault();
          window.open(mailpoetAccountUrl);
          history.push(`/steps/${step}/part/2`);
        }}
        iconEnd={<Icon icon={external} />}
      >
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartButton')}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <OwnEmailServiceNote finishWizard={finishWizard} />
    </>
  );
}

export { MSSStepFirstPart };
