import { __, _x } from '@wordpress/i18n';
import {
  SummaryList,
  SummaryListPlaceholder,
  SummaryNumber,
} from '@woocommerce/components/build';
import { select, useSelect } from '@wordpress/data';
import { MailPoet } from '../../../../../../mailpoet';
import { OverviewSection, storeName } from '../../store';
import { locale } from '../../../../../config';

function getEmailPercentage(
  type: 'opened' | 'clicked',
  period: 'current' | 'previous' = 'current',
): number | undefined {
  const overview = select(storeName).getSection('overview');
  if (overview.data === undefined) {
    return undefined;
  }

  const data = overview.data[type] ?? null;
  const total = overview.data?.total ?? null;
  if (!data || !total || !data[period] || !total[period]) {
    return 0;
  }

  return (data[period] * 100) / total[period];
}

function getEmailDelta(type: 'opened' | 'clicked'): number | undefined {
  const current = getEmailPercentage(type, 'current');
  const previous = getEmailPercentage(type, 'previous');
  if (current === undefined || previous === undefined) {
    return undefined;
  }

  if (previous === 0) {
    return 0;
  }

  const newValue = current > previous ? current - previous : previous - current;
  return (newValue / previous) * 100;
}

function getWooCommerceTotal(
  type: 'revenue' | 'orders',
  period: 'current' | 'previous' = 'current',
): number | undefined {
  const overview = select(storeName).getSection('overview');
  if (overview.data === undefined) {
    return undefined;
  }

  const data = overview.data[type] ?? null;
  if (!data || !data[period]) {
    return 0;
  }

  return data[period] as number;
}

function getWooCommerceDelta(type: 'revenue' | 'orders'): number | undefined {
  const current = getWooCommerceTotal(type, 'current');
  const previous = getWooCommerceTotal(type, 'previous');

  if (current === undefined || previous === undefined) {
    return undefined;
  }
  const newValue = current > previous ? current - previous : previous - current;
  if (newValue === 0 || previous === 0) {
    return 0;
  }

  return (newValue / previous) * 100;
}

export function Overview(): JSX.Element | null {
  const { overview, hasEmails } = useSelect((s) => ({
    overview: s(storeName).getSection('overview'),
    hasEmails: s(storeName).automationHasEmails(),
  })) as { overview: OverviewSection; hasEmails: boolean };

  const percentageFormatter = new Intl.NumberFormat(locale.toString(), {
    style: 'percent',
    maximumFractionDigits: 2,
  });
  const numberFormatter = new Intl.NumberFormat(locale.toString());
  const items: JSX.Element[] = [];
  if (overview.data !== undefined) {
    items.push(
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      <SummaryNumber
        key="overview-opened"
        label={__('Opened', 'mailpoet')}
        value={`${percentageFormatter.format(getEmailPercentage('opened'))}`}
        delta={getEmailDelta('opened').toFixed(2) as unknown as number}
      />,
    );
    items.push(
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      <SummaryNumber
        key="overview-clicked"
        label={__('Clicked', 'mailpoet')}
        value={percentageFormatter.format(getEmailPercentage('clicked'))}
        delta={getEmailDelta('clicked').toFixed(2) as unknown as number}
      />,
    );
  }
  if (overview.data !== undefined && MailPoet.isWoocommerceActive) {
    items.push(
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      <SummaryNumber
        key="overview-orders"
        label={_x('Orders', 'WooCommerce orders', 'mailpoet')}
        delta={getWooCommerceDelta('orders').toFixed(2) as unknown as number}
        value={numberFormatter.format(getWooCommerceTotal('orders'))}
      />,
    );
    items.push(
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      <SummaryNumber
        key="overview-revenue"
        label={__('Revenue', 'mailpoet')}
        delta={getWooCommerceDelta('revenue').toFixed(2) as unknown as number}
        value={overview.data?.revenue_formatted?.current}
      />,
    );
  }

  if (!hasEmails) {
    return null;
  }
  return (
    <div className="mailpoet-automation-analytics-overview">
      <h1>{__('Overview', 'mailpoet')}</h1>
      {items.length === 0 && (
        <SummaryListPlaceholder
          key="placeholder"
          numberOfItems={MailPoet.isWoocommerceActive ? 4 : 2}
        />
      )}
      {items.length !== 0 && (
        <SummaryList label={__('Overview', 'mailpoet')}>
          {() => items}
        </SummaryList>
      )}
    </div>
  );
}
