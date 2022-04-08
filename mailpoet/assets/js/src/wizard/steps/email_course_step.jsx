import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Button from '../../common/button/button';
import Heading from '../../common/typography/heading/heading';

function WelcomeWizardEmailCourseStep(props) {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardEmailCourseTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardEmailCourseText')}</p>
      <div className="mailpoet-gap" />

      <iframe
        id="mailpoet_form_iframe"
        width="100%"
        scrolling="no"
        frameBorder="0"
        title="Apply to course"
        src="https://www.mailpoet.com?mailpoet_form_iframe=13"
        className="mailpoet-wizard-course-iframe"
        marginWidth="0"
        marginHeight="0"
      />

      <Button isFullWidth onClick={props.next} type="button">
        {MailPoet.I18n.t('continue')}
      </Button>
    </>
  );
}

WelcomeWizardEmailCourseStep.propTypes = {
  next: PropTypes.func.isRequired,
};

export default WelcomeWizardEmailCourseStep;
