import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Button from '../../common/button/button';
import Heading from '../../common/typography/heading/heading';

function WelcomeWizardMigratedUserStep(props) {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardLetsStartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardSenderMigratedUserText')}</p>
      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button isFullWidth onClick={props.next} type="button">
        {MailPoet.I18n.t('continue')}
      </Button>
    </>
  );
}

WelcomeWizardMigratedUserStep.propTypes = {
  next: PropTypes.func.isRequired,
};

export default WelcomeWizardMigratedUserStep;
