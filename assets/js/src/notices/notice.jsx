import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const Notice = (props) => {
  const [hidden, setHidden] = React.useState(false);
  const elementRef = React.useRef(null);
  const timeoutRef = React.useRef(null);

  const { onClose, onDisplay } = props;

  const close = React.useCallback(() => {
    if (onClose) onClose();
    setHidden(true);
  }, [onClose]);

  React.useEffect(() => {
    if (props.timeout) {
      timeoutRef.current = setTimeout(close, props.timeout);
    }
    return () => (timeoutRef.current ? clearTimeout(timeoutRef.current) : null);
  }, [close, props.timeout]);

  React.useLayoutEffect(() => {
    if (props.scroll && elementRef.current) {
      elementRef.current.scrollIntoView(false);
    }
  }, [props.scroll]);

  React.useLayoutEffect(() => {
    if (onDisplay) onDisplay();
  }, [onDisplay]);

  if (hidden) return null;
  return ReactDOM.createPortal(
    <div ref={elementRef} className={`mailpoet_base_notice mailpoet_${props.type}_notice`}>
      {props.children}
      {props.closable && (
        <button type="button" className="notice-dismiss" onClick={close}>
          <span className="screen-reader-text">{MailPoet.I18n.t('dismissNotice')}</span>
        </button>
      )}
    </div>,
    document.getElementById('mailpoet_notices')
  );
};
Notice.propTypes = {
  type: PropTypes.oneOf(['success', 'info', 'warning', 'error']).isRequired,
  scroll: PropTypes.bool,
  closable: PropTypes.bool,
  onDisplay: PropTypes.func,
  onClose: PropTypes.func,
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
  closable: true,
  onDisplay: undefined,
  onClose: undefined,
};

export default Notice;
