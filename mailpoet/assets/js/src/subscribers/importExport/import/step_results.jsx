import { useEffect } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import _ from 'underscore';
import { withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import { Button } from 'common/button/button';
import { ErrorBoundary } from 'common';

function ResultMessage({ subscribersCount, segments, initialMessage }) {
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

ResultMessage.defaultProps = {
  segments: [],
  subscribersCount: 0,
  initialMessage: '',
};
ResultMessage.displayName = 'ResultMessage';

function NoAction({ createdSubscribers, updatedSubscribers }) {
  if (!createdSubscribers && !updatedSubscribers) {
    return <p>{MailPoet.I18n.t('importNoAction')}</p>;
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
NoAction.displayName = 'NoAction';

function SuppressionListReminder({ createdSubscribers, updatedSubscribers }) {
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
                data-beacon-article="6064973ce0324b5fdfd0650c"
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

SuppressionListReminder.defaultProps = {
  createdSubscribers: 0,
  updatedSubscribers: 0,
};
SuppressionListReminder.displayName = 'SuppressionListReminder';

function NoWelcomeEmail({ addedToSegmentWithWelcomeNotification }) {
  if (addedToSegmentWithWelcomeNotification) {
    return <p>{MailPoet.I18n.t('importNoWelcomeEmail')}</p>;
  }
  return null;
}

NoWelcomeEmail.propTypes = {
  addedToSegmentWithWelcomeNotification: PropTypes.bool,
};

NoWelcomeEmail.defaultProps = {
  addedToSegmentWithWelcomeNotification: false,
};
NoWelcomeEmail.diplayName = 'NoWelcomeEmail';

function StepResultsComponent({
  errors,
  createdSubscribers,
  updatedSubscribers,
  segments,
  addedToSegmentWithWelcomeNotification,
  history,
}) {
  useEffect(() => {
    if (
      typeof segments === 'undefined' &&
      errors.length === 0 &&
      typeof createdSubscribers === 'undefined' &&
      typeof updatedSubscribers === 'undefined'
    ) {
      history.replace('step_method_selection');
    }
  }, [
    segments,
    createdSubscribers,
    errors.length,
    history,
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
            onClick={() => history.push('step_method_selection')}
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

StepResultsComponent.propTypes = {
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

StepResultsComponent.defaultProps = {
  errors: [],
  segments: undefined,
  createdSubscribers: undefined,
  updatedSubscribers: undefined,
  addedToSegmentWithWelcomeNotification: undefined,
};
StepResultsComponent.displayName = 'StepResultsComponent';
export const StepResults = withRouter(StepResultsComponent);
