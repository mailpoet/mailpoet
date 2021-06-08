import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import Select from 'common/form/react_select/react_select';
import Textarea from 'common/form/textarea/textarea';
import { Grid } from 'common/grid';
import { ConditionType } from './condition_type';
import { FilterSeparator } from './filter_separator';
import { SubscribersCounter } from './subscribers_counter';
import { FormFilterFields } from './form_filter_fields';
import { isFormValid } from './validator';
import { MinusIcon } from '../../common/button/icon/minus';
import plusIcon from '../../common/button/icon/plus';

import {
  FilterRow,
  FilterValue,
  GroupFilterValue,
  Segment,
  SegmentTypes,
  SubscriberActionTypes,
} from './types';

interface Props {
  segmentId?: number;
}

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

  const { updateSegment, updateSegmentFilter, handleSave } = useDispatch('mailpoet-dynamic-segments-form');

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
          <ConditionType />
          {Array.isArray(filterRows) && filterRows.map((filterRow, index) => (
            <>
              <Grid.ThreeColumns>
                {filterRows.length > 1 && (
                  <a
                    href={undefined}
                    className="mailpoet-form-segment-delete"
                    onClick={(e) => {
                      e.preventDefault();
                      const filters = segment.filters;
                      filters.splice(index, 1);
                      updateSegment({
                        filters,
                      });
                    }}
                  >
                    {MinusIcon}
                  </a>
                )}
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
                {filterRow.index !== undefined && (
                  <FormFilterFields filterIndex={filterRow.index} />
                )}
              </Grid.ThreeColumns>
              <FilterSeparator index={index} />
            </>
          ))}
          <Button
            type="button"
            dimension="small"
            variant="link"
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
        <SubscribersCounter />
        <div className="mailpoet-form-actions">
          <Button
            type="submit"
            onClick={(e): void => {
              e.preventDefault();
              handleSave(segmentId);
            }}
            isDisabled={!isFormValid(segment.filters)}
          >
            {MailPoet.I18n.t('save')}
          </Button>
        </div>
      </div>
    </form>
  );
};
