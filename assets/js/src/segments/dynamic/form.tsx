import React from 'react';
import {
  assign,
} from 'lodash/fp';

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
  item: AnyFormItem;
  segmentType: FilterValue | undefined;
  onItemChange: (AnyFormItem) => void;
  onSegmentTypeChange: (FilterValue) => void;
  segmentFilters: GroupFilterValue[];
}

export const Form: React.FunctionComponent<Props> = ({
  onSave,
  item,
  segmentType,
  onItemChange,
  onSegmentTypeChange,
  segmentFilters,
}) => (
  <>
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
              defaultValue={item.name}
              onChange={
                (e): void => onItemChange(assign(item, { name: e.target.value }))
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
              defaultValue={item.description}
              onChange={
                (e): void => onItemChange(assign(item, { description: e.target.value }))
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
              onItemChange(assign(item, {
                segmentType: newValue.group,
                action: newValue.value,
              }));
              onSegmentTypeChange(newValue);
            }}
            automationId="select-segment-action"
            isFullWidth
          />
          {segmentType !== undefined && (
            <FormFilterFields
              segmentType={segmentType}
              updateItem={onItemChange}
              item={item}
            />
          )}
        </div>
        <SubscribersCounter item={item} />
        <div className="mailpoet-form-actions">
          <Button type="submit" onClick={onSave} isDisabled={!isFormValid(item)}>
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  </>
);
