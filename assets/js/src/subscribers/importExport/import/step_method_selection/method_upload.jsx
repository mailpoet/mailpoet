import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import PreviousNextStepButtons from '../previous_next_step_buttons.jsx';

const kbLink = 'http://docs.mailpoet.com/article/126-importing-subscribers-with-csv-files'

const MethodUpload = ({ onValueChange, canFinish, onFinish }) => {
  const onChange = (e) => {
    const ext = e.target.value.match(/[^.]+$/);
    MailPoet.Notice.hide();
    if (ext === null || ext[0].toLowerCase() !== 'csv') {
      MailPoet.Notice.error(MailPoet.I18n.t('wrongFileFormat'));
      onValueChange('');
    } else {
      onValueChange(e.target.files[0]);
    }
  };

  return (
    <>
      <div>
        <label htmlFor="paste_input" className="import_method_paste">
          <div className="import_paste_texts">
            <span className="import_heading">{MailPoet.I18n.t('methodUpload')}</span>
            <p className="description">
              {ReactStringReplace(
                MailPoet.I18n.t('pasteDescription'),
                /\[link\](.*?)\[\/link\]/,
                match => (
                  <a
                    href={`${kbLink}`}
                    key="kb-link"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    { match }
                  </a>
                )
              )}
            </p>
          </div>
          <input
            type="file"
            id="file_local"
            accept=".csv"
            data-automation-id="import-file-upload-input"
            onChange={onChange}
          />
        </label>
      </div>
      <PreviousNextStepButtons
        canGoNext={canFinish}
        hidePrevious
        onNextAction={onFinish}
      />
    </>
  );
};

MethodUpload.propTypes = {
  canFinish: PropTypes.bool.isRequired,
  onFinish: PropTypes.func,
  onValueChange: PropTypes.func.isRequired,
};

MethodUpload.defaultProps = {
  onFinish: () => {},
};

export default MethodUpload;
