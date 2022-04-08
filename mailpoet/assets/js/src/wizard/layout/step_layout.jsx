import PropTypes from 'prop-types';

import WelcomeWizardStepLayoutBody from './step_layout_body.jsx';

function WelcomeWizardStepLayout(props) {
  return (
    <>
      <div className="mailpoet-wizard-logo">
        <img
          src={window.mailpoet_logo_url}
          width="160"
          height="50"
          alt="MailPoet logo"
        />
      </div>
      <WelcomeWizardStepLayoutBody illustrationUrl={props.illustrationUrl}>
        {props.children}
      </WelcomeWizardStepLayoutBody>
    </>
  );
}

WelcomeWizardStepLayout.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default WelcomeWizardStepLayout;
