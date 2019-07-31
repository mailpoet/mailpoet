import PropTypes from 'prop-types';
import React from 'react';

const SteppedProgressBar = (props) => {
  if (props.step > props.steps_count) {
    return null;
  }
  return (
    <div className="mailpoet_stepped_progress_bar">
      {
        [...Array(props.steps_count).keys()].map((step) => (
          <div
            className={`
              mailpoet_stepped_progress_bar_step ${(step < props.step ? 'active' : '')} ${(step === (props.step - 1) ? 'current' : '')}
             `}
            key={`step_${step}`}
          />
        ))
      }
    </div>
  );
};

SteppedProgressBar.propTypes = {
  steps_count: PropTypes.number.isRequired,
  step: PropTypes.number.isRequired,
};

export default SteppedProgressBar;
