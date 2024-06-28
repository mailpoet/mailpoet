import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

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
  stepMethodSelectionData?: StepMethodSelectionData;
};

export function StepInputValidation({
  stepMethodSelectionData = undefined,
}: Props): JSX.Element {
  const navigate = useNavigate();
  const [importSource, setImportSource] = useState(undefined);
  const [lastSent, setLastSent] = useState(undefined);

  useEffect(() => {
    if (stepMethodSelectionData === undefined) {
      navigate('/step_method_selection', { replace: true });
    }
  }, [stepMethodSelectionData, navigate]);

  const lastSentSubmit = useCallback(
    (when) => {
      setLastSent(when);
      if (when === 'recently') {
        navigate('/step_data_manipulation');
      }
    },
    [navigate, setLastSent],
  );

  return (
    <>
      {importSource === undefined && (
        <ErrorBoundary>
          <InitialQuestion onSubmit={setImportSource} />
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

StepInputValidation.displayName = 'StepInputValidation';
