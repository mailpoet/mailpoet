import { MailPoet } from 'mailpoet';
import { useSelect } from '@wordpress/data';

import {
  AnyValueTypes,
  EmailActionTypes,
  Segment,
  SubscriberActionTypes,
} from './types';
import { storeName } from './store';
import { SubscriberScoreOperator } from './dynamic-segments-filters/fields/subscriber/subscriber-score';

/**
 * Filters that read open or click data and have no way to tell "did not engage"
 * apart from "we were not allowed to look". A subscriber who opted out of
 * tracking never records an open or a click, so they always land on the
 * not-engaged side of these.
 *
 * "was sent" and "number of emails received" are deliberately absent: those
 * count rows written at send time, which are recorded for everyone regardless
 * of consent.
 */
const engagementActions: string[] = [
  EmailActionTypes.OPENS_ABSOLUTE_COUNT,
  EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
  EmailActionTypes.NUMBER_OF_CLICKS,
  SubscriberActionTypes.SUBSCRIBER_LAST_OPEN_DATE,
  SubscriberActionTypes.SUBSCRIBER_LAST_CLICK_DATE,
  SubscriberActionTypes.SUBSCRIBER_LAST_ENGAGEMENT_DATE,
];

/** Only the "none of" form of these infers disengagement from missing data. */
const actionsAffectedWithNoneOperator: string[] = [
  EmailActionTypes.OPENED,
  EmailActionTypes.MACHINE_OPENED,
  EmailActionTypes.CLICKED,
];

const engagementScoreOperators: string[] = [
  SubscriberScoreOperator.UNKNOWN,
  SubscriberScoreOperator.NOT_UNKNOWN,
  SubscriberScoreOperator.DORMANT,
  SubscriberScoreOperator.NOT_DORMANT,
];

function TrackingConsentNotice(): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );

  const containsEngagementFilter = (segment.filters ?? []).some((formItem) => {
    const action = formItem.action;
    const operator = 'operator' in formItem ? formItem.operator : undefined;
    if (engagementActions.includes(action)) {
      return true;
    }
    if (
      actionsAffectedWithNoneOperator.includes(action) &&
      operator === AnyValueTypes.NONE
    ) {
      return true;
    }
    return (
      action === SubscriberActionTypes.SUBSCRIBER_SCORE &&
      engagementScoreOperators.includes(operator)
    );
  });

  if (!containsEngagementFilter) {
    return <span />;
  }

  return (
    <div className="mailpoet-form-field">
      <span className="mailpoet-form-notice-message">
        {MailPoet.I18n.t('trackingConsentEngagementNotice')}
      </span>
    </div>
  );
}

export { TrackingConsentNotice };
