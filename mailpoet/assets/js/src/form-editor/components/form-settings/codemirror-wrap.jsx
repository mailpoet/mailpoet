import { useCallback } from 'react';
import CodeMirror from '@uiw/react-codemirror';
import PropTypes from 'prop-types';

function CodemirrorWrap({
  value,
  onChange,
  options = {
    lineNumbers: true,
    tabMode: 'indent',
    matchBrackets: true,
  },
}) {
  const changeEvent = useCallback(
    (currentValue) => {
      onChange(currentValue);
    },
    [onChange],
  );

  return (
    <CodeMirror
      value={value}
      onChange={changeEvent}
      basicSetup={{
        lineNumbers: options.lineNumbers,
        indentWithTabs: options.tabMode === 'indent',
        bracketMatching: options.matchBrackets,
      }}
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
