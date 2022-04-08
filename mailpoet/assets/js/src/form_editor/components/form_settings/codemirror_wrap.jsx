import { useCallback, useEffect, useRef } from 'react';
import codemirror from 'codemirror';
import 'codemirror/mode/css/css'; // Side effect
import PropTypes from 'prop-types';

function CodemirrorWrap({ options, value, onChange }) {
  const textArea = useRef(null);
  const editor = useRef(null);

  const changeEvent = useCallback(
    (doc) => {
      onChange(doc.getValue());
    },
    [onChange],
  );

  useEffect(() => {
    editor.current = codemirror.fromTextArea(textArea.current, options);
    editor.current.on('change', changeEvent);
    return () => {
      if (editor.current) {
        editor.current.toTextArea();
      }
    };
  }, [options, changeEvent]);

  useEffect(() => {
    if (editor.current.getValue() !== value) {
      editor.current.off('change', changeEvent);
      editor.current.setValue(value);
      editor.current.on('change', changeEvent);
    }
  }, [value, changeEvent]);

  return (
    <div>
      <textarea
        ref={textArea}
        name="name"
        defaultValue={value}
        autoComplete="off"
      />
    </div>
  );
}

CodemirrorWrap.propTypes = {
  value: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired,
  options: PropTypes.shape({
    lineNumbers: PropTypes.bool,
    tabMode: PropTypes.string,
    matchBrackets: PropTypes.bool,
    theme: PropTypes.string,
    mode: PropTypes.string,
  }),
};

CodemirrorWrap.defaultProps = {
  options: {
    lineNumbers: true,
    tabMode: 'indent',
    matchBrackets: true,
    theme: 'neo',
    mode: 'css',
  },
};

export default CodemirrorWrap;
