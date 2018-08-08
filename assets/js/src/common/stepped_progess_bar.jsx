import React from 'react';

const SteppedProgressBar = (props) => {
  if (props.step > props.steps_count) {
    return null;
  }
  return (
    <div className="mailpoet_stepped_progress_bar">
      {
        [...Array(props.steps_count).keys()].map(step => (
          <div
            className={`mailpoet_stepped_progress_bar_step ${(step < props.step ? 'active' : '')}`}
            key={`step_${step}`}
            style={{ width: `${Math.floor(100 / props.steps_count)}%` }}
          />
        ))
      }
    </div>
  );
};

SteppedProgressBar.propTypes = {
  steps_count: React.PropTypes.number.isRequired,
  step: React.PropTypes.number.isRequired,
};

module.exports = SteppedProgressBar;
