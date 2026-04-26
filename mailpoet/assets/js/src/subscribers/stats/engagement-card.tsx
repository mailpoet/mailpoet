import { useMemo, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
  Card,
  CardBody,
  CardHeader,
  Flex,
  FlexBlock,
  SelectControl,
} from '@wordpress/components';
import { Tag } from 'common/tag';
import { StatsBadge } from 'common/listings/newsletter-stats/stats';
import {
  getEngagementScoreBadgeType,
  EngagementScoreBadgeType,
} from '../listings-engagement-score';
import { PeriodicStats, StatsPeriodKey, StatsType } from '../types';

type Props = {
  stats: StatsType;
};

const PERIOD_OPTIONS: Array<{ label: string; value: StatsPeriodKey }> = [
  { label: __('7 days', 'mailpoet'), value: '7_days' },
  { label: __('30 days', 'mailpoet'), value: '30_days' },
  { label: __('3 months', 'mailpoet'), value: '3_months' },
  { label: __('12 months', 'mailpoet'), value: '12_months' },
];

function getEngagementBadgeLabel(type: EngagementScoreBadgeType): string {
  const labels = {
    unknown: __('Unknown', 'mailpoet'),
    average: __('Average', 'mailpoet'),
    good: __('Good', 'mailpoet'),
    excellent: __('Excellent', 'mailpoet'),
  };
  return labels[type];
}

function getStatsForPeriod(
  periodicStats: PeriodicStats[],
  period: StatsPeriodKey,
): PeriodicStats | undefined {
  return periodicStats.find((item) => item.key === period);
}

function getRate(value: number, total: number): number {
  if (total <= 0) {
    return 0;
  }
  return (value / total) * 100;
}

function formatPercentage(rate: number): string {
  const rounded = Math.round(rate * 10) / 10;
  const precision = Number.isInteger(rounded) ? 0 : 1;
  return `${rounded.toLocaleString(undefined, {
    minimumFractionDigits: precision,
    maximumFractionDigits: 1,
  })}%`;
}

function formatCount(count: number): string {
  return Intl.NumberFormat(undefined, {
    maximumFractionDigits: 1,
    notation: 'compact',
  }).format(count);
}

function MetricValue({
  stats,
  type,
}: {
  stats: PeriodicStats;
  type: 'sent' | 'opened' | 'clicked';
}): JSX.Element {
  if (type === 'sent') {
    return <>{formatCount(stats.total_sent)}</>;
  }

  const count = type === 'opened' ? stats.open : stats.click;
  const rate = getRate(count, stats.total_sent);
  const value = formatPercentage(rate);

  if (type === 'clicked') {
    return (
      <span className="mailpoet-subscriber-stats-engagement-value-with-badge">
        <StatsBadge
          stat="clicked"
          rate={rate}
          tooltipId={`subscriber-stats-clicked-${stats.key}`}
        />
        <span>{value}</span>
      </span>
    );
  }

  return <>{value}</>;
}

export function EngagementCard({ stats }: Props): JSX.Element | null {
  const [selectedPeriod, setSelectedPeriod] =
    useState<StatsPeriodKey>('30_days');

  const lifetimeStats = useMemo(
    () => getStatsForPeriod(stats.periodic_stats, 'lifetime'),
    [stats.periodic_stats],
  );
  const selectedStats = useMemo(
    () =>
      getStatsForPeriod(stats.periodic_stats, selectedPeriod) ||
      getStatsForPeriod(stats.periodic_stats, '30_days') ||
      stats.periodic_stats[0],
    [selectedPeriod, stats.periodic_stats],
  );

  if (!selectedStats || !lifetimeStats) {
    return null;
  }

  const engagementBadgeType = getEngagementScoreBadgeType(
    stats.engagement_score,
  );
  const rows: Array<{
    label: string;
    type: 'sent' | 'opened' | 'clicked';
  }> = [
    { label: __('Emails sent', 'mailpoet'), type: 'sent' },
    { label: __('Opened', 'mailpoet'), type: 'opened' },
    { label: __('Clicked', 'mailpoet'), type: 'clicked' },
  ];

  return (
    <Card className="mailpoet-subscriber-stats-card" size="medium">
      <CardHeader className="mailpoet-subscriber-stats-card-header">
        <Flex align="center" gap={3}>
          <FlexBlock>
            <div className="mailpoet-subscriber-stats-card-title-row">
              <h2 className="mailpoet-subscriber-stats-card-title">
                {__('Engagement', 'mailpoet')}
              </h2>
              <Tag
                className="mailpoet-subscriber-stats-engagement-badge"
                variant={engagementBadgeType}
              >
                {getEngagementBadgeLabel(engagementBadgeType)}
              </Tag>
            </div>
          </FlexBlock>
          <SelectControl
            className="mailpoet-subscriber-stats-period-control"
            hideLabelFromVision
            label={__('Engagement period', 'mailpoet')}
            onChange={(period) => setSelectedPeriod(period as StatsPeriodKey)}
            options={PERIOD_OPTIONS}
            value={selectedPeriod}
          />
        </Flex>
      </CardHeader>
      <CardBody>
        <table className="mailpoet-subscriber-stats-engagement-table">
          <thead>
            <tr>
              <th>
                <span className="screen-reader-text">
                  {__('Engagement metric', 'mailpoet')}
                </span>
              </th>
              <th>{selectedStats.timeframe}</th>
              <th>{lifetimeStats.timeframe}</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.type}>
                <th scope="row">{row.label}</th>
                <td>
                  <MetricValue stats={selectedStats} type={row.type} />
                </td>
                <td>
                  <MetricValue stats={lifetimeStats} type={row.type} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </CardBody>
    </Card>
  );
}

EngagementCard.displayName = 'EngagementCard';
