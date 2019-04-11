import React, { useState } from 'react';
import PropTypes from 'prop-types';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';
import SelectMethod from './step_method_selection/select_import_method.jsx';
import MethodPaste from './step_method_selection/method_paste.jsx';
import MethodUpload from './step_method_selection/method_upload.jsx';
import MethodMailChimp from './step_method_selection/method_mailchimp.jsx';

function StepMethodSelection({
  navigate,
}) {
  const [canGoNext, setCanGoNext] = useState(false);
  const [method, setMethod] = useState(undefined);

  const setInputValid = () => {
    setCanGoNext(true);
  };

  const setInputInValid = () => {
    setCanGoNext(false);
  };

  const showNextButton = () => {
    if (method) {
      return (
        <PreviousNextStepButtons
          canGoNext={canGoNext}
          hidePrevious
          onNextAction={() => navigate('step_data_manipulation', { trigger: true })}
        />
      );
    }
    return null;
  };

  return (
    <>
      <SelectMethod
        activeMethod={method}
        onMethodChange={setMethod}
      />
      { method === 'paste-method'
        ? (
          <MethodPaste
            setInputValid={setInputValid}
            setInputInvalid={setInputInValid}
          />
        ) : null
      }
      { method === 'csv-method'
        ? (
          <MethodUpload
            setInputValid={setInputValid}
          />
        ) : null
      }
      { method === 'mailchimp-method'
        ? (
          <MethodMailChimp
            setInputValid={setInputValid}
          />
        ) : null
      }
      {showNextButton()}
    </>
  );
}

StepMethodSelection.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepMethodSelection;
