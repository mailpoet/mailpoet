import { useEffect } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import _ from 'underscore';
import { useNavigate } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import { Button } from 'common/button/button';
import { ErrorBoundary } from 'common';

function ResultMessage({
  subscribersCount = 0,
  segments = [],
  initialMessage = '',
}) {
  if (subscribersCount) {
    let message = ReactStringReplace(initialMessage, '%1$s', () => (
      <strong key="%1$s">{subscribersCount.toLocaleString()}</strong>
    ));
    message = ReactStringReplace(
      message,
      '%2$s',
      () => `"${segments.join('", "')}"`,
    );
    return <p>{message}</p>;
  }
  return null;
}

ResultMessage.propTypes = {
  segments: PropTypes.arrayOf(PropTypes.string.isRequired),
  subscribersCount: PropTypes.number,
  initialMessage: PropTypes.string,
};

ResultMessage.displayName = 'ResultMessage';

function NoAction({ createdSubscribers = 0, updatedSubscribers = 0 }) {
  if (!createdSubscribers && !updatedSubscribers) {
    return <p>{MailPoet.I18n.t('importNoAction')}</p>;
  }
  return null;
}

NoAction.propTypes = {
  createdSubscribers: PropTypes.number,
  updatedSubscribers: PropTypes.number,
};

NoAction.displayName = 'NoAction';

function SuppressionListReminder({
  createdSubscribers = 0,
  updatedSubscribers = 0,
}) {
  if (createdSubscribers || updatedSubscribers) {
    return (
      <>
        <p>
          <b>{MailPoet.I18n.t('congratulationResult')}</b>
        </p>
        <p>
          {ReactStringReplace(
            MailPoet.I18n.t('suppressionListReminder'),
            /\[link](.*?)\[\/link]/,
            (match) => (
              <a
                className="mailpoet-link"
                href="https://kb.mailpoet.com/article/359-how-to-import-a-suppression-list"
                key="kb-link"
                target="_blank"
                rel="noopener noreferrer"
              >
                {match}
              </a>
            ),
          )}
        </p>
      </>
    );
  }
  return null;
}

SuppressionListReminder.propTypes = {
  createdSubscribers: PropTypes.number,
  updatedSubscribers: PropTypes.number,
};

SuppressionListReminder.displayName = 'SuppressionListReminder';

function NoWelcomeEmail({ addedToSegmentWithWelcomeNotification = false }) {
  if (addedToSegmentWithWelcomeNotification) {
    return <p>{MailPoet.I18n.t('importNoWelcomeEmail')}</p>;
  }
  return null;
}

NoWelcomeEmail.propTypes = {
  addedToSegmentWithWelcomeNotification: PropTypes.bool,
};

NoWelcomeEmail.diplayName = 'NoWelcomeEmail';

export function StepResults({
  errors = [],
  createdSubscribers = undefined,
  updatedSubscribers = undefined,
  segments = undefined,
  addedToSegmentWithWelcomeNotification = undefined,
}) {
  const navigate = useNavigate();
  useEffect(() => {
    if (
      typeof segments === 'undefined' &&
      errors.length === 0 &&
      typeof createdSubscribers === 'undefined' &&
      typeof updatedSubscribers === 'undefined'
    ) {
      navigate('/step_method_selection', { replace: true });
    }
  }, [
    segments,
    createdSubscribers,
    errors.length,
    navigate,
    updatedSubscribers,
  ]);
  if (errors.length) {
    MailPoet.Notice.error(_.flatten(errors));
  }
  return (
    <>
      <ErrorBoundary>
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
            addedToSegmentWithWelcomeNotification={
              addedToSegmentWithWelcomeNotification
            }
          />
        </div>
      </ErrorBoundary>
      <ErrorBoundary>
        <SuppressionListReminder
          createdSubscribers={createdSubscribers}
          updatedSubscribers={updatedSubscribers}
        />
      </ErrorBoundary>
      <div className="mailpoet-settings-grid">
        <div className="mailpoet-settings-save">
          <Button
            variant="secondary"
            type="button"
            onClick={() => navigate('/step_method_selection')}
          >
            {MailPoet.I18n.t('importAgain')}
          </Button>
          <Button
            type="button"
            onClick={() => {
              window.location.href = 'admin.php?page=mailpoet-subscribers';
            }}
          >
            {MailPoet.I18n.t('viewSubscribers')}
          </Button>
        </div>
      </div>
    </>
  );
}

StepResults.propTypes = {
  errors: PropTypes.arrayOf(PropTypes.string.isRequired),
  segments: PropTypes.arrayOf(PropTypes.string.isRequired),
  createdSubscribers: PropTypes.number,
  updatedSubscribers: PropTypes.number,
  addedToSegmentWithWelcomeNotification: PropTypes.bool,
};

StepResults.displayName = 'StepResults';
