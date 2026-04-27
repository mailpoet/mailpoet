import { __ } from '@wordpress/i18n';
import { Button, Dropdown, MenuItem, MenuGroup } from '@wordpress/components';
import { chevronDown, Icon } from '@wordpress/icons';
import { MailPoet } from 'mailpoet';
import { NewsletterType } from './newsletter-type';

type ExportFormat = 'csv' | 'xlsx';

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
      });
      window.location.href = fileUrl;
    })
    .fail((response: ErrorResponse) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
      }
    });
}

export function ExportButton({ newsletter }: Props): JSX.Element | null {
  if (!MailPoet.trackingConfig.emailTrackingEnabled) {
    return null;
  }

  return (
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
        >
          {__('Export', 'mailpoet')}
          <Icon icon={chevronDown} size={18} />
        </Button>
      )}
      renderContent={({ onClose }) => (
        <MenuGroup>
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
      )}
    />
  );
}

ExportButton.displayName = 'ExportButton';
