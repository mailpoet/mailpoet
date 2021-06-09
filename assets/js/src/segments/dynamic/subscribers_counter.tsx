import React, { useEffect, useState } from 'react';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import { isFormValid } from './validator';
import { loadCount } from './subscribers_calculator';

import {
  Segment,
} from './types';

interface SubscriberCount {
  count?: number;
  loading?: boolean;
  errors?: string[];
}

const SubscribersCounter: React.FunctionComponent = () => {
  const [subscribersCount, setSubscribersCount] = useState<SubscriberCount>({
    loading: false,
    count: undefined,
    errors: undefined,
  });

  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const serializedSegment = JSON.stringify(segment);
  useEffect(() => {
    function load(loadItem: Segment): void {
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

    if (isFormValid(segment.filters)) {
      load(segment);
    } else {
      setSubscribersCount({
        count: undefined,
        loading: false,
      });
    }
  }, [segment, serializedSegment]);

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
