import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import ReactDOMServer from 'react-dom/server';

function ConfirmAlert(props) {
  MailPoet.Modal.popup({
    title: props.title,
    template: ReactDOMServer.renderToString(
      <>
        <p>{props.message}</p>
        <button
          id="mailpoet_alert_cancel"
          className="button button-secondary"
          type="button"
        >
          {props.cancelLabel}
        </button>
        <button
          id="mailpoet_alert_confirm"
          className="button button-primary"
          type="submit"
        >
          {props.confirmLabel}
        </button>
      </>,
    ),
    onInit: () => {
      document
        .getElementById('mailpoet_alert_confirm')
        .addEventListener('click', () => {
          MailPoet.Modal.close();
          props.onConfirm();
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
};

ConfirmAlert.defaultProps = {
  title: MailPoet.I18n.t('confirmTitle'),
  cancelLabel: MailPoet.I18n.t('cancelLabel'),
  confirmLabel: MailPoet.I18n.t('confirmLabel'),
};

export default function confirmAlert(props) {
  // the below render is only to invoke proptypes on ConfirmAlert
  ReactDOMServer.renderToString(
    <ConfirmAlert
      title={props.title}
      message={props.message}
      cancelLabel={props.cancelLabel}
      confirmLabel={props.confirmLabel}
      onConfirm={props.onConfirm}
    />,
  );
}
