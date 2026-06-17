import { useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

const defaultOptions = {
  lineNumbers: true,
  tabMode: 'indent',
  matchBrackets: true,
};

function CodemirrorWrap({ value, onChange, options = defaultOptions }) {
  const codeMirrorRef = useRef(null);
  const onChangeRef = useRef(onChange);
  const textareaRef = useRef(null);

  useEffect(() => {
    onChangeRef.current = onChange;
  }, [onChange]);

  useEffect(() => {
    const codeEditor = typeof window !== 'undefined' && window.wp?.codeEditor;
    const textarea = textareaRef.current;
    if (!codeEditor?.initialize || !textarea) {
      return undefined;
    }

    const editor = codeEditor.initialize(textarea, {
      ...codeEditor.defaultSettings,
      codemirror: {
        ...codeEditor.defaultSettings?.codemirror,
        indentWithTabs: options.tabMode === 'indent',
        lineNumbers: options.lineNumbers,
        matchBrackets: options.matchBrackets,
      },
    });
    const codeMirror = editor?.codemirror;
    if (!codeMirror) {
      return undefined;
    }

    const handleChange = (currentCodeMirror) => {
      onChangeRef.current(currentCodeMirror.getValue());
    };

    codeMirrorRef.current = codeMirror;
    codeMirror.on('change', handleChange);

    return () => {
      codeMirror.off?.('change', handleChange);
      codeMirror.toTextArea?.();
      codeMirrorRef.current = null;
    };
  }, [options.lineNumbers, options.matchBrackets, options.tabMode]);

  useEffect(() => {
    const codeMirror = codeMirrorRef.current;
    if (codeMirror) {
      if (codeMirror.getValue() !== value) {
        codeMirror.setValue(value);
      }
      return;
    }

    const textarea = textareaRef.current;
    if (textarea && textarea.value !== value) {
      textarea.value = value;
    }
  }, [value]);

  return (
    <textarea
      ref={textareaRef}
      className="mailpoet-code-editor-fallback"
      defaultValue={value}
      onInput={(event) => {
        if (!codeMirrorRef.current) {
          onChange(event.target.value);
        }
      }}
      rows={12}
    />
  );
}

CodemirrorWrap.propTypes = {
  value: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired,
  options: PropTypes.shape({
    lineNumbers: PropTypes.bool,
    tabMode: PropTypes.string,
    matchBrackets: PropTypes.bool,
  }),
};

export { CodemirrorWrap };
