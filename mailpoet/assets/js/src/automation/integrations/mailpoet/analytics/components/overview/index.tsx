import { __, _x } from '@wordpress/i18n';
import {
  SummaryList,
  SummaryListPlaceholder,
  SummaryNumber as WooSummaryNumber,
} from '@woocommerce/components';
import { select, useSelect } from '@wordpress/data';
import { MailPoet } from '../../../../../../mailpoet';
import { OverviewSection, storeName } from '../../store';
import { storeName as editorStoreName } from '../../../../../editor/store';
import { locale } from '../../../../../config';
import { formattedPrice } from '../../formatter';

// WooSummaryNumber has return type annotated as Object and has all props mandatory
const SummaryNumber = WooSummaryNumber as unknown as (
  ...props: [Partial<Parameters<typeof WooSummaryNumber>[0]>]
) => JSX.Element;

function getEmailPercentage(
  type: 'opened' | 'clicked',
  period: 'current' | 'previous' = 'current',
): number | undefined {
  const overview = select(storeName).getSection('overview');
  if (overview.data === undefined) {
    return undefined;
  }

  const data = overview.data[type] ?? null;
  // Divide by the recipients we were allowed to measure. An opted-out
  // subscriber can never register an open or a click, so counting them only
  // drags the rate down. Older payloads have no trackedSent, so fall back.
  const base = overview.data?.trackedSent ?? overview.data?.sent ?? null;
  if (!data || !base || !data[period] || !base[period]) {
    return 0;
  }

  return (data[period] * 100) / base[period] / 100;
}

/**
 * How much of the audience the rates above rest on. Clamped to 0-100 because
 * the sent counter and the per-recipient rows have different writers and can
 * drift apart.
 */
function getTrackingCoverage(): number | undefined {
  const overview = select(storeName).getSection('overview') as OverviewSection;
  if (overview.data === undefined) {
    return undefined;
  }
  const sent = overview.data.sent?.current ?? 0;
  const tracked = overview.data.trackedSent?.current ?? sent;
  if (sent <= 0) {
    return 0;
  }
  return Math.min(1, tracked / sent);
}

function getNotTrackedCount(): number {
  const overview = select(storeName).getSection('overview') as OverviewSection;
  return overview.data?.notTracked?.current ?? 0;
}

function getEmailDelta(type: 'opened' | 'clicked'): number | undefined {
  const current = getEmailPercentage(type, 'current');
  const previous = getEmailPercentage(type, 'previous');
  if (current === undefined || previous === undefined) {
    return 0;
  }

  if (previous === 0) {
    return 0;
  }

  const newValue = current - previous;
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
  const newValue = current - previous;
  if (newValue === 0 || previous === 0) {
    return 0;
  }
  return (newValue / previous) * 100;
}

export function Overview(): JSX.Element | null {
  const { overview, hasEmails } = useSelect((s) => ({
    overview: s(storeName).getSection('overview'),
    hasEmails: s(editorStoreName).automationHasStep('mailpoet:send-email'),
  })) as { overview: OverviewSection; hasEmails: boolean };

  const percentageFormatter = new Intl.NumberFormat(locale.toString(), {
    style: 'percent',
    maximumFractionDigits: 2,
  });
  const numberFormatter = new Intl.NumberFormat(locale.toString());
  const items: JSX.Element[] = [];
  if (overview.data !== undefined) {
    items.push(
      <SummaryNumber
        key="overview-opened"
        label={__('Opened', 'mailpoet')}
        value={percentageFormatter.format(getEmailPercentage('opened'))}
        delta={Number(getEmailDelta('opened').toFixed(2))}
      />,
    );
    items.push(
      <SummaryNumber
        key="overview-clicked"
        label={__('Clicked', 'mailpoet')}
        value={percentageFormatter.format(getEmailPercentage('clicked'))}
        delta={Number(getEmailDelta('clicked').toFixed(2))}
      />,
    );
    // Only when something is untracked, so an automation with no opted-out
    // recipients looks exactly as it does today.
    if (getNotTrackedCount() > 0) {
      items.push(
        <SummaryNumber
          key="overview-tracking-coverage"
          label={__('Tracking coverage', 'mailpoet')}
          value={percentageFormatter.format(getTrackingCoverage())}
        />,
      );
    }
  }
  if (overview.data !== undefined && MailPoet.isWoocommerceActive) {
    items.push(
      <SummaryNumber
        key="overview-orders"
        label={_x('Orders', 'WooCommerce orders', 'mailpoet')}
        delta={Number(getWooCommerceDelta('orders').toFixed(2))}
        value={numberFormatter.format(getWooCommerceTotal('orders'))}
      />,
    );
    items.push(
      <SummaryNumber
        key="overview-revenue"
        label={__('Revenue', 'mailpoet')}
        delta={Number(getWooCommerceDelta('revenue').toFixed(2))}
        value={formattedPrice(
          overview.data !== undefined ? overview.data.revenue.current : 0,
        )}
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
