import { useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

import { ParagraphEdit } from './paragraph_edit.jsx';
import { formatLabel } from './label_formatter.jsx';
import { inputStylesPropTypes } from './input_styles_settings';
import { convertAlignmentToMargin } from './convert_alignment_to_margin';

function TextInputEdit({
  label,
  labelWithinInput,
  name,
  mandatory,
  styles,
  className,
}) {
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const input = useRef(null);
  const id = `${name}_${Math.random().toString(36).substring(2, 15)}`;
  const [value, setValue] = useState('');

  const labelStyles = !styles.inheritFromTheme
    ? {
        fontWeight: styles.bold ? 'bold' : 'inherit',
      }
    : {};

  const inputStyles = !styles.inheritFromTheme
    ? {
        borderRadius: styles.borderRadius ? `${styles.borderRadius}px` : 0,
        borderWidth:
          styles.borderSize !== undefined ? `${styles.borderSize}px` : '1px',
        borderColor: styles.borderColor || 'initial',
        borderStyle: 'solid',
      }
    : {};

  if (settings.inputPadding !== undefined) {
    inputStyles.padding = settings.inputPadding;
  }

  if (settings.inputPadding !== undefined) {
    inputStyles.padding = settings.inputPadding;
  }

  if (settings.alignment !== undefined) {
    inputStyles.textAlign = settings.alignment;
    inputStyles.margin = convertAlignmentToMargin(inputStyles.textAlign);
  }

  if (styles.fullWidth) {
    inputStyles.width = '100%';
  }

  if (styles.backgroundColor && !styles.inheritFromTheme) {
    inputStyles.backgroundColor = styles.backgroundColor;
  }

  const placeholderStyle = {};

  if (styles.fontColor && !styles.inheritFromTheme) {
    inputStyles.color = styles.fontColor;
    if (labelWithinInput) {
      placeholderStyle.color = styles.fontColor;
    }
  }

  const getTextInput = (placeholder) => {
    let style = `#${id}::placeholder {`;
    if (placeholderStyle.color !== undefined) {
      style += `color: ${placeholderStyle.color};`;
    }
    if (settings.fontFamily) {
      style += `font-family: ${settings.fontFamily};`;
    }
    style += '}';
    return (
      <>
        <style>{style}</style>
        <input
          id={id}
          ref={input}
          className="mailpoet_text"
          type="text"
          name={name}
          value={value}
          onChange={() => setValue('')}
          placeholder={placeholder}
          data-automation-id={`editor_${name}_input`}
          style={inputStyles}
          autoComplete="off"
        />
      </>
    );
  };

  return (
    <ParagraphEdit className={className}>
      {!labelWithinInput ? (
        <label
          className="mailpoet_text_label"
          data-automation-id={`editor_${name}_label`}
          htmlFor={id}
          style={labelStyles}
        >
          {formatLabel({ label, mandatory })}
        </label>
      ) : null}
      {getTextInput(labelWithinInput ? formatLabel({ label, mandatory }) : '')}
    </ParagraphEdit>
  );
}

TextInputEdit.propTypes = {
  label: PropTypes.string.isRequired,
  labelWithinInput: PropTypes.bool.isRequired,
  name: PropTypes.string.isRequired,
  mandatory: PropTypes.bool.isRequired,
  className: PropTypes.string,
  styles: inputStylesPropTypes.isRequired,
};

TextInputEdit.defaultProps = {
  className: '',
};

export { TextInputEdit };
