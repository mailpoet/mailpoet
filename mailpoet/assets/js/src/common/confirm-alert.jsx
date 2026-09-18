import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import ReactDOMServer from 'react-dom/server';

import { MailPoet } from 'mailpoet';

function resolveReturnFocusElement(returnFocus) {
  if (!returnFocus) {
    return null;
  }
  const element =
    typeof returnFocus === 'function'
      ? returnFocus()
      : document.querySelector(returnFocus);
  return element && document.contains(element) ? element : null;
}

function focusReturnTarget(returnFocus) {
  // Deferred to the next tick so React has committed the re-render that
  // followed onConfirm settling (e.g. re-enabling a disabled field) before
  // we check focusability and try to focus it.
  setTimeout(() => {
    const element = resolveReturnFocusElement(returnFocus);
    if (element && !element.disabled) {
      element.focus();
    }
  }, 0);
}

function ConfirmAlert({
  message,
  onConfirm,
  returnFocus,
  title = __('Confirm to proceed', 'mailpoet'),
  cancelLabel = __('Cancel', 'mailpoet'),
  confirmLabel = __('Confirm', 'mailpoet'),
}) {
  MailPoet.Modal.popup({
    title,
    // Cancel is destructive-safe: these confirmations guard trash/delete actions
    initialFocus: '#mailpoet_alert_cancel',
    template: ReactDOMServer.renderToString(
      <>
        <p>{message}</p>
        <button
          id="mailpoet_alert_cancel"
          className="button button-secondary"
          type="button"
        >
          {cancelLabel}
        </button>
        <button
          id="mailpoet_alert_confirm"
          className="button button-primary"
          type="button"
        >
          {confirmLabel}
        </button>
      </>,
    ),
    onInit: () => {
      document
        .getElementById('mailpoet_alert_confirm')
        .addEventListener('click', () => {
          MailPoet.Modal.close();
          const result = onConfirm();
          if (result && typeof result.then === 'function') {
            const onSettled = () => focusReturnTarget(returnFocus);
            result.then(onSettled, onSettled);
          } else {
            focusReturnTarget(returnFocus);
          }
        });

      document
        .getElementById('mailpoet_alert_cancel')
        .addEventListener('click', () => MailPoet.Modal.close());
    },
  });
  return null;
}

ConfirmAlert.propTypes = {
  title: PropTypes.string,
  message: PropTypes.string.isRequired,
  cancelLabel: PropTypes.string,
  confirmLabel: PropTypes.string,
  onConfirm: PropTypes.func.isRequired,
  returnFocus: PropTypes.oneOfType([PropTypes.string, PropTypes.func]),
};

export function confirmAlert(props) {
  // the below render is only to invoke proptypes on ConfirmAlert
  ReactDOMServer.renderToString(
    <ConfirmAlert
      title={props.title}
      message={props.message}
      cancelLabel={props.cancelLabel}
      confirmLabel={props.confirmLabel}
      onConfirm={props.onConfirm}
      returnFocus={props.returnFocus}
    />,
  );
}
