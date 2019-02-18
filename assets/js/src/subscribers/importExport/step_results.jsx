import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import ReactStringReplace from 'react-string-replace';

function ResultMessage({ subscribersCount, segments, initialMessage }) {
  if (subscribersCount) {
    let message = ReactStringReplace(initialMessage, '%1$s', () => (
      <strong key="%1$s">{subscribersCount.toLocaleString()}</strong>
    ));
    message = ReactStringReplace(message, '%2$s', () => (
      `"${segments.join('", "')}"`
    ));
    return (<p>{message}</p>);
  }
  return null;
}

ResultMessage.propTypes = {
  segments: PropTypes.arrayOf(PropTypes.string.isRequired).isRequired,
  subscribersCount: PropTypes.number.isRequired,
  initialMessage: PropTypes.string.isRequired,
};

function NoAction({ createdSubscribers, updatedSubscribers }) {
  if (!createdSubscribers && !updatedSubscribers) {
    return (<p>{MailPoet.I18n.t('importNoAction')}</p>);
  }
  return null;
}

NoAction.propTypes = {
  createdSubscribers: PropTypes.number.isRequired,
  updatedSubscribers: PropTypes.number.isRequired,
};

function NoWelcomeEmail({ addedToSegmentWithWelcomeNotification }) {
  if (addedToSegmentWithWelcomeNotification) {
    return (<p>{MailPoet.I18n.t('importNoWelcomeEmail')}</p>);
  }
  return null;
}

NoWelcomeEmail.propTypes = {
  addedToSegmentWithWelcomeNotification: PropTypes.bool.isRequired,
};

function StepResults({
  errors,
  createdSubscribers,
  updatedSubscribers,
  segments,
  addedToSegmentWithWelcomeNotification,
  navigate,
}) {
  if (errors.length) {
    MailPoet.Notice.error(_.flatten(errors));
  }
  return (
    <>
      <div className="updated">
        <ResultMessage
          subscribersCount={createdSubscribers}
          segments={segments}
          initialMessage={MailPoet.I18n.t('subscribersCreated')}
        />
        <ResultMessage
          subscribersCount={updatedSubscribers}
          segments={segments}
          initialMessage={MailPoet.I18n.t('subscribersUpdated')}
        />
        <NoAction
          createdSubscribers={createdSubscribers}
          updatedSubscribers={updatedSubscribers}
        />
        <NoWelcomeEmail
          addedToSegmentWithWelcomeNotification={addedToSegmentWithWelcomeNotification}
        />
      </div>
      <button
        type="button"
        className="button-primary wysija"
        onClick={() => navigate('step_method_selection', { trigger: true })}
      >
        {MailPoet.I18n.t('importAgain')}
      </button>
      &nbsp;&nbsp;
      <button
        type="button"
        className="button-primary wysija"
        onClick={() => {
          window.location.href = 'admin.php?page=mailpoet-subscribers';
        }}
      >
        {MailPoet.I18n.t('viewSubscribers')}
      </button>
    </>
  );
}

StepResults.propTypes = {
  errors: PropTypes.arrayOf(PropTypes.string.isRequired),
  segments: PropTypes.arrayOf(PropTypes.string.isRequired).isRequired,
  createdSubscribers: PropTypes.number.isRequired,
  updatedSubscribers: PropTypes.number.isRequired,
  addedToSegmentWithWelcomeNotification: PropTypes.bool.isRequired,
  navigate: PropTypes.func.isRequired,
};

StepResults.defaultProps = {
  errors: [],
};

export default StepResults;
