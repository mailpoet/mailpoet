import React, { useEffect, useState } from 'react';
import MailPoet from 'mailpoet';
import { validateEmail } from './dynamic_segments_filters/email';
import { validateWooCommerce } from './dynamic_segments_filters/woocommerce';
import { validateWordpressRole } from './dynamic_segments_filters/wordpress_role';
import { validateWooCommerceSubscription } from './dynamic_segments_filters/woocommerce_subscription';

import { loadCount } from './subscribers_calculator';

import {
  AnyFormItem,
  SegmentTypes,
} from './types';

interface SubscriberCount {
  count?: number;
  loading?: boolean;
  errors?: string[];
}

interface Props {
  item: AnyFormItem;
}

const validationMap = {
  [SegmentTypes.Email]: validateEmail,
  [SegmentTypes.WooCommerce]: validateWooCommerce,
  [SegmentTypes.WordPressRole]: validateWordpressRole,
  [SegmentTypes.WooCommerceSubscription]: validateWooCommerceSubscription,
};

function isFormValid(item: AnyFormItem): boolean {
  if (validationMap[item.segmentType] === undefined) return false;
  return validationMap[item.segmentType](item);
}

const SubscribersCounter: React.FunctionComponent<Props> = ({ item }: Props) => {
  const [subscribersCount, setSubscribersCount] = useState<SubscriberCount>({
    loading: false,
    count: undefined,
    errors: undefined,
  });

  useEffect(() => {
    function load(loadItem: AnyFormItem): void {
      setSubscribersCount({
        loading: true,
        count: undefined,
        errors: undefined,
      });

      loadCount(loadItem).then((response) => {
        const finished = {} as SubscriberCount;
        finished.loading = false;
        if (response) {
          finished.count = response.count;
          finished.errors = response.errors;
        }
        setSubscribersCount(finished);
      }, (errorResponse) => {
        const finished = {} as SubscriberCount;
        const errors = errorResponse.errors.map((error) => error.message);
        finished.loading = false;
        finished.count = undefined;
        finished.errors = errors;
        setSubscribersCount(finished);
      });
    }

    if (isFormValid(item)) {
      load(item);
    } else {
      setSubscribersCount({
        count: undefined,
        loading: false,
      });
    }
  }, [item]);

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
