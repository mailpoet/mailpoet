import { __ } from '@wordpress/i18n';
import { KeyValueTable } from 'common/key-value-table';

interface DataInconsistenciesProps {
  dataInconsistencies: {
    [key: string]: number;
  };
}

export function DataInconsistencies({
  dataInconsistencies,
}: DataInconsistenciesProps) {
  if (!dataInconsistencies || dataInconsistencies.total === 0) {
    return null;
  }
  const rowsData = Object.entries(dataInconsistencies)
    .filter(([key]) => key !== 'total')
    .map(([key, value]) => ({
      key,
      value,
    }));
  return (
    <div>
      <h2>{__('Data Inconsistencies', 'mailpoet')}</h2>
      <KeyValueTable rows={rowsData} />
    </div>
  );
}
