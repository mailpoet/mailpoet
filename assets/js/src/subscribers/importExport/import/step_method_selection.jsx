import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Papa from 'papaparse';
import PreviousNextStepButtons from './previous_next_step_buttons.jsx';
import SelectMethod from './step_method_selection/select_import_method.jsx';
import MethodPaste from './step_method_selection/method_paste.jsx';
import MethodUpload from './step_method_selection/method_upload.jsx';
import MethodMailChimp from './step_method_selection/method_mailchimp.jsx';
import sanitizeCSVData from './sanitize_csv_data.jsx';

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
  const [canGoNext, setCanGoNext] = useState(false);
  const [method, setMethod] = useState(undefined);
  const [csvData, setCsvData] = useState('');

  const setInputValid = () => {
    setCanGoNext(true);
  };

  const setInputInValid = () => {
    setCanGoNext(false);
  };

  function papaParserConfig(isFile) {
    return {
      skipEmptyLines: true,
      error() {
        MailPoet.Notice.hide();
        MailPoet.Notice.error(MailPoet.I18n.t('dataProcessingError'));
      },
      complete(CSV) {
        const sanitizedData = sanitizeCSVData(CSV.data);
        if (sanitizedData) {
          // since we assume that the header line is always present, we need
          // to detect the header by checking if it contains a valid e-mail address
          window.importData.step_method_selection = sanitizedData;
          MailPoet.trackEvent('Subscribers import started', {
            source: isFile ? 'file upload' : 'pasted data',
            'MailPoet Free version': window.mailpoet_version,
          });
          navigate(
            getNextStepLink(window.importData.step_method_selection),
            { trigger: true }
          );
        } else {
          MailPoet.Modal.loading(false);
          let errorNotice = MailPoet.I18n.t('noValidRecords');
          errorNotice = errorNotice.replace('[link]', MailPoet.I18n.t('csvKBLink'));
          errorNotice = errorNotice.replace('[/link]', '</a>');
          MailPoet.Notice.error(errorNotice);
        }
      },
    };
  }

  const process = () => {
    const pasteSize = encodeURI(csvData).split(/%..|./).length - 1;
    MailPoet.Notice.hide();
    // get an approximate size of textarea paste in bytes
    if (pasteSize > window.maxPostSizeBytes) {
      MailPoet.Notice.error(MailPoet.I18n.t('maxPostSizeNotice'));
      return;
    }
    // delay loading indicator for 10ms or else it's just too fast :)
    MailPoet.Modal.loading(true);
    Papa.parse(csvData, papaParserConfig(false));
  };

  const showNextButton = () => {
    if (method) {
      return (
        <PreviousNextStepButtons
          canGoNext={canGoNext}
          hidePrevious
          onNextAction={process}
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
            onValueChange={setCsvData}
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
