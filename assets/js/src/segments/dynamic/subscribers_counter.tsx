import React, { useEffect } from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import { isFormValid } from './validator';
import { loadCount } from './subscribers_calculator';

import {
  Segment,
  SubscriberCount,
} from './types';

const SubscribersCounter: React.FunctionComponent = () => {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const subscribersCount: SubscriberCount = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSubscriberCount(),
    []
  );

  const { updateSubscriberCount } = useDispatch('mailpoet-dynamic-segments-form');

  const serializedSegment = JSON.stringify(segment);
  useEffect(() => {
    function load(loadItem: Segment): void {
      updateSubscriberCount({
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
        updateSubscriberCount(finished);
      }, (errorResponse) => {
        const finished = {} as SubscriberCount;
        const errors = errorResponse.errors.map((error) => error.message);
        finished.loading = false;
        finished.count = undefined;
        finished.errors = errors;
        updateSubscriberCount(finished);
      });
    }

    if (isFormValid(segment.filters)) {
      load(segment);
    } else {
      updateSubscriberCount({
        count: undefined,
        loading: false,
      });
    }
  }, [segment, serializedSegment, updateSubscriberCount]);

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
