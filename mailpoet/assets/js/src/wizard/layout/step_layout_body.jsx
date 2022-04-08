import PropTypes from 'prop-types';

function WelcomeWizardStepLayoutBody(props) {
  return (
    <div className="mailpoet-wizard-step">
      <div className="mailpoet-wizard-step-illustration">
        <img src={props.illustrationUrl} width="500" alt="" />
      </div>
      <div className="mailpoet-wizard-step-content">{props.children}</div>
    </div>
  );
}

WelcomeWizardStepLayoutBody.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default WelcomeWizardStepLayoutBody;
