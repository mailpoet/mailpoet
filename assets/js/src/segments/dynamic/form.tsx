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
} from './types';

interface Props {
  onSave: (Event) => void;
  segmentType: FilterValue | undefined;
  onSegmentTypeChange: (FilterValue) => void;
  segmentFilters: GroupFilterValue[];
}

export const Form: React.FunctionComponent<Props> = ({
  onSave,
  segmentType,
  onSegmentTypeChange,
  segmentFilters,
}) => {
  const segment: AnyFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

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
            placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
            options={segmentFilters}
            value={segmentType}
            onChange={(newValue: FilterValue): void => {
              updateSegment({
                segmentType: newValue.group,
                action: newValue.value,
              });
              onSegmentTypeChange(newValue);
            }}
            automationId="select-segment-action"
            isFullWidth
          />
          {segmentType !== undefined && (
            <FormFilterFields
              segmentType={segmentType}
            />
          )}
        </div>
        <SubscribersCounter />
        <div className="mailpoet-form-actions">
          <Button type="submit" onClick={onSave} isDisabled={!isFormValid(segment)}>
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  );
};
