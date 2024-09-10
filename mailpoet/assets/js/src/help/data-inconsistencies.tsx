import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';
import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { KeyValueTable } from 'common/key-value-table';

type DataInconsistencies = {
  [key: string]: number;
};

export function DataInconsistencies() {
  const [data, setData] = useState({ total: 0 } as DataInconsistencies);
  const [cleaningKey, setCleaningKey] = useState('');

  useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'help',
      action: 'getInconsistentDataStatus',
    })
      .done((response) => {
        setData((response.data as DataInconsistencies) || null);
      })
      .catch((e) => {
        MailPoet.Notice.show({
          type: 'error',
          message: e.errors.map((error) => error.message).join(' '),
          scroll: true,
        });
      });
  }, []);

  const labelsMap = useMemo(
    () => ({
      orphaned_sending_tasks: __('Orphaned Sending Tasks', 'mailpoet'),
      orphaned_sending_task_subscribers: __(
        'Orphaned Sending Task Subscribers',
        'mailpoet',
      ),
      sending_queue_without_newsletter: __(
        'Sending Queues without Newsletter',
        'mailpoet',
      ),
      orphaned_subscriptions: __('Orphaned Subscriptions', 'mailpoet'),
      orphaned_links: __('Orphaned Links', 'mailpoet'),
      orphaned_newsletter_posts: __('Orphaned Newsletter Posts', 'mailpoet'),
    }),
    [],
  );

  const fixInconsistency = useCallback((key) => {
    setCleaningKey(key as string);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'help',
      action: 'fixInconsistentData',
      data: {
        inconsistency: key,
      },
    })
      .done((response) => {
        setCleaningKey('');
        setData((response.data as DataInconsistencies) || null);
        MailPoet.Notice.show({
          message: __('Inconsistency fixed!', 'mailpoet'),
          scroll: true,
        });
      })
      .catch((e) => {
        setCleaningKey('');
        MailPoet.Notice.show({
          type: 'error',
          message: e.errors.map((error) => error.message).join(' '),
          scroll: true,
        });
      });
  }, []);

  if (!data || data.total === 0) {
    return null;
  }

  const rowsData = Object.entries(data)
    .filter(([key, value]) => key !== 'total' && value > 0)
    .map(([key, value]) => ({
      key: labelsMap[key],
      value,
      action: (
        <Button
          variant="primary"
          size="small"
          label={__('Clean', 'mailpoet')}
          onClick={() => fixInconsistency(key)}
          isBusy={cleaningKey === key}
          disabled={!!cleaningKey}
        >
          {__('Fix', 'mailpoet')}
        </Button>
      ),
    }));

  return (
    <div>
      <h2>{__('Data Inconsistencies', 'mailpoet')}</h2>
      <p>
        {__(
          'We found the following data inconsistencies in your DB. Click the "Fix" button to clean them.',
          'mailpoet',
        )}
      </p>
      <KeyValueTable rows={rowsData} max_width="400px" is_fixed={false} />
    </div>
  );
}
