import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { Input } from 'common/form/input/input';
import { PreviousNextStepButtons } from '../previous_next_step_buttons.jsx';

const kbLink =
  'https://kb.mailpoet.com/article/126-importing-subscribers-with-csv-files';

function MethodUpload({ onValueChange, canFinish, onFinish, onPrevious }) {
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
      <div className="mailpoet-settings-label">
        <label htmlFor="file_local">{MailPoet.I18n.t('methodUpload')}</label>
        <p className="description">
          {ReactStringReplace(
            MailPoet.I18n.t('pasteDescription'),
            /\[link\](.*?)\[\/link\]/,
            (match) => (
              <a
                className="mailpoet-link"
                href={`${kbLink}`}
                data-beacon-article="57ce079f903360649f6e56fc"
                key="kb-link"
                target="_blank"
                rel="noopener noreferrer"
              >
                {match}
              </a>
            ),
          )}
        </p>
      </div>
      <div className="mailpoet-settings-inputs">
        <Input
          type="file"
          id="file_local"
          accept=".csv"
          data-automation-id="import-file-upload-input"
          onChange={onChange}
        />
      </div>
      <PreviousNextStepButtons
        canGoNext={canFinish}
        onPreviousAction={onPrevious}
        onNextAction={onFinish}
      />
    </>
  );
}

MethodUpload.propTypes = {
  canFinish: PropTypes.bool.isRequired,
  onFinish: PropTypes.func,
  onPrevious: PropTypes.func,
  onValueChange: PropTypes.func.isRequired,
};

MethodUpload.defaultProps = {
  onFinish: () => {},
  onPrevious: () => {},
};
MethodUpload.displayName = 'MethodUpload';
export { MethodUpload };
