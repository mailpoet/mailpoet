import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import {
  Button,
  DropdownMenu,
  __experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { useNavigate, useLocation } from 'react-router-dom';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../../../store';

export function ActivityCell({
  runId,
  status,
}: {
  runId: number;
  status: string;
}): JSX.Element {
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);
  const [showResumeConfirm, setShowResumeConfirm] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { updateRunStatus } = useDispatch(storeName);
  const { runStatusUpdate } = useSelect(
    (select) => ({
      runStatusUpdate: select(storeName).getRunStatusUpdate(runId),
    }),
    [runId],
  );
  const isBusyCancel = runStatusUpdate?.status === 'cancelled';
  const isBusyResume = runStatusUpdate?.status === 'running';

  const openActivityModal = () => {
    const params = new URLSearchParams(location.search);
    params.set('runId', runId.toString());
    navigate({ search: params.toString() });
  };

  useEffect(() => {
    if (showCancelConfirm && status === 'cancelled') {
      setShowCancelConfirm(false);
    }
  }, [status, showCancelConfirm]);

  useEffect(() => {
    if (showResumeConfirm && status === 'running') {
      setShowResumeConfirm(false);
    }
  }, [status, showResumeConfirm]);

  const menuControls = [];
  if (status === 'running') {
    menuControls.push({
      title: __('Cancel run', 'mailpoet'),
      onClick: () => setShowCancelConfirm(true),
    });
  } else if (status === 'cancelled') {
    menuControls.push({
      title: __('Resume run', 'mailpoet'),
      onClick: () => setShowResumeConfirm(true),
    });
  }

  return (
    <div className="mailpoet-analytics-subscribers-activity-cell">
      <Button variant="tertiary" onClick={openActivityModal}>
        {__('View activity', 'mailpoet')}
      </Button>
      <ConfirmDialog
        isOpen={showCancelConfirm}
        title={__('Cancel run', 'mailpoet')}
        confirmButtonText={
          isBusyCancel
            ? __('Cancelling…', 'mailpoet')
            : __('Yes, cancel run', 'mailpoet')
        }
        __experimentalHideHeader={false}
        onConfirm={() => {
          if (isBusyCancel) {
            return;
          }
          void updateRunStatus(runId, 'cancelled');
        }}
        onCancel={() => {
          if (!isBusyCancel) {
            setShowCancelConfirm(false);
          }
        }}
      >
        {__(
          'Are you sure you want to cancel this run for this subscriber?',
          'mailpoet',
        )}
      </ConfirmDialog>
      <ConfirmDialog
        isOpen={showResumeConfirm}
        title={__('Resume run', 'mailpoet')}
        confirmButtonText={
          isBusyResume
            ? __('Resuming…', 'mailpoet')
            : __('Yes, resume', 'mailpoet')
        }
        __experimentalHideHeader={false}
        onConfirm={() => {
          if (isBusyResume) {
            return;
          }
          void updateRunStatus(runId, 'running');
        }}
        onCancel={() => {
          if (!isBusyResume) {
            setShowResumeConfirm(false);
          }
        }}
      >
        {__('Are you sure you want to resume this run?', 'mailpoet')}
      </ConfirmDialog>
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
