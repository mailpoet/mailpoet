import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import classnames from 'classnames';

const resumeMailerSending = () => {
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'resumeSending',
  })
    .done(() => {
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
      if (window.mailpoet_listing) {
        window.mailpoet_listing.forceUpdate();
      }
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true },
        );
      }
    });
};

export function MailerError(props) {
  if (
    !props.mta_log?.error ||
    props.mta_log.status !== 'paused' ||
    props.mta_log.error.operation === 'authorization'
  ) {
    return null;
  }
  // do not display MailPoet API Key error twice
  if (
    props.mta_log.error.operation === 'send' &&
    props.mta_method === 'MailPoet' &&
    MailPoet.hasInvalidMssApiKey
  ) {
    return null;
  }
  // do not display Email Volume Limit reached error twice
  if (
    props.mta_method === 'MailPoet' &&
    props.mta_log.error.operation === 'email_limit_reached'
  ) {
    return null;
  }

  if (props.mta_log.error.operation === 'migration') {
    const className = classnames('mailpoet_notice notice notice-warning', {
      inline: props.is_inline,
    });
    return (
      <div className={className}>
        <p>{props.mta_log.error.error_message}</p>
      </div>
    );
  }

  let message = props.mta_log.error.error_message;
  const code = props.mta_log.error.error_code;
  const className = classnames('mailpoet_notice notice notice-error', {
    inline: props.is_inline,
  });
  if (code) {
    message += message ? ', ' : '';
    message += MailPoet.I18n.t('mailerErrorCode').replace('%1$s', code);
  }

  // allow <a> tags with some attributes
  const links = [];
  message = message.replace(/<a.*?>.*?<\/a>/g, (match) => {
    links.push(match);
    return `[link-${links.length - 1}]`;
  });

  message = ReactStringReplace(message, /\[link-(\d+)\]/g, (match) => {
    const link = new DOMParser().parseFromString(
      links[match],
      'text/xml',
    ).firstChild;

    const listOfAttributeNames = link.getAttributeNames();
    const allowedAttributeNames = [
      'target',
      'rel',
      'class',
      'data-email',
      'data-type',
    ];

    // include these custom attributes in the final link
    const allowedAttributes = listOfAttributeNames.reduce((acc, name) => {
      if (allowedAttributeNames.includes(name)) {
        // react requires the class attribute to be named className.
        return {
          ...acc,
          [name === 'class' ? 'className' : name]: link.getAttribute(name),
        };
      }
      return acc;
    }, {});

    return (
      <a
        key={`a-${match}`}
        href={link.getAttribute('href')}
        {...allowedAttributes}
      >
        {link.textContent}
      </a>
    );
  });

  // allow <br> tags
  let brKey = 0;
  message = ReactStringReplace(
    message,
    /(<br\s*\/?>)/g,
    () => <br key={`br-${brKey++}`} />, // eslint-disable-line no-plusplus
  );

  if (props.mta_log.error.operation === 'pending_approval') {
    return (
      <div className={className}>
        <p>{message}</p>
      </div>
    );
  }

  if (props.mta_log.error.operation === 'insufficient_privileges') {
    return (
      <div className={className}>
        <p>{message}</p>
        <p>
          <a
            href="#"
            className="button button-primary"
            onClick={(event) => {
              event.preventDefault();
              resumeMailerSending(event);
            }}
          >
            {MailPoet.I18n.t('mailerResumeSendingAfterUpgradeButton')}
          </a>
        </p>
      </div>
    );
  }

  return (
    <div className={className}>
      <p>
        {props.mta_log.error.operation === 'send'
          ? MailPoet.I18n.t('mailerSendErrorNotice').replace(
              '%1$s',
              props.mta_method,
            )
          : MailPoet.I18n.t('mailerConnectionErrorNotice')}
        : <i>{message}</i>
      </p>
      {props.mta_method === 'PHPMail' ? (
        <PHPMailerCheckSettingsNotice />
      ) : (
        <MailerCheckSettingsNotice />
      )}
      <p>
        <a
          href="#"
          className="button button-primary"
          onClick={(event) => {
            event.preventDefault();
            resumeMailerSending(event);
          }}
        >
          {MailPoet.I18n.t('mailerResumeSendingButton')}
        </a>
      </p>
    </div>
  );
}

MailerError.propTypes = {
  mta_method: PropTypes.string.isRequired,
  mta_log: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  is_inline: PropTypes.bool,
};

MailerError.defaultProps = {
  mta_log: {},
  is_inline: false,
};

function PHPMailerCheckSettingsNotice() {
  return (
    <>
      <p>{MailPoet.I18n.t('mailerSendErrorCheckConfiguration')}</p>
      <br />
      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('mailerSendErrorUseSendingService'),
          /<b>(.*?)<\/b>/g,
          (match, key) => (
            <b key={key}>{match}</b>
          ),
        )}
      </p>
      <p>
        <a
          href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({
            utm_campaign: 'sending-error',
          })}
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('mailerSendErrorSignUpForSendingService')}
        </a>
      </p>
      <br />
    </>
  );
}

function MailerCheckSettingsNotice() {
  return (
    <p>
      {ReactStringReplace(
        MailPoet.I18n.t('mailerCheckSettingsNotice'),
        /\[link\](.*?)\[\/link\]/g,
        (match) => (
          <a href="?page=mailpoet-settings#mta" key="check-sending">
            {match}
          </a>
        ),
      )}
    </p>
  );
}
