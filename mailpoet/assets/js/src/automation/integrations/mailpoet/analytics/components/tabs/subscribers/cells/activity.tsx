import { __ } from '@wordpress/i18n';
import { Button, DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { useNavigate, useLocation } from 'react-router-dom';
import { useDispatch } from '@wordpress/data';
import { storeName } from '../../../../store';

export function ActivityCell({
  runId,
  status,
}: {
  runId: number;
  status: string;
}): JSX.Element {
  const navigate = useNavigate();
  const location = useLocation();
  const { updateRunStatus } = useDispatch(storeName);

  const openActivityModal = () => {
    const params = new URLSearchParams(location.search);
    params.set('runId', runId.toString());
    navigate({ search: params.toString() });
  };

  const handleStatusChange = (newStatus: string) => {
    void updateRunStatus(runId, newStatus);
  };

  const menuControls = [];
  if (status === 'running') {
    menuControls.push({
      title: __('Cancel run', 'mailpoet'),
      onClick: () => handleStatusChange('cancelled'),
    });
  } else if (status === 'cancelled') {
    menuControls.push({
      title: __('Resume run', 'mailpoet'),
      onClick: () => handleStatusChange('running'),
    });
  }

  return (
    <div className="mailpoet-analytics-subscribers-activity-cell">
      <Button variant="tertiary" onClick={openActivityModal}>
        {__('View activity', 'mailpoet')}
      </Button>
      {menuControls.length > 0 && (
        <DropdownMenu
          className="mailpoet-analytics-subscribers-more-button"
          label={__('More', 'mailpoet')}
          icon={moreVertical}
          controls={menuControls}
          popoverProps={{ placement: 'bottom-start' }}
          variant="tertiary"
        />
      )}
    </div>
  );
}
