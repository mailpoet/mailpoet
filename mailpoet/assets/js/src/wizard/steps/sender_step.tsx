import { MailPoet } from 'mailpoet';
import jQuery from 'jquery';
import { Grid } from '../../common/grid';
import { Heading } from '../../common/typography/heading/heading';
import { Button, Input } from '../../common';

type WelcomeWizardSenderStepPropType = {
  skipStep: (event: React.MouseEvent<HTMLButtonElement>) => void;
  loading: boolean;
  update_sender: (data: Record<string, string>) => void;
  submit_sender: () => void;
  sender: {
    name: string;
    address: string;
  };
};

function WelcomeWizardSenderStep({
  skipStep,
  loading,
  update_sender,
  submit_sender,
  sender = null,
}: WelcomeWizardSenderStepPropType): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardLetsStartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>
        <b>{MailPoet.I18n.t('welcomeWizardSenderTitle')}</b>
        <br />
        {MailPoet.I18n.t('welcomeWizardSenderText')}
      </p>
      <div className="mailpoet-gap" />

      <form
        id="mailpoet_sender_form"
        onSubmit={(e) => {
          e.preventDefault();
          if (!jQuery('#mailpoet_sender_form').parsley().validate()) {
            return;
          }
          submit_sender();
        }}
      >
        <Grid.TwoColumns>
          <label htmlFor="senderName">
            <span className="mailpoet-wizard-label">
              {MailPoet.I18n.t('senderName')}
            </span>
            <Input
              isFullWidth
              name="senderName"
              type="text"
              placeholder="John Doe"
              value={sender ? sender.name : ''}
              data-parsley-required
              onChange={(e) => update_sender({ name: e.target.value })}
            />
          </label>

          <label htmlFor="senderAddress">
            <span className="mailpoet-wizard-label">
              {MailPoet.I18n.t('senderAddress')}
            </span>
            <Input
              isFullWidth
              name="senderAddress"
              type="text"
              placeholder="john@doe.com"
              value={sender ? sender.address : ''}
              data-parsley-required
              data-parsley-type="email"
              onChange={(e) => update_sender({ address: e.target.value })}
            />
          </label>
        </Grid.TwoColumns>

        <div className="mailpoet-gap" />
        <div className="mailpoet-gap" />

        <Button isFullWidth type="submit" withSpinner={loading}>
          {MailPoet.I18n.t('continue')}
        </Button>
        <Button
          href="#skipStep"
          isDisabled={loading}
          isFullWidth
          onClick={skipStep}
          variant="tertiary"
        >
          {MailPoet.I18n.t('skipStep')}
        </Button>
      </form>
    </>
  );
}

WelcomeWizardSenderStep.displayName = 'WelcomeWizardSenderStep';

export { WelcomeWizardSenderStep };
