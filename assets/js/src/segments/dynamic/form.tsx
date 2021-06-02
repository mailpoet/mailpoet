import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import Select from 'common/form/react_select/react_select';
import Textarea from 'common/form/textarea/textarea';
import { Grid } from 'common/grid';
import { SubscribersCounter } from './subscribers_counter';
import { FormFilterFields } from './form_filter_fields';
import { isFormValid } from './validator';

import {
  AnyFormItem,
  FilterValue,
  GroupFilterValue,
} from './types';

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

  const segmentFilters: GroupFilterValue[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getAvailableFilters(),
    []
  );

  const filterValue: FilterValue | undefined = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').findFilterValueForSegment(segment),
    [segment]
  );

  const { updateSegment, handleSave } = useDispatch('mailpoet-dynamic-segments-form');

  return (
    <form className="mailpoet_form">
      <div className="mailpoet-form-grid">
        <div className="mailpoet-form-field-name form-field-row-name mailpoet-segments-name-section">
          <Heading level={4}>
            <label htmlFor="field_name">
              {MailPoet.I18n.t('name')}
            </label>
          </Heading>
          <div className="mailpoet-form-field">
            <Input
              isFullWidth
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
        <div className="mailpoet-form-field-description form-field-row-description mailpoet-segments-description-section">
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
              isFullWidth
              name="description"
              id="field_description"
              defaultValue={segment.description}
              onChange={
                (e): void => updateSegment({ description: e.target.value })
              }
            />
          </div>
        </div>
        <div className="mailpoet-segments-segments-section">
          <Heading level={4}>
            <label htmlFor="field_filters">
              {MailPoet.I18n.t('formPageTitle')}
            </label>
          </Heading>
          <Grid.ThreeColumns>
            <Select
              dimension="small"
              placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
              options={segmentFilters}
              value={filterValue}
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
          </Grid.ThreeColumns>
        </div>
        <SubscribersCounter />
        <div className="mailpoet-form-actions">
          <Button
            type="submit"
            onClick={(e): void => {
              e.preventDefault();
              handleSave(segmentId);
            }}
            isDisabled={!isFormValid(segment)}
          >
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  );
};
