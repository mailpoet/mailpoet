import React from 'react';
import PropTypes from 'prop-types';
import ParagraphEdit from './paragraph_edit.jsx';
import formatLabel from './label_formatter.jsx';

const TextInputEdit = ({
  label,
  labelWithinInput,
  name,
  mandatory,
}) => {
  const id = `${name}_${Math.random().toString(36).substring(2, 15)}`;

  const getTextInput = (placeholder) => (
    <input
      id={id}
      className="mailpoet_text"
      type={name === 'email' ? 'email' : 'text'}
      name={name}
      disabled
      placeholder={placeholder}
      data-automation-id={`editor_${name}_input`}
    />
  );

  return (
    <ParagraphEdit>
      {!labelWithinInput ? (
        <label className="mailpoet_text_label" data-automation-id={`editor_${name}_label`} htmlFor={id}>
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
};

export default TextInputEdit;
