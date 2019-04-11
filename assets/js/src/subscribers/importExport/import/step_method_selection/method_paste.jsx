import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const kbLink = 'http://docs.mailpoet.com/article/126-importing-subscribers-with-csv-files'

const placeholder = 'Email, First Name, Last Name\njohn@doe.com, John, Doe\nmary@smith.com, Mary, Smith\njohnny@walker.com, Johnny, Walker';

const MethodPaste = ({ setInputValid, setInputInvalid }) => {
  const onChange = (e) => {
    if (e.target.value) {
      setInputValid();
    } else {
      setInputInvalid();
    }
  };

  return (
    <>
      <label htmlFor="paste_input" className="import_method_paste">
        <div className="import_paste_texts">
          <span className="import_heading">{MailPoet.I18n.t('pasteLabel')}</span>
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
        <textarea
          id="paste_input"
          rows="15"
          placeholder={placeholder}
          className="regular-text code"
          onChange={onChange}
        />
      </label>
    </>
  );
};

MethodPaste.propTypes = {
  setInputValid: PropTypes.func,
  setInputInvalid: PropTypes.func,
};

MethodPaste.defaultProps = {
  setInputValid: () => {},
  setInputInvalid: () => {},
};

export default MethodPaste;
