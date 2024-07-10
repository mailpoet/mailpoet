import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { ErrorBoundary } from 'common';
import { SelectImportMethod } from './step-method-selection/select-import-method.jsx';
import { MethodPaste } from './step-method-selection/method-paste.jsx';
import { MethodUpload } from './step-method-selection/method-upload.jsx';
import { MethodMailChimp } from './step-method-selection/method-mailchimp.jsx';
import { processCsv } from './step-method-selection/process-csv.jsx';
import { PreviousNextStepButtons } from './previous-next-step-buttons';

const getNextStepLink = (importData, subscribersLimitForValidation, method) => {
  if (importData === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount === undefined) {
    return 'step_data_manipulation';
  }
  if (importData.subscribersCount < subscribersLimitForValidation) {
    return 'step_data_manipulation';
  }
  if (method === 'mailchimp-method') {
    return 'step_data_manipulation';
  }
  return 'step_input_validation';
};

export function StepMethodSelection({
  setStepMethodSelectionData,
  subscribersLimitForValidation,
}) {
  const navigate = useNavigate();
  const [method, setMethod] = useState(undefined);
  const [pastedCsvData, setPastedCsvData] = useState('');
  const [file, setFile] = useState(undefined);

  const finish = (parsedData) => {
    setStepMethodSelectionData(parsedData);
    navigate(
      getNextStepLink(parsedData, subscribersLimitForValidation, method),
    );
  };

  const previousStep = () => {
    navigate('/step_clean_list');
  };

  const processLocal = () => {
    const data = method === 'paste-method' ? pastedCsvData : file;
    processCsv(data, (sanitizedData) => {
      MailPoet.trackEvent('Subscribers import started', {
        source: method === 'file-method' ? 'file upload' : 'pasted data',
      });
      finish(sanitizedData);
    });
  };

  return (
    <div className="mailpoet-settings-grid">
      <ErrorBoundary>
        <SelectImportMethod activeMethod={method} onMethodChange={setMethod} />
      </ErrorBoundary>
      {method === 'paste-method' && (
        <ErrorBoundary>
          <MethodPaste
            onPrevious={previousStep}
            onValueChange={setPastedCsvData}
            onFinish={processLocal}
            canFinish={!!pastedCsvData.trim()}
            data={pastedCsvData}
          />
        </ErrorBoundary>
      )}
      {method === 'file-method' && (
        <ErrorBoundary>
          <MethodUpload
            onPrevious={previousStep}
            onValueChange={setFile}
            onFinish={processLocal}
            canFinish={!!file}
            data={file}
          />
        </ErrorBoundary>
      )}
      {method === 'mailchimp-method' && (
        <ErrorBoundary>
          <MethodMailChimp
            onPrevious={previousStep}
            onFinish={(data) => {
              MailPoet.trackEvent('Subscribers import started', {
                source: 'MailChimp',
              });
              finish(data);
            }}
          />
        </ErrorBoundary>
      )}
      {method === undefined && (
        <ErrorBoundary>
          <PreviousNextStepButtons
            canGoNext={false}
            onPreviousAction={previousStep}
          />
        </ErrorBoundary>
      )}
    </div>
  );
}

StepMethodSelection.propTypes = {
  setStepMethodSelectionData: PropTypes.func.isRequired,
  subscribersLimitForValidation: PropTypes.number.isRequired,
};
StepMethodSelection.diplayName = 'StepMethodSelection';
