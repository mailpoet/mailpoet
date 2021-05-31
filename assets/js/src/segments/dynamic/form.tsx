import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import Select from 'common/form/react_select/react_select';
import Textarea from 'common/form/textarea/textarea';
import { SubscribersCounter } from './subscribers_counter';
import { FormFilterFields } from './form_filter_fields';
import { isFormValid } from './validator';

import {
  AnyFormItem,
  FilterValue,
  GroupFilterValue,
  SubscriberActionTypes,
} from './types';
import { SubscriberSegmentOptions } from './dynamic_segments_filters/subscriber';
import { getAvailableFilters } from './all_available_filters';

interface Props {
  segmentId?: number;
}

export const Form: React.FunctionComponent<Props> = ({
  segmentId,
}) => {
  const segment: AnyFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const canUseWooSubscriptions: boolean = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').canUseWooSubscriptions(),
    []
  );

  const { updateSegment, handleSave } = useDispatch('mailpoet-dynamic-segments-form');

  function findSegmentType(itemSearch): FilterValue | undefined {
    let found: FilterValue | undefined;
    if (itemSearch.action === undefined) {
      // bc compatibility, the wordpress user role segment doesn't have action
      return SubscriberSegmentOptions.find(
        (value) => value.value === SubscriberActionTypes.WORDPRESS_ROLE
      );
    }

    segmentFilters.forEach((filter: GroupFilterValue) => {
      filter.options.forEach((option: FilterValue) => {
        if (option.group === itemSearch.segmentType) {
          if (itemSearch.action === option.value) {
            found = option;
          }
        }
      });
    });
    return found;
  }

  const segmentFilters = getAvailableFilters(canUseWooSubscriptions);

  return (
    <form className="mailpoet_form">
      <div className="mailpoet-form-grid">
        <div className="mailpoet-form-field-name form-field-row-name">
          <Heading level={4}>
            <label htmlFor="field_name">
              {MailPoet.I18n.t('name')}
            </label>
          </Heading>
          <div className="mailpoet-form-field">
            <Input
              type="text"
              name="name"
              id="field_name"
              defaultValue={segment.name}
              onChange={
                (e): void => updateSegment({ name: e.target.value })
              }
            />
          </div>
        </div>
        <div className="mailpoet-form-field-description form-field-row-description">
          <Heading level={4}>
            <label htmlFor="field_description">
              {MailPoet.I18n.t('description')}
            </label>
          </Heading>
          <p className="mailpoet-form-description">
            {MailPoet.I18n.t('segmentDescriptionTip')}
          </p>
          <div className="mailpoet-form-field">
            <Textarea
              name="description"
              id="field_description"
              defaultValue={segment.description}
              onChange={
                (e): void => updateSegment({ description: e.target.value })
              }
            />
          </div>
        </div>
        <div>
          <Heading level={4}>
            <label htmlFor="field_filters">
              {MailPoet.I18n.t('formPageTitle')}
            </label>
          </Heading>
          <Select
            dimension="small"
            placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
            options={segmentFilters}
            value={findSegmentType(segment)}
            onChange={(newValue: FilterValue): void => {
              updateSegment({
                segmentType: newValue.group,
                action: newValue.value,
              });
            }}
            automationId="select-segment-action"
            isFullWidth
          />
          {segment.segmentType !== undefined && (
            <FormFilterFields />
          )}
        </div>
        <SubscribersCounter />
        <div className="mailpoet-form-actions">
          <Button type="submit" onClick={(): void => { handleSave(segmentId); }} isDisabled={!isFormValid(segment)}>
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  );
};
