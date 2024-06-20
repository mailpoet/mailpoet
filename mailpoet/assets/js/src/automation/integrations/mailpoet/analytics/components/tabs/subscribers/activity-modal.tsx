import { Modal } from '@wordpress/components';
import { useHistory, useLocation } from 'react-router-dom';
import { useEffect, useMemo, useState } from 'react';

export function ActivityModal(): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageParams = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );
  const [runId, setRunId] = useState(null);

  // Open modal when activity param changes in the URL
  useEffect(() => {
    setRunId(pageParams.get('runId'));
  }, [pageParams]);

  const closeModal = () => {
    pageParams.delete('runId');
    history.push({ search: pageParams.toString() });
  };

  return (
    <>
      {runId && (
        <Modal onRequestClose={closeModal} title={runId}>
          <div />
        </Modal>
      )}
    </>
  );
}
