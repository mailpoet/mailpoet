import React, { useEffect, useRef } from 'react';
import codemirror from 'codemirror';
import 'codemirror/mode/css/css'; // Side effect
import PropTypes from 'prop-types';

const CodemirrorWrap = ({ options, value, onChange }) => {
  const textArea = useRef(null);
  const editor = useRef(null);

  useEffect(() => {
    editor.current = codemirror.fromTextArea(textArea.current, options);
    editor.current.on('change', (doc) => onChange(doc.getValue()));
    return () => {
      if (editor.current) {
        editor.current.toTextArea();
      }
    };
  }, [options, onChange]);

  return (
    <textarea
      ref={textArea}
      name="name"
      defaultValue={value}
      autoComplete="off"
    />
  );
};

CodemirrorWrap.propTypes = {
  value: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired,
  options: PropTypes.shape({
    lineNumbers: PropTypes.bool.isRequired,
    tabMode: PropTypes.string.isRequired,
    matchBrackets: PropTypes.bool.isRequired,
    theme: PropTypes.string.isRequired,
    mode: PropTypes.string.isRequired,
  }).isRequired,
};

export default CodemirrorWrap;
