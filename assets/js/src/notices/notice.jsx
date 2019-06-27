import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

const Notice = (props) => {
  const [hidden, setHidden] = React.useState(false);
  const elementRef = React.useRef(null);
  const timeoutRef = React.useRef(null);

  React.useEffect(() => {
    if (props.timeout) {
      timeoutRef.current = setTimeout(() => setHidden(true), props.timeout);
    }
    return () => (timeoutRef.current ? clearTimeout(timeoutRef.current) : null);
  }, [props.timeout]);

  React.useLayoutEffect(() => {
    if (props.scroll && elementRef.current) {
      elementRef.current.scrollIntoView(false);
    }
  }, [props.scroll]);

  if (hidden) return null;
  return ReactDOM.createPortal(
    <div ref={elementRef} className={`mailpoet_${props.type}_notice`}>{props.children}</div>,
    document.getElementById('mailpoet_notices')
  );
};
Notice.propTypes = {
  type: PropTypes.oneOf(['success', 'info', 'warning', 'error']).isRequired,
  scroll: PropTypes.bool,
  timeout: PropTypes.oneOfType([PropTypes.number, PropTypes.oneOf([false])]),
  children: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.element,
    PropTypes.arrayOf(PropTypes.element),
  ]).isRequired,
};
Notice.defaultProps = {
  timeout: 10000,
  scroll: false,
};

export default Notice;
