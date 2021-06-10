import React from 'react';
import ReactStringReplace from 'react-string-replace';
import { useDispatch, useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import {
  Segment,
  SegmentConnectTypes,
} from './types';

const ConditionType: React.FunctionComponent = () => {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  if (segment.filters.length <= 1) {
    return null;
  }

  return (
    <>
      <div>
        <label className="mailpoet-form-radio" data-automation-id="dynamic-segment-condition-type-and">
          <input
            type="radio"
            value={SegmentConnectTypes.AND}
            checked={segment.filters_connect === SegmentConnectTypes.AND}
            onChange={
              (e): void => updateSegment({ filters_connect: e.target.value })
            }
          />
          <span className="mailpoet-form-radio-control" />
          <span>
            {ReactStringReplace(
              MailPoet.I18n.t('allConditions'),
              /<strong>(.*?)<\/strong>/g,
              (match, i) => <strong key={i}>{match}</strong>
            )}
          </span>
        </label>
        <label className="mailpoet-form-radio" data-automation-id="dynamic-segment-condition-type-or">
          <input
            type="radio"
            value={SegmentConnectTypes.OR}
            checked={segment.filters_connect === SegmentConnectTypes.OR}
            onChange={
              (e): void => updateSegment({ filters_connect: e.target.value })
            }
          />
          <span className="mailpoet-form-radio-control" />
          <span>
            {ReactStringReplace(
              MailPoet.I18n.t('anyConditions'),
              /<strong>(.*?)<\/strong>/g,
              (match, i) => <strong key={i}>{match}</strong>
            )}
          </span>
        </label>
      </div>
      <div className="mailpoet-gap" />
    </>
  );
};

export { ConditionType };
