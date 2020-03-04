import React from 'react';
import PropTypes from 'prop-types';
import ParagraphEdit from './paragraph_edit.jsx';
import formatLabel from './label_formatter.jsx';
import { inputStylesPropTypes } from './input_styles_settings.jsx';

const TextInputEdit = ({
  label,
  labelWithinInput,
  name,
  mandatory,
  styles,
}) => {
  const id = `${name}_${Math.random().toString(36).substring(2, 15)}`;

  const labelStyles = {
    fontWeight: styles.bold ? 'bold' : 'inherit',
  };

  const inputStyles = {
    borderRadius: styles.borderRadius ? `${styles.borderRadius}px` : 0,
    borderWidth: styles.borderSize ? `${styles.borderSize}px` : '1px',
    borderColor: styles.borderColor || 'initial',
  };

  if (styles.fullWidth) {
    inputStyles.width = '100%';
  }

  if (styles.backgroundColor) {
    inputStyles.backgroundColor = styles.backgroundColor;
  }

  const getTextInput = (placeholder) => (
    <input
      id={id}
      className="mailpoet_text"
      type={name === 'email' ? 'email' : 'text'}
      name={name}
      disabled
      placeholder={placeholder}
      data-automation-id={`editor_${name}_input`}
      style={inputStyles}
    />
  );

  return (
    <ParagraphEdit>
      {!labelWithinInput ? (
        <label className="mailpoet_text_label" data-automation-id={`editor_${name}_label`} htmlFor={id} style={labelStyles}>
          {formatLabel({ label, mandatory })}
        </label>
      ) : null}
      {getTextInput(labelWithinInput ? formatLabel({ label, mandatory }) : '')}
    </ParagraphEdit>
  );
};

TextInputEdit.propTypes = {
  label: PropTypes.string.isRequired,
  labelWithinInput: PropTypes.bool.isRequired,
  name: PropTypes.string.isRequired,
  mandatory: PropTypes.bool.isRequired,
  styles: inputStylesPropTypes.isRequired,
};

export default TextInputEdit;
