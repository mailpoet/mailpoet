import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import React from 'react';
import ReactDOM from 'react-dom';

class ConfirmAlert extends React.Component {
  static propTypes = {
    title: PropTypes.string,
    message: PropTypes.string.isRequired,
    cancelLabel: PropTypes.string,
    confirmLabel: PropTypes.string,
    onConfirm: PropTypes.func.isRequired,
  }

  static defaultProps = {
    title: MailPoet.I18n.t('confirmTitle'),
    cancelLabel: MailPoet.I18n.t('cancelLabel'),
    confirmLabel: MailPoet.I18n.t('confirmLabel'),
  }

  constructor(props) {
    super(props);
    this.state = { show: true };
  }

  componentWillUpdate = () => {
    if (!this.state.show) {
      this.setState({ show: true });
    }
  }

  onClose = () => {
    this.setState({ show: false });
  }

  onConfirm = () => {
    this.onClose();
    this.props.onConfirm();
  }

  render() {
    const { title, message, confirmLabel, cancelLabel } = this.props;

    return (this.state.show &&
      <div className="mailpoet_modal_overlay">
        <div className="mailpoet_popup" tabIndex="-1">
          <div className="mailpoet_popup_wrapper">
            <button className="mailpoet_modal_close" onClick={this.onClose} />
            {title &&
              <div className="mailpoet_popup_title">
                <h2>{title}</h2>
              </div>
            }
            <div className="mailpoet_popup_body clearfix">
              <p className="mailpoet_hp_email_label">{message}</p>
              <button className="button button-secondary" onClick={this.onClose}>
                {cancelLabel}
              </button>
              <button className="button button-primary" onClick={this.onConfirm}>
                {confirmLabel}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default function confirmAlert(props) {
  ReactDOM.render(<ConfirmAlert {...props} />, document.getElementById('mailpoet_confirm_alert_holder'));
}
