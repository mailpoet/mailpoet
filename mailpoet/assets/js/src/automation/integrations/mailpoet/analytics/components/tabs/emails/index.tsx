import { useEffect, useState } from 'react';
import { TableCard } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { calculateSummary } from './summary';
import { transformEmailsToRows } from './rows';
import { EmailStats, OverviewSection, storeName } from '../../../store';

const headers = [
  {
    key: 'email',
    label: __('Email', 'mailpoet'),
  },
  {
    key: 'sent',
    label: __('Sent', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'opened',
    label: __('Opened', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'clicked',
    label: __('Clicked', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'orders',
    label: __('Orders', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'revenue',
    label: __('Revenue', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'unsubscribed',
    label: __('Unsubscribed', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
  },
  {
    key: 'actions',
    label: '',
  },
];

export function Emails(): JSX.Element {
  const { overview } = useSelect((s) => ({
    overview: s(storeName).getSection('overview'),
  })) as { overview: OverviewSection };

  const [visibleEmails, setVisibleEmails] = useState<EmailStats[] | undefined>(
    undefined,
  );
  const [currentPage, setCurrentPage] = useState(1);
  const [rowsPerPage, setRowsPerPage] = useState(25);
  useEffect(() => {
    setVisibleEmails(
      overview.data !== undefined
        ? Object.values(overview.data.emails).splice(
            (currentPage - 1) * rowsPerPage,
            rowsPerPage,
          )
        : undefined,
    );
  }, [overview.data, currentPage, rowsPerPage]);

  const rows =
    visibleEmails !== undefined ? transformEmailsToRows(visibleEmails) : [];

  const summary = calculateSummary(visibleEmails ?? []);
  return (
    <TableCard
      title=""
      onQueryChange={(type: string) => (param: number) => {
        if (type === 'paged') {
          setCurrentPage(param);
          setVisibleEmails(
            overview.data !== undefined
              ? Object.values(overview.data.emails).splice(
                  (param - 1) * rowsPerPage,
                  rowsPerPage,
                )
              : undefined,
          );
        } else if (type === 'per_page') {
          setCurrentPage(1);
          setRowsPerPage(param);
          setVisibleEmails(
            overview.data !== undefined
              ? Object.values(overview.data.emails).splice(0, param)
              : undefined,
          );
        }
      }}
      query={{ paged: currentPage, orderby: 'email', order: 'asc' }}
      rows={rows}
      headers={headers}
      showMenu={false}
      rowsPerPage={rowsPerPage}
      totalRows={
        overview.data !== undefined
          ? Object.values(overview.data.emails).length
          : 0
      }
      summary={summary}
      isLoading={overview.data === undefined}
    />
  );
}
