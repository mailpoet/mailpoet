import { Modal, Spinner } from '@wordpress/components';
import { useHistory, useLocation } from 'react-router-dom';
import { useEffect, useMemo, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { ActivityModalState, RunData } from './modal/types';
import { Header } from './modal/header';

export function ActivityModal(): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageParams = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );
  const [state, setState] = useState<ActivityModalState>('hidden');

  const [runId, setRunId] = useState<number | null>(null);
  const [run, setRun] = useState<RunData | null>(null);

  const closeModal = () => {
    setState('hidden');
    setRun(null);
    pageParams.delete('runId');
    history.push({ search: pageParams.toString() });
  };

  // Open modal when activity param changes in the URL
  useEffect(() => {
    setRunId(parseInt(pageParams.get('runId'), 10));
  }, [pageParams]);

  useEffect(() => {
    const controller = new AbortController();
    const loadRunData = async () => {
      if (!runId) {
        return;
      }
      setState('loading');

      try {
        const data = await apiFetch<{ data: RunData }>({
          path: `/automation/analytics/run-logs&id=${runId}`,
          method: 'GET',
          signal: controller.signal,
        });
        setRun(data.data);
        setState('loaded');
      } catch (error) {
        if (!controller.signal?.aborted) {
          setState('error');
        }
      }
    };

    void loadRunData();

    return () => {
      setState('hidden');
      controller.abort();
    };
  }, [runId]);

  if (state === 'hidden') {
    return <div />;
  }

  if (state === 'loading') {
    return (
      <Modal
        onRequestClose={closeModal}
        __experimentalHideHeader
        className="mailpoet-analytics-activity-modal-spinner"
      >
        <Spinner className="mailpoet-automation-thumbnail-spinner" />
      </Modal>
    );
  }

  return (
    <Modal
      onRequestClose={closeModal}
      __experimentalHideHeader
      className="mailpoet-analytics-activity-modal"
    >
      <Header subscriber={run.subscriber} onClose={closeModal} />
    </Modal>
  );
}
