import { useCallback } from 'react';
import { Modal, Notice } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import type { LogsFilter } from './url-state';

type DownloadConfig = {
  action_url: string;
  nonce: string;
};

// Keep in sync with LogsDownload::MAX_LOGS on the PHP side.
const MAX_LOGS = 50000;

type Props = {
  count: number;
  filter: LogsFilter;
  search: string | undefined;
  isUnrestricted: boolean;
  downloadConfig: DownloadConfig;
  onClose: () => void;
};

function buildDownloadUrl(
  downloadConfig: DownloadConfig,
  filter: LogsFilter,
  search: string | undefined,
): string {
  const params = new URLSearchParams({
    action: 'mailpoet_download_logs',
    _wpnonce: downloadConfig.nonce,
  });
  if (filter.from) params.set('from', filter.from);
  if (filter.to) params.set('to', filter.to);
  (filter.name ?? []).forEach((name) => params.append('name[]', name));
  (filter.level ?? []).forEach((level) =>
    params.append('level[]', String(level)),
  );
  if (search) params.set('search', search);
  return `${downloadConfig.action_url}?${params.toString()}`;
}

export function DownloadLogsModal({
  count,
  filter,
  search,
  isUnrestricted,
  downloadConfig,
  onClose,
}: Props): JSX.Element {
  const formattedCount = count.toLocaleString();
  const message = isUnrestricted
    ? sprintf(
        _n(
          'This downloads the only log as a .txt file.',
          'This downloads all %s logs as a .txt file.',
          count,
          'mailpoet',
        ),
        formattedCount,
      )
    : sprintf(
        _n(
          'This downloads %s log matching the current filters as a .txt file.',
          'This downloads %s logs matching the current filters as a .txt file.',
          count,
          'mailpoet',
        ),
        formattedCount,
      );

  const handleDownload = useCallback((): void => {
    window.location.assign(buildDownloadUrl(downloadConfig, filter, search));
    onClose();
  }, [downloadConfig, filter, search, onClose]);

  return (
    <Modal
      className="mailpoet-logs-download-modal"
      title={__('Download logs', 'mailpoet')}
      onRequestClose={onClose}
    >
      {count > MAX_LOGS && (
        <Notice status="warning" isDismissible={false}>
          {sprintf(
            __('Only the most recent %s logs will be downloaded.', 'mailpoet'),
            MAX_LOGS.toLocaleString(),
          )}
        </Notice>
      )}

      <p>{message}</p>

      <div className="mailpoet-logs-download-modal__actions">
        <Button dimension="small" variant="secondary" onClick={onClose}>
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button dimension="small" onClick={handleDownload}>
          {sprintf(
            _n('Download %s log', 'Download %s logs', count, 'mailpoet'),
            formattedCount,
          )}
        </Button>
      </div>
    </Modal>
  );
}
