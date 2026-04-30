import { FunctionComponent } from 'react';
import { Icon } from '@wordpress/components';
import { cart, closeSmall, link as linkIcon, seen } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumBannerWithUpgrade } from 'common/premium-banner-with-upgrade/premium-banner-with-upgrade';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';
import type { ActivityEventType } from './activity-shell';

type SampleActivityItem = {
  key: string;
  eventType: Exclude<ActivityEventType, 'all'>;
  icon: JSX.Element;
  title: string;
  description: string;
  date: Date;
};

type ActivityGroup = {
  key: string;
  label: string;
  items: SampleActivityItem[];
};

type Props = {
  eventType: ActivityEventType;
};

function getSampleDate(dayOffset: number, hoursOffset = 0): Date {
  const date = new Date();
  date.setDate(date.getDate() - dayOffset);
  date.setHours(Math.max(0, date.getHours() - hoursOffset));
  return date;
}

function getSampleActivityItems(): SampleActivityItem[] {
  return [
    {
      key: 'opened',
      eventType: 'open',
      icon: seen,
      title: __('Opened email “Spring sale newsletter”', 'mailpoet'),
      description: __('Opened the email on desktop.', 'mailpoet'),
      date: getSampleDate(0, 2),
    },
    {
      key: 'clicked',
      eventType: 'click',
      icon: linkIcon,
      title: __('Clicked a link in email “Spring sale newsletter”', 'mailpoet'),
      description: __('https://example.com/spring-collection', 'mailpoet'),
      date: getSampleDate(0, 3),
    },
    {
      key: 'purchased',
      eventType: 'purchase',
      icon: cart,
      title: __('Completed purchase', 'mailpoet'),
      description: __('Revenue tracked from an email click.', 'mailpoet'),
      date: getSampleDate(1),
    },
    {
      key: 'unsubscribed',
      eventType: 'unsubscribe',
      icon: closeSmall,
      title: __('Unsubscribed', 'mailpoet'),
      description: __('Reason: No reason provided', 'mailpoet'),
      date: getSampleDate(1, 2),
    },
  ];
}

function getDateKey(date: Date): string {
  return MailPoet.Date.format(date, { format: 'Y-m-d' });
}

function getGroupLabel(date: Date): string {
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(today.getDate() - 1);

  const dateKey = getDateKey(date);
  if (dateKey === getDateKey(today)) {
    return __('Today', 'mailpoet');
  }
  if (dateKey === getDateKey(yesterday)) {
    return __('Yesterday', 'mailpoet');
  }
  return MailPoet.Date.short(date);
}

function groupActivityItems(items: SampleActivityItem[]): ActivityGroup[] {
  return items.reduce<ActivityGroup[]>((groups, item) => {
    const groupKey = getDateKey(item.date);
    const currentGroup = groups.find((group) => group.key === groupKey);
    if (currentGroup) {
      currentGroup.items.push(item);
      return groups;
    }
    groups.push({
      key: groupKey,
      label: getGroupLabel(item.date),
      items: [item],
    });
    return groups;
  }, []);
}

function SampleActivityRow({
  item,
}: {
  item: SampleActivityItem;
}): JSX.Element {
  return (
    <li className="mailpoet-subscriber-stats-activity-item">
      <span
        className="mailpoet-subscriber-stats-activity-icon"
        aria-hidden="true"
      >
        <Icon icon={item.icon} />
      </span>
      <div className="mailpoet-subscriber-stats-activity-item-content">
        <div className="mailpoet-subscriber-stats-activity-item-main">
          <div>
            <div className="mailpoet-subscriber-stats-activity-item-title">
              {item.title}
            </div>
            <div className="mailpoet-subscriber-stats-activity-item-description">
              {item.description}
            </div>
          </div>
          <time
            className="mailpoet-subscriber-stats-activity-item-date"
            dateTime={item.date.toISOString()}
          >
            {MailPoet.Date.full(item.date)}
          </time>
        </div>
      </div>
    </li>
  );
}

function SampleActivityGroup({ group }: { group: ActivityGroup }): JSX.Element {
  return (
    <li className="mailpoet-subscriber-stats-activity-day">
      <h3 className="mailpoet-subscriber-stats-activity-day-title">
        {group.label}
      </h3>
      <ul className="mailpoet-subscriber-stats-activity-list">
        {group.items.map((item) => (
          <SampleActivityRow item={item} key={item.key} />
        ))}
      </ul>
    </li>
  );
}

export function NoAccessInfo({ eventType }: Props): JSX.Element {
  const activityItems = getSampleActivityItems()
    .filter((item) => eventType === 'all' || item.eventType === eventType)
    .sort((first, second) => second.date.getTime() - first.date.getTime());
  const groups = groupActivityItems(activityItems);

  const getBannerMessage: FunctionComponent = () => {
    const message = __(
      'Learn more about how each of your subscribers is engaging with your emails. See which emails they’ve opened, the links they clicked. If you’re a WooCommerce store owner, you’ll also see any purchases made as a result of your emails. [link]Learn more[/link].',
      'mailpoet',
    );
    return (
      <p>
        {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
          <a
            key={match}
            href={MailPoet.premiumLink}
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ))}
      </p>
    );
  };
  const getCtaButton: FunctionComponent = () => (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
        MailPoet.subscribersCount,
        MailPoet.currentWpUserEmail,
        null,
        { utm_medium: 'stats', utm_campaign: 'signup' },
      )}
      target="_blank"
      rel="noopener noreferrer"
    >
      {__('Upgrade', 'mailpoet')}
    </Button>
  );

  return (
    <div
      className="mailpoet-subscriber-stats-locked-activity"
      data-automation-id="subscriber-stats-no-access"
    >
      <ul
        className="mailpoet-subscriber-stats-activity-days mailpoet-subscriber-stats-activity-list-sample"
        aria-label={__('Sample subscriber activity', 'mailpoet')}
      >
        {groups.map((group) => (
          <SampleActivityGroup group={group} key={group.key} />
        ))}
      </ul>
      <div className="mailpoet-subscriber-stats-no-access-content">
        <PremiumBannerWithUpgrade
          message={getBannerMessage({})}
          actionButton={getCtaButton({})}
          capabilities={{ detailedAnalytics: true }}
        />
      </div>
    </div>
  );
}
