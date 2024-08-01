import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';
import { useState, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { KeyValueTable } from 'common/key-value-table';

type DataInconsistencies = {
  [key: string]: number;
};

type Props = {
  dataInconsistencies: DataInconsistencies;
};

export function DataInconsistencies({ dataInconsistencies }: Props) {
  const [data, setData] = useState(dataInconsistencies);
  const [cleaningKey, setCleaningKey] = useState('');

  const labelsMap = useMemo(
    () => ({
      orphaned_sending_tasks: __('Orphaned Sending Tasks', 'mailpoet'),
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
          message: __('Inconsistency cleaned', 'mailpoet'),
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
    .filter(([key]) => key !== 'total')
    .map(([key, value]) => ({
      key: labelsMap[key],
      value: (
        <div>
          {value} (
          <Button
            variant="link"
            label={__('Clean', 'mailpoet')}
            onClick={() => fixInconsistency(key)}
            isBusy={cleaningKey === key}
            disabled={!!cleaningKey}
          >
            {__('Fix', 'mailpoet')}
          </Button>
          )
        </div>
      ),
    }));

  return (
    <div>
      <h2>{__('Data Inconsistencies', 'mailpoet')}</h2>
      <KeyValueTable rows={rowsData} />
    </div>
  );
}
