import { __, _x } from '@wordpress/i18n';
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
        {__('Start by configuring your sender information', 'mailpoet')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>
        <b>{__('Default sender', 'mailpoet')}</b>
        <br />
        {__(
          'Enter details of the person or brand your subscribers expect to receive emails from',
          'mailpoet',
        )}
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
              {_x('From Name', 'A form field label', 'mailpoet')}
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
              {_x('From Address', 'A form field label', 'mailpoet')}
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

        <Button
          className="mailpoet-wizard-continue-button"
          isFullWidth
          type="submit"
          withSpinner={loading}
        >
          {_x('Continue', 'A label on a button', 'mailpoet')}
        </Button>
        <Button
          href="#skipStep"
          isDisabled={loading}
          isFullWidth
          onClick={skipStep}
          variant="tertiary"
        >
          {_x('Skip this step', 'A label on a skip button', 'mailpoet')}
        </Button>
      </form>
    </>
  );
}

WelcomeWizardSenderStep.displayName = 'WelcomeWizardSenderStep';

export { WelcomeWizardSenderStep };
