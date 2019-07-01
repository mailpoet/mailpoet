import React, { useState } from 'react';
import { withRouter } from 'react-router-dom';
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
  history,
}) {
  const [method, setMethod] = useState(undefined);
  const [pastedCsvData, setPastedCsvData] = useState('');
  const [file, setFile] = useState(undefined);

  const finish = (parsedData) => {
    window.importData.step_method_selection = parsedData;
    setStepMethodSelection(parsedData);
    history.push(getNextStepLink(parsedData));
  };

  const processLocal = () => {
    const data = method === 'paste-method' ? pastedCsvData : file;
    processCsv(data, (sanitizedData) => {
      MailPoet.trackEvent('Subscribers import started', {
        source: method === 'file-method' ? 'file upload' : 'pasted data',
        'MailPoet Free version': window.mailpoet_version,
      });
      finish(sanitizedData);
    });
  };

  return (
    <div className="mailpoet_method_selection_step">
      <SelectMethod
        activeMethod={method}
        onMethodChange={setMethod}
      />
      { method === 'paste-method'
        ? (
          <MethodPaste
            onValueChange={setPastedCsvData}
            onFinish={processLocal}
            canFinish={!!pastedCsvData.trim()}
            data={pastedCsvData}
          />
        ) : null
      }
      { method === 'file-method'
        ? (
          <MethodUpload
            onValueChange={setFile}
            onFinish={processLocal}
            canFinish={!!file}
            data={file}
          />
        ) : null
      }
      { method === 'mailchimp-method'
        ? (
          <MethodMailChimp
            onFinish={(data) => {
              MailPoet.trackEvent('Subscribers import started', {
                source: 'MailChimp',
                'MailPoet Free version': window.mailpoet_version,
              });
              finish(data);
            }}
          />
        ) : null
      }
    </div>
  );
}

StepMethodSelection.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(StepMethodSelection);
