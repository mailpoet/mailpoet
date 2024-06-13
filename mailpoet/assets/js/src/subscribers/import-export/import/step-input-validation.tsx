import { ComponentType, useCallback, useEffect, useState } from 'react';
import { withRouter, RouteComponentProps } from 'react-router-dom';

import { CleanList } from 'subscribers/import-export/import/clean-list';
import { ErrorBoundary } from 'common';
import { InitialQuestion } from './step-input-validation/initial-question.jsx';
import { WrongSourceBlock } from './step-input-validation/wrong-source-block.jsx';
import { LastSentQuestion } from './step-input-validation/last-sent-question.jsx';

type StepMethodSelectionData = {
  duplicate: string[];
  header: string[];
  invalid: string[];
  role: string[];
  subscribersCount: number;
  subscribers: Array<string[]>;
};

type Props = {
  history: RouteComponentProps['history'];
  stepMethodSelectionData?: StepMethodSelectionData;
};

function StepInputValidationComponent({
  history,
  stepMethodSelectionData = undefined,
}: Props): JSX.Element {
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
          <CleanList iHaveCleanedList={() => lastSentSubmit('recently')} />
        </ErrorBoundary>
      )}
    </>
  );
}

StepInputValidationComponent.displayName = 'StepInputValidationComponent';

export const StepInputValidation = withRouter(
  StepInputValidationComponent as ComponentType<RouteComponentProps>,
);
