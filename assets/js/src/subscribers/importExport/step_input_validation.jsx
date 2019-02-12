import React from 'react';
import PropTypes from 'prop-types';

const StepInputValidation = props => (
  <>
    <button
      className="button-primary wysija button"
      type="button"
      onClick={() => {
        console.log('previous step');
        props.navigate('step_method_selection', { trigger: true });
      }}
    >
      Previous step
    </button>
      &nbsp;&nbsp;
    <button
      type="button"
      className="button-primary wysija button-disabled"
      onClick={() => {
        // TODO only if all checkboxes are checked
        console.log('next step');
        props.navigate('step_data_manipulation', { trigger: true });
      }}
    >
       Next step
    </button>
  </>
);

StepInputValidation.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepInputValidation;
