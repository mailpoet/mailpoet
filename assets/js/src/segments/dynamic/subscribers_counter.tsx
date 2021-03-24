import React from 'react';
import MailPoet from 'mailpoet';

interface SubscriberCount {
  count?: number;
  loading?: boolean;
  errors?: string[];
}

interface Item {
  subscribersCount?: SubscriberCount;
}

interface Props {
  item: Item;
}

const SubscribersCounter: React.FunctionComponent<Props> = ({ item }: Props) => {
  const subscribersCount = item.subscribersCount;
  if (subscribersCount === undefined) {
    return (
      <span />
    );
  }

  if (subscribersCount.errors) {
    return (
      <span className="mailpoet-form-error-message">
        {MailPoet.I18n.t('dynamicSegmentSizeCalculatingTimeout')}
      </span>
    );
  }

  if (!subscribersCount.loading && subscribersCount.count === undefined) {
    return (
      <span />
    );
  }

  if (subscribersCount.loading) {
    return (
      <span className="mailpoet-form-notice-message">
        {MailPoet.I18n.t('dynamicSegmentSizeIsCalculated')}
      </span>
    );
  }

  return (
    <span className="mailpoet-form-notice-message">
      {(MailPoet.I18n.t('dynamicSegmentSize')).replace('%$1d', subscribersCount.count.toLocaleString())}
    </span>
  );
};

export { SubscribersCounter };
