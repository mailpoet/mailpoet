import { useCallback, useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Dropdown, MenuItem, MenuGroup } from '@wordpress/components';
import { chevronDown, Icon } from '@wordpress/icons';
import { MailPoet } from 'mailpoet';
import { PremiumModal } from '../../common/premium-modal';
import {
  ExportFormat,
  StatusPayload,
  pollExportStatus,
} from '../statistics-export/poll-export-status';
import { NewsletterType } from './newsletter-type';

type Props = {
  newsletter: NewsletterType;
};

function exportCampaignStats(
  newsletterId: string | number,
  format: ExportFormat,
): void {
  MailPoet.Modal.loading(true);
  void MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'statisticsExport',
    action: 'exportCampaign',
    data: {
      id: newsletterId,
      format,
    },
  })
    .always(() => {
      MailPoet.Modal.loading(false);
    })
    .done((response) => {
      const fileUrl = response.data?.exportFileURL as string | undefined;
      if (!fileUrl) {
        MailPoet.Notice.error(
          __('The export file could not be generated.', 'mailpoet'),
          { scroll: true },
        );
        return;
      }
      MailPoet.trackEvent('Email statistics export completed', {
        'File Format': format,
        'Export Type': 'aggregate',
      });
      window.location.href = fileUrl;
    })
    .fail((response: ErrorResponse) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
      }
    });
}

const scheduleRecipientsExport = (
  newsletterId: string | number,
  format: ExportFormat,
) =>
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'statisticsExport',
    action: 'exportRecipients',
    data: {
      id: newsletterId,
      format,
    },
  }).then((response) => response.data as StatusPayload);

const useRecipientsExport = (newsletterId: string | number) => {
  const [isExporting, setIsExporting] = useState(false);
  const cancelRef = useRef<(() => void) | null>(null);

  const stopPolling = useCallback(() => {
    if (cancelRef.current !== null) {
      cancelRef.current();
      cancelRef.current = null;
    }
  }, []);

  useEffect(() => stopPolling, [stopPolling]);

  const handleSuccess = useCallback(
    (status: StatusPayload, format: ExportFormat) => {
      setIsExporting(false);
      if (!status.exportFileURL) {
        MailPoet.Notice.error(
          __('The export file could not be generated.', 'mailpoet'),
          { scroll: true },
        );
        return;
      }
      MailPoet.trackEvent('Email statistics export completed', {
        'File Format': format,
        'Export Type': 'recipients',
      });
      window.location.href = status.exportFileURL;
    },
    [],
  );

  const handleFailure = useCallback((message: string) => {
    setIsExporting(false);
    MailPoet.Notice.error(message, { scroll: true });
  }, []);

  const start = useCallback(
    (format: ExportFormat) => {
      if (isExporting) {
        return;
      }
      setIsExporting(true);
      MailPoet.Notice.success(
        __(
          'Export queued. The download will start automatically when it is ready.',
          'mailpoet',
        ),
        { timeout: 6000 },
      );
      scheduleRecipientsExport(newsletterId, format)
        .then((status) => {
          if (status.exportFileURL) {
            handleSuccess(status, format);
            return;
          }
          const handle = pollExportStatus(status.taskId, {
            onComplete: (s) => handleSuccess(s, format),
            onError: handleFailure,
          });
          cancelRef.current = handle.cancel;
        })
        .catch((response: ErrorResponse) => {
          const message =
            response?.errors?.[0]?.message ??
            __('Could not start the export.', 'mailpoet');
          handleFailure(message);
        });
    },
    [handleFailure, handleSuccess, isExporting, newsletterId],
  );

  return { start, isExporting };
};

export function ExportButton({ newsletter }: Props): JSX.Element | null {
  const [showPremiumModal, setShowPremiumModal] = useState(false);
  const { start: startRecipientsExport, isExporting } = useRecipientsExport(
    newsletter.id,
  );

  if (!MailPoet.trackingConfig.emailTrackingEnabled) {
    return null;
  }

  const isRecipientsRestricted =
    !MailPoet.capabilities.detailedAnalytics ||
    MailPoet.capabilities.detailedAnalytics.isRestricted;

  const handleRecipientsClick = (format: ExportFormat, onClose: () => void) => {
    onClose();
    if (isRecipientsRestricted) {
      setShowPremiumModal(true);
      return;
    }
    startRecipientsExport(format);
  };

  return (
    <>
      <Dropdown
        className="mailpoet-stats-export-dropdown"
        focusOnMount={false}
        popoverProps={{ placement: 'bottom-end' }}
        renderToggle={({ isOpen, onToggle }) => (
          <Button
            variant="secondary"
            onClick={onToggle}
            aria-expanded={isOpen}
            aria-label={__('Export', 'mailpoet')}
            isBusy={isExporting}
            disabled={isExporting}
          >
            {__('Export', 'mailpoet')}
            <Icon icon={chevronDown} size={18} />
          </Button>
        )}
        renderContent={({ onClose }) => (
          <>
            <MenuGroup label={__('Campaign summary', 'mailpoet')}>
              <MenuItem
                className="mailpoet-no-box-shadow"
                onClick={() => {
                  exportCampaignStats(newsletter.id, 'csv');
                  onClose();
                }}
              >
                {__('Export as CSV', 'mailpoet')}
              </MenuItem>
              <MenuItem
                className="mailpoet-no-box-shadow"
                onClick={() => {
                  exportCampaignStats(newsletter.id, 'xlsx');
                  onClose();
                }}
              >
                {__('Export as Excel (XLSX)', 'mailpoet')}
              </MenuItem>
            </MenuGroup>
            <MenuGroup label={__('Per recipient', 'mailpoet')}>
              <MenuItem
                className="mailpoet-no-box-shadow"
                onClick={() => handleRecipientsClick('csv', onClose)}
              >
                {__('Recipients as CSV', 'mailpoet')}
              </MenuItem>
              <MenuItem
                className="mailpoet-no-box-shadow"
                onClick={() => handleRecipientsClick('xlsx', onClose)}
              >
                {__('Recipients as Excel (XLSX)', 'mailpoet')}
              </MenuItem>
            </MenuGroup>
          </>
        )}
      />
      {showPremiumModal && (
        <PremiumModal
          onRequestClose={() => setShowPremiumModal(false)}
          data={{ capabilities: { detailedAnalytics: true } }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'stats_recipients_export',
          }}
        >
          {__(
            'Per-recipient statistics export is available with detailed analytics.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </>
  );
}

ExportButton.displayName = 'ExportButton';
