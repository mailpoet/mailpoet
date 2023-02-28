import { Fragment, FunctionComponent } from 'react';
import { Grid } from '../../common/grid';
import { ReactSelect } from '../../common/form/react_select/react_select';
import { MailPoet } from '../../mailpoet';
import { FilterRow, FilterValue, GroupFilterValue, Segment } from './types';
import { FormFilterFields } from './form_filter_fields';
import { Button } from '../../common';
import { plusIcon } from '../../common/button/icon/plus';
import { Hooks } from 'wp-js-hooks';
import { useDispatch, useSelect } from '@wordpress/data';

const FiltersBefore = Hooks.applyFilters(
  'mailpoet_dynamic_segments_form_filters_before',
  (): FunctionComponent => null,
);

const FilterBefore = Hooks.applyFilters(
  'mailpoet_dynamic_filters_filter_before',
  (): FunctionComponent => null,
);

const FilterAfter = Hooks.applyFilters(
  'mailpoet_dynamic_filters_filter_after',
  (): JSX.Element => <div className="mailpoet-gap" />,
);

type Props = {
  onSubmit: (segment: Segment) => void;
};

export function FormBody({ onSubmit }: Props): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    [],
  );

  const segmentFilters: GroupFilterValue[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getAvailableFilters(),
    [],
  );

  const filterRows: FilterRow[] = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').findFiltersValueForSegment(
        segment,
      ),
    [segment],
  );

  const { updateSegment, updateSegmentFilter } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  /*const showPremiumBanner = (): void => {
    setPremiumBannerVisible(true);
  };*/

  const addConditionAction = Hooks.applyFilters(
    'mailpoet_dynamic_segments_form_add_condition_action',
    () => {},
  );

  return (
    <>
      <FiltersBefore />
      {Array.isArray(filterRows) &&
        filterRows.map((filterRow, index) => (
          <Fragment key={filterRow.index}>
            <Grid.ThreeColumns
              className="mailpoet-segments-grid"
              automationId={`filter-row-${index}`}
            >
              <FilterBefore filterRows={filterRows} index={index} />
              <Grid.CenteredRow>
                <ReactSelect
                  dimension="small"
                  placeholder={MailPoet.I18n.t('selectActionPlaceholder')}
                  options={segmentFilters}
                  value={filterRow.filterValue}
                  onChange={(newValue: FilterValue): void => {
                    void updateSegmentFilter(
                      {
                        segmentType: newValue.group,
                        action: newValue.value,
                      },
                      index,
                    );
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
          </Fragment>
        ))}
      <Button
        type="button"
        variant="tertiary"
        iconStart={plusIcon}
        onClick={(e): void => {
          e.preventDefault();
          addConditionAction(segment, updateSegment);
        }}
      >
        {MailPoet.I18n.t('addCondition')}
      </Button>
    </>
  );
}
