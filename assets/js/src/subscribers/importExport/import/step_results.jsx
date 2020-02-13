import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import { withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import OfferMigration from './step_results/offer_migration.jsx';

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
  segments: PropTypes.arrayOf(PropTypes.string.isRequired),
  subscribersCount: PropTypes.number,
  initialMessage: PropTypes.string,
};

ResultMessage.defaultProps = {
  segments: [],
  subscribersCount: 0,
  initialMessage: '',
};

function NoAction({ createdSubscribers, updatedSubscribers }) {
  if (!createdSubscribers && !updatedSubscribers) {
    return (<p>{MailPoet.I18n.t('importNoAction')}</p>);
  }
  return null;
}

NoAction.propTypes = {
  createdSubscribers: PropTypes.number,
  updatedSubscribers: PropTypes.number,
};

NoAction.defaultProps = {
  createdSubscribers: 0,
  updatedSubscribers: 0,
};

function NoWelcomeEmail({ addedToSegmentWithWelcomeNotification }) {
  if (addedToSegmentWithWelcomeNotification) {
    return (<p>{MailPoet.I18n.t('importNoWelcomeEmail')}</p>);
  }
  return null;
}

NoWelcomeEmail.propTypes = {
  addedToSegmentWithWelcomeNotification: PropTypes.bool,
};

NoWelcomeEmail.defaultProps = {
  addedToSegmentWithWelcomeNotification: false,
};

function StepResults({
  errors,
  createdSubscribers,
  updatedSubscribers,
  segments,
  addedToSegmentWithWelcomeNotification,
  history,
}) {
  useEffect(
    () => {
      if (
        (typeof (segments) === 'undefined')
        && (errors.length === 0)
        && (typeof createdSubscribers) === 'undefined'
        && (typeof updatedSubscribers) === 'undefined'
      ) {
        history.replace('step_method_selection');
      }
    },
    [segments, createdSubscribers, errors.length, history, updatedSubscribers],
  );
  if (errors.length) {
    MailPoet.Notice.error(_.flatten(errors));
  }
  let totalNumberOfSubscribers = 0;
  if (createdSubscribers != null) {
    totalNumberOfSubscribers += createdSubscribers;
  }
  if (updatedSubscribers != null) {
    totalNumberOfSubscribers += updatedSubscribers;
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
        onClick={() => history.push('step_method_selection')}
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
      <OfferMigration
        subscribersCount={totalNumberOfSubscribers}
      />
    </>
  );
}

StepResults.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
    replace: PropTypes.func.isRequired,
  }).isRequired,
  errors: PropTypes.arrayOf(PropTypes.string.isRequired),
  segments: PropTypes.arrayOf(PropTypes.string.isRequired),
  createdSubscribers: PropTypes.number,
  updatedSubscribers: PropTypes.number,
  addedToSegmentWithWelcomeNotification: PropTypes.bool,
};

StepResults.defaultProps = {
  errors: [],
  segments: undefined,
  createdSubscribers: undefined,
  updatedSubscribers: undefined,
  addedToSegmentWithWelcomeNotification: undefined,
};

export default withRouter(StepResults);
