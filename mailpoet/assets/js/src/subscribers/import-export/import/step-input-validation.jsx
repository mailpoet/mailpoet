import { useCallback, useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';

import { CleanList } from 'subscribers/importExport/import/clean_list';
import { ErrorBoundary } from 'common';
import { InitialQuestion } from './step_input_validation/initial_question.jsx';
import { WrongSourceBlock } from './step_input_validation/wrong_source_block.jsx';
import { LastSentQuestion } from './step_input_validation/last_sent_question.jsx';

function StepInputValidationComponent({ stepMethodSelectionData, history }) {
  const [importSource, setImportSource] = useState(undefined);
  const [lastSent, setLastSent] = useState(undefined);

  useEffect(() => {
    if (stepMethodSelectionData === undefined) {
      history.replace('step_method_selection');
    }
  }, [stepMethodSelectionData, history]);

  const lastSentSubmit = useCallback(
    (when) => {
      setLastSent(when);
      if (when === 'recently') {
        history.push('step_data_manipulation');
      }
    },
    [history, setLastSent],
  );

  return (
    <>
      {importSource === undefined && (
        <ErrorBoundary>
          <InitialQuestion onSubmit={setImportSource} history={history} />
        </ErrorBoundary>
      )}

      {importSource === 'address-book' && <WrongSourceBlock />}

      {importSource === 'existing-list' && lastSent === undefined && (
        <ErrorBoundary>
          <LastSentQuestion onSubmit={lastSentSubmit} />
        </ErrorBoundary>
      )}

      {importSource === 'existing-list' && lastSent === 'notRecently' && (
        <ErrorBoundary>
          <CleanList />
        </ErrorBoundary>
      )}
    </>
  );
}

StepInputValidationComponent.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
    replace: PropTypes.func.isRequired,
  }).isRequired,
  stepMethodSelectionData: PropTypes.shape({
    duplicate: PropTypes.arrayOf(PropTypes.string),
    header: PropTypes.arrayOf(PropTypes.string),
    invalid: PropTypes.arrayOf(PropTypes.string),
    role: PropTypes.arrayOf(PropTypes.string),
    subscribersCount: PropTypes.number,
    subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)),
  }),
};

StepInputValidationComponent.defaultProps = {
  stepMethodSelectionData: undefined,
};

export const StepInputValidation = withRouter(StepInputValidationComponent);
