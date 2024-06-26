import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useNavigate, useLocation } from 'react-router-dom';

export function ActivityCell({ runId }: { runId: number }): JSX.Element {
  const navigate = useNavigate();
  const location = useLocation();

  const openActivityModal = () => {
    const params = new URLSearchParams(location.search);
    params.set('runId', runId.toString());
    navigate({ search: params.toString() });
  };

  return (
    <Button variant="tertiary" onClick={openActivityModal}>
      {__('View activity', 'mailpoet')}
    </Button>
  );
}
