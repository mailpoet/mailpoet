import { MailPoet } from 'mailpoet';
import { useSelect } from '@wordpress/data';

import {
  AnyValueTypes,
  EmailActionTypes,
  Segment,
  SubscriberActionTypes,
} from './types';
import { storeName } from './store';

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

/**
 * These read the same missing data, but which way it misleads depends on the
 * operator, so the two forms get different wording.
 *
 * "none of" infers disengagement: an opted-out subscriber never recorded an
 * open or click, so they are pulled in and described as not engaged — a wrong
 * statement about a real person.
 *
 * "any of" / "all of" only omits them: nobody is mislabelled, but the audience
 * is quietly smaller than the merchant expects, which is worth saying plainly.
 */
const actionsAffectedByOperator: string[] = [
  EmailActionTypes.OPENED,
  EmailActionTypes.MACHINE_OPENED,
  EmailActionTypes.CLICKED,
];

function TrackingConsentNotice(): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );

  const filters = segment.filters ?? [];

  const countsThemAsNotEngaged = filters.some((formItem) => {
    if (formItem.onlyTrackable) {
      return false;
    }
    const action = formItem.action;
    const operator = 'operator' in formItem ? formItem.operator : undefined;
    if (engagementActions.includes(action)) {
      return true;
    }
    return (
      actionsAffectedByOperator.includes(action) &&
      operator === AnyValueTypes.NONE
    );
  });

  // Subscribers we cannot track have no score, so every score operator puts
  // them in the Unknown or Dormant group.
  const usesEngagementScore = filters.some(
    (formItem) => formItem.action === SubscriberActionTypes.SUBSCRIBER_SCORE,
  );

  const leavesThemOut = filters.some((formItem) => {
    const operator =
      'operator' in formItem ? formItem.operator : AnyValueTypes.ANY;
    return (
      actionsAffectedByOperator.includes(formItem.action) &&
      operator !== AnyValueTypes.NONE
    );
  });

  const messages: string[] = [];
  // The stronger warning wins when a segment has both, rather than stacking two
  // notices that say overlapping things.
  if (countsThemAsNotEngaged) {
    messages.push('trackingConsentEngagementNotice');
  } else if (leavesThemOut) {
    messages.push('trackingConsentOmittedNotice');
  }
  if (usesEngagementScore) {
    messages.push('trackingConsentScoreNotice');
  }

  if (messages.length === 0) {
    return <span />;
  }

  return (
    <div className="mailpoet-form-field">
      {messages.map((message) => (
        <div key={message} className="mailpoet-form-notice-message">
          {MailPoet.I18n.t(message)}
        </div>
      ))}
    </div>
  );
}

export { TrackingConsentNotice };
