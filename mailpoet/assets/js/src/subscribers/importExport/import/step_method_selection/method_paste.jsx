import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { Textarea } from 'common/form/textarea/textarea';
import { PreviousNextStepButtons } from '../previous_next_step_buttons.jsx';

const kbLink =
  'https://kb.mailpoet.com/article/126-importing-subscribers-with-csv-files';

const placeholder =
  'Email, First Name, Last Name\njohn@doe.com, John, Doe\nmary@smith.com, Mary, Smith\njohnny@walker.com, Johnny, Walker';

function MethodPaste({ onValueChange, canFinish, onFinish, data, onPrevious }) {
  const onChange = (e) => {
    onValueChange(e.target.value);
  };

  return (
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="paste_input">{MailPoet.I18n.t('pasteLabel')}</label>
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
        <Textarea
          id="paste_input"
          rows="15"
          placeholder={placeholder}
          isCode
          onChange={onChange}
          defaultValue={data}
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

MethodPaste.propTypes = {
  onFinish: PropTypes.func,
  onPrevious: PropTypes.func,
  canFinish: PropTypes.bool.isRequired,
  onValueChange: PropTypes.func.isRequired,
  data: PropTypes.string,
};

MethodPaste.defaultProps = {
  onFinish: () => {},
  onPrevious: () => {},
  data: '',
};
MethodPaste.displayName = 'MethodPaste';
export { MethodPaste };
