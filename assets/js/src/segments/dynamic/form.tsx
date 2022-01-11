import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import Hooks from 'wp-js-hooks';
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
import plusIcon from '../../common/button/icon/plus';
import APIErrorsNotice from '../../notices/api_errors_notice';
import { PrivacyProtectionNotice } from './privacy_protection_notice';

import {
  FilterRow,
  FilterValue,
  GroupFilterValue,
  Segment,
  SegmentTypes,
  SubscriberActionTypes,
  SubscriberCount,
} from './types';

interface Props {
  segmentId?: number;
}

const FiltersBefore = Hooks.applyFilters('mailpoet_dynamic_segments_form_filters_before', (): React.FunctionComponent => null);
const FilterBefore = Hooks.applyFilters('mailpoet_dynamic_filters_filter_before', (): React.FunctionComponent => null);
const FilterAfter = Hooks.applyFilters('mailpoet_dynamic_filters_filter_after', (): JSX.Element => (
  <div className="mailpoet-gap" />
));

export const Form: React.FunctionComponent<Props> = ({
  segmentId,
}) => {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const segmentFilters: GroupFilterValue[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getAvailableFilters(),
    []
  );

  const filterRows: FilterRow[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').findFiltersValueForSegment(segment),
    [segment]
  );

  const subscriberCount: SubscriberCount = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSubscriberCount(),
    []
  );

  const errors: string[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getErrors(),
    []
  );

  const { updateSegment, updateSegmentFilter, handleSave } = useDispatch('mailpoet-dynamic-segments-form');

  return (
    <form className="mailpoet_form">
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors.map((error) => ({ message: error }))} />
      ))}
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
              value={segment.description}
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
          <FiltersBefore />
          {Array.isArray(filterRows) && filterRows.map((filterRow, index) => (
            <React.Fragment key={filterRow.index}>
              <Grid.ThreeColumns automationId={`filter-row-${index}`}>
                <FilterBefore filterRows={filterRows} index={index} />
                <Grid.CenteredRow>
                  <Select
                    dimension="small"
                    placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
                    options={segmentFilters}
                    value={filterRow.filterValue}
                    onChange={(newValue: FilterValue): void => {
                      updateSegmentFilter({
                        segmentType: newValue.group,
                        action: newValue.value,
                      }, index);
                    }}
                    automationId="select-segment-action"
                    isFullWidth
                  />
                </Grid.CenteredRow>
                {filterRow.index !== undefined && (
                  <FormFilterFields filterIndex={filterRow.index} />
                )}
              </Grid.ThreeColumns>
              <FilterAfter index={index} />
            </React.Fragment>
          ))}
          <Button
            type="button"
            variant="tertiary"
            iconStart={plusIcon}
            onClick={(e): void => {
              e.preventDefault();
              const filters = segment.filters;
              filters.push({
                segmentType: SegmentTypes.WordPressRole,
                action: SubscriberActionTypes.WORDPRESS_ROLE,
              });
              updateSegment({
                filters,
              });
            }}
          >
            {MailPoet.I18n.t('addCondition')}
          </Button>
        </div>
        <div className="mailpoet-segments-counter-section">
          <SubscribersCounter />
          <PrivacyProtectionNotice />
        </div>
        <div className="mailpoet-form-actions">
          <Button
            type="submit"
            onClick={(e): void => {
              e.preventDefault();
              handleSave(segmentId);
            }}
            isDisabled={!isFormValid(segment.filters) || subscriberCount.count === undefined}
          >
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  );
};
