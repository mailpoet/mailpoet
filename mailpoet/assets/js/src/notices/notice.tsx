import {
  ReactNode,
  useCallback,
  useEffect,
  useLayoutEffect,
  useRef,
  useState,
} from 'react';
import ReactDOM from 'react-dom';
import { MailPoet } from 'mailpoet';
import { withBoundary } from 'common';

type Props = {
  type: 'success' | 'info' | 'warning' | 'error';
  children: ReactNode;
  scroll?: boolean;
  closable?: boolean;
  renderInPlace?: boolean;
  onDisplay?: () => void;
  onClose?: () => void;
  timeout?: number | false;
};

function Notice({
  onClose,
  onDisplay,
  renderInPlace,
  timeout,
  scroll,
  children,
  closable,
  type,
}: Props) {
  const [hidden, setHidden] = useState(false);
  const elementRef = useRef(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout>>(null);

  const close = useCallback(() => {
    if (onClose) onClose();
    setHidden(true);
  }, [onClose]);

  useEffect(() => {
    if (timeout) {
      timeoutRef.current = setTimeout(close, timeout);
    }
    return () => (timeoutRef.current ? clearTimeout(timeoutRef.current) : null);
  }, [close, timeout]);

  useLayoutEffect(() => {
    if (scroll && elementRef.current) {
      elementRef.current.scrollIntoView(false);
    }
  }, [scroll]);

  useLayoutEffect(() => {
    if (onDisplay) onDisplay();
  }, [onDisplay]);

  if (hidden) return null;

  // inline class is used to prevent moving notice by WP JavaScript (wp-admin/js/common.js) because it can break DOM, and events don't work anymore
  const content = (
    <div
      ref={elementRef}
      className={`notice inline ${type} notice-${type} ${
        closable ? 'is-dismissible' : ''
      }`}
    >
      {children}
      {closable && (
        <button type="button" className="notice-dismiss" onClick={close}>
          <span className="screen-reader-text">
            {MailPoet.I18n.t('dismissNotice')}
          </span>
        </button>
      )}
    </div>
  );

  if (renderInPlace) {
    return content;
  }

  return ReactDOM.createPortal(
    content,
    document.getElementById('mailpoet_notices'),
  );
}

Notice.defaultProps = {
  timeout: 10000,
  scroll: false,
  closable: true,
  renderInPlace: false,
  onDisplay: undefined,
  onClose: undefined,
};
Notice.displayName = 'Notice';
const NoticeWithBoundary = withBoundary(Notice);
export { NoticeWithBoundary as Notice };
