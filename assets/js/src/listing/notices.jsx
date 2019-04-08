import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import React, { Fragment } from 'react';
import ReactStringReplace from 'react-string-replace';

const resumeMailerSending = () => {
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'resumeSending',
  }).done(() => {
    MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
    window.mailpoet_listing.forceUpdate();
  }).fail((response) => {
    if (response.errors.length > 0) {
      MailPoet.Notice.error(
        response.errors.map(error => error.message),
        { scroll: true }
      );
    }
  });
};

function MailerError(props) {
  if (props.mta_log.error && props.mta_log.status === 'paused' && props.mta_log.error.operation !== 'authorization') {
    if (props.mta_log.error.operation === 'migration') {
      return (
        <div className="mailpoet_notice notice notice-warning">
          <p>{ props.mta_log.error.error_message }</p>
        </div>
      );
    }

    let message = props.mta_log.error.error_message;
    const code = props.mta_log.error.error_code;
    if (code) {
      message += message ? ', ' : '';
      message += MailPoet.I18n.t('mailerErrorCode').replace('%$1s', code);
    }
    return (
      <div className="mailpoet_notice notice notice-error">
        <p>
          {
            props.mta_log.error.operation === 'send'
              ? MailPoet.I18n.t('mailerSendErrorNotice').replace('%$1s', props.mta_method)
              : MailPoet.I18n.t('mailerConnectionErrorNotice')
          }
          :
          {' '}
          <i>{ message }</i>
        </p>
        { props.mta_method === 'PHPMail' ? <PHPMailerCheckSettingsNotice /> : <MailerCheckSettingsNotice /> }
        <p>
          <a
            href="javascript:;"
            className="button button-primary"
            onClick={resumeMailerSending}
          >
            { MailPoet.I18n.t('mailerResumeSendingButton') }
          </a>
        </p>
      </div>
    );
  }
  return null;
}

MailerError.propTypes = {
  mta_method: PropTypes.string.isRequired,
  mta_log: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

const PHPMailerCheckSettingsNotice = () => (
  <Fragment>
    <p>{ MailPoet.I18n.t('mailerSendErrorCheckConfiguration') }</p>
    <br />
    <p>
      {
        ReactStringReplace(
          MailPoet.I18n.t('mailerSendErrorUseSendingService'),
          /<b>(.*?)<\/b>/g,
          (match, key) => <b key={key}>{ match }</b>
        )
      }
    </p>
    <p>
      <a
        href="https://www.mailpoet.com/free-plan/?utm_source=plugin&utm_campaign=sending-error"
        target="_blank"
        rel="noopener noreferrer"
      >
        { MailPoet.I18n.t('mailerSendErrorSignUpForSendingService') }
      </a>
    </p>
    <br />
  </Fragment>
);

const MailerCheckSettingsNotice = () => (
  <p>
    {
      ReactStringReplace(
        MailPoet.I18n.t('mailerCheckSettingsNotice'),
        /\[link\](.*?)\[\/link\]/g,
        match => <a href="?page=mailpoet-settings#mta" key="check-sending">{ match }</a>
      )
    }
  </p>
);

export default MailerError;
