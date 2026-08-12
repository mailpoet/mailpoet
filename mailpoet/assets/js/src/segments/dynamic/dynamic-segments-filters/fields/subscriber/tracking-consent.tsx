import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';

import {
  FilterProps,
  TrackingConsentOperators,
  TrackingConsentValues,
  WordpressRoleFormItem,
} from '../../../types';
import { storeName } from '../../../store';

const availableOperators: string[] = [
  TrackingConsentOperators.IS,
  TrackingConsentOperators.IS_NOT,
];

const availableValues: string[] = [
  TrackingConsentValues.GRANTED,
  TrackingConsentValues.DENIED,
  TrackingConsentValues.UNKNOWN,
];

export function validateTrackingConsent(
  formItems: WordpressRoleFormItem,
): boolean {
  return (
    availableOperators.includes(formItems.operator) &&
    availableValues.includes(formItems.value)
  );
}

export function TrackingConsentFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  useEffect(() => {
    if (!availableOperators.includes(segment.operator)) {
      void updateSegmentFilter(
        { operator: TrackingConsentOperators.IS },
        filterIndex,
      );
    }
    if (!availableValues.includes(segment.value)) {
      void updateSegmentFilter(
        { value: TrackingConsentValues.DENIED },
        filterIndex,
      );
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Select
        key="operator"
        isMinWidth
        value={segment.operator}
        automationId="segment-tracking-consent-operator"
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={TrackingConsentOperators.IS}>
          {MailPoet.I18n.t('is')}
        </option>
        <option value={TrackingConsentOperators.IS_NOT}>
          {MailPoet.I18n.t('isNot')}
        </option>
      </Select>
      <Select
        key="value"
        isMinWidth
        value={segment.value}
        automationId="segment-tracking-consent-value"
        onChange={(e) => {
          void updateSegmentFilterFromEvent('value', filterIndex, e);
        }}
      >
        <option value={TrackingConsentValues.GRANTED}>
          {MailPoet.I18n.t('trackingConsentGranted')}
        </option>
        <option value={TrackingConsentValues.DENIED}>
          {MailPoet.I18n.t('trackingConsentDenied')}
        </option>
        <option value={TrackingConsentValues.UNKNOWN}>
          {MailPoet.I18n.t('trackingConsentUnknown')}
        </option>
      </Select>
    </>
  );
}
