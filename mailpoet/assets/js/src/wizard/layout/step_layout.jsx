import PropTypes from 'prop-types';

import { WelcomeWizardStepLayoutBody } from './step_layout_body.jsx';

function WelcomeWizardStepLayout(props) {
  return (
    <WelcomeWizardStepLayoutBody illustrationUrl={props.illustrationUrl}>
      {props.children}
    </WelcomeWizardStepLayoutBody>
  );
}

WelcomeWizardStepLayout.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export { WelcomeWizardStepLayout };
