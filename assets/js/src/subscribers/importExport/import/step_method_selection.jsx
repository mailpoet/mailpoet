import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import SelectMethod from './step_method_selection/select_import_method.jsx';
import MethodPaste from './step_method_selection/method_paste.jsx';
import MethodUpload from './step_method_selection/method_upload.jsx';
import MethodMailChimp from './step_method_selection/method_mailchimp.jsx';
import processCsv from './step_method_selection/process_csv.jsx';

const SUBSCRIBERS_LIMIT_FOR_VALIDATION = 500;

const getNextStepLink = (importData) => {
  if (importData === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount < SUBSCRIBERS_LIMIT_FOR_VALIDATION) {
    return 'step_data_manipulation';
  }
  return 'step_input_validation';
};

function StepMethodSelection({
  navigate,
}) {
  const [method, setMethod] = useState(undefined);
  const [csvData, setCsvData] = useState('');

  const methodChanged = (newMethod) => {
    setMethod(newMethod);
    setCsvData('');
  };

  const navigateToNextStep = () => {
    navigate(
      getNextStepLink(window.importData.step_method_selection),
      { trigger: true }
    );
  };

  const processLocal = () => {
    processCsv(csvData, (sanitizedData) => {
      window.importData.step_method_selection = sanitizedData;
      MailPoet.trackEvent('Subscribers import started', {
        source: method === 'file-method' ? 'file upload' : 'pasted data',
        'MailPoet Free version': window.mailpoet_version,
      });
      navigateToNextStep();
    });
  };

  return (
    <>
      <SelectMethod
        activeMethod={method}
        onMethodChange={methodChanged}
      />
      { method === 'paste-method'
        ? (
          <MethodPaste
            onValueChange={setCsvData}
            onFinish={processLocal}
            canFinish={!!csvData.trim()}
          />
        ) : null
      }
      { method === 'file-method'
        ? (
          <MethodUpload
            onValueChange={setCsvData}
            onFinish={processLocal}
            canFinish={!!csvData}
          />
        ) : null
      }
      { method === 'mailchimp-method'
        ? (
          <MethodMailChimp
            onFinish={navigateToNextStep}
          />
        ) : null
      }
    </>
  );
}

StepMethodSelection.propTypes = {
  navigate: PropTypes.func.isRequired,
};

export default StepMethodSelection;
