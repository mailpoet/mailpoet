import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

function StepDataManipulation({ history, stepMethodSelection }) {
  useEffect(
    () => {
      if (typeof (stepMethodSelection) === 'undefined') {
        history.replace('step_method_selection');
      }
    },
    [stepMethodSelection],
  );

  return (
    <>
      
    </>
  );
}

StepDataManipulation.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
    replace: PropTypes.func.isRequired,
  }).isRequired,
  stepMethodSelection: PropTypes.shape({
    duplicate: PropTypes.arrayOf(PropTypes.string),
    header: PropTypes.arrayOf(PropTypes.string),
    invalid: PropTypes.arrayOf(PropTypes.string),
    role: PropTypes.arrayOf(PropTypes.string),
    subscribersCount: PropTypes.number,
    subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)),
  }),
};

StepDataManipulation.defaultProps = {
  stepMethodSelection: undefined,
};

export default withRouter(StepDataManipulation);
