import { Modal, Spinner } from '@wordpress/components';
import { useNavigate, useLocation } from 'react-router-dom';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Table } from '@woocommerce/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Hooks } from 'wp-js-hooks';
import { Header } from './modal/header';
import { headers, transformLogsToRows } from './modal/rows';
import { Footer } from './modal/footer';
import { RunData } from '../../../store';
import { runLogs as SampleRunLogData } from '../../../store/samples/run-logs';

export type ActivityModalState = 'loading' | 'loaded' | 'hidden';

function getSampleData(runId: number): RunData | undefined {
  return Hooks.applyFilters(
    'mailpoet_analytics_section_sample_data',
    SampleRunLogData[runId],
    'runLogs',
  ) as RunData | undefined;
}

export function ActivityModal(): JSX.Element {
  const navigate = useNavigate();
  const location = useLocation();
  const pageParams = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );
  const runId = pageParams.get('runId');
  const [state, setState] = useState<ActivityModalState>('hidden');
  const [run, setRun] = useState<RunData | null>(null);

  const closeModal = useCallback(() => {
    setState('hidden');
    setRun(null);
    pageParams.delete('runId');
    navigate({ search: pageParams.toString() });
  }, [navigate, pageParams]);

  useEffect(() => {
    const controller = new AbortController();
    const loadRunData = async () => {
      if (!runId) {
        return;
      }
      setState('loading');

      const sampleData = getSampleData(parseInt(runId, 10));
      if (sampleData) {
        setRun(sampleData);
        setState('loaded');
        return;
      }

      try {
        const data = await apiFetch<{ data: RunData }>({
          path: addQueryArgs('/automation/analytics/run-logs', { id: runId }),
          method: 'GET',
          signal: controller.signal,
        });
        setRun(data.data);
        setState('loaded');
      } catch (error) {
        if (!controller.signal?.aborted) {
          closeModal();
        }
      }
    };

    void loadRunData();

    return () => {
      setState('hidden');
      controller.abort();
    };
  }, [runId, closeModal]);

  if (state === 'hidden') {
    return null;
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

      <Table
        className="mailpoet-analytics-activity-modal-table"
        headers={headers}
        rows={transformLogsToRows(run.logs, run.automation.steps)}
      />

      <Footer run={run.run} />
    </Modal>
  );
}
