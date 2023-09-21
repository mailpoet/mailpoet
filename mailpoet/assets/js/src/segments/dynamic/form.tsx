import { Fragment, FunctionComponent, useState } from 'react';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Hooks } from 'wp-js-hooks';
import { Input } from 'common/form/input/input';
import { ReactSelect } from 'common/form/react-select/react-select';
import { Grid } from 'common/grid';
import { APIErrorsNotice } from 'notices/api-errors-notice';
import { SubscribersCounter } from './subscribers-counter';
import { FormFilterFields } from './form-filter-fields';
import { isFormValid } from './validator';
import { PrivacyProtectionNotice } from './privacy-protection-notice';
import { storeName } from './store';

import {
  FilterRow,
  FilterValue,
  GroupFilterValue,
  Segment,
  SubscriberCount,
} from './types';
import { FieldsSection } from '../../common/fields_section/fields_section';
import { FieldWrapper } from '../../common/fields_section/field_wrapper';
import { MailPoet } from '../../mailpoet';
import { LockedBadge } from '../../common/premium-modal/locked-badge';
import { PremiumModal } from '../../common/premium-modal';

interface Props {
  isNewSegment: boolean;
}

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

export function Form({ isNewSegment }: Props): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );

  const segmentFilters: GroupFilterValue[] = useSelect(
    (select) => select(storeName).getAvailableFilters(),
    [],
  );

  const filterRows: FilterRow[] = useSelect(
    (select) => select(storeName).findFiltersValueForSegment(segment),
    [segment],
  );

  const subscriberCount: SubscriberCount = useSelect(
    (select) => select(storeName).getSubscriberCount(),
    [],
  );

  const errors: string[] = useSelect(
    (select) => select(storeName).getErrors(),
    [],
  );

  const { updateSegment, updateSegmentFilter, handleSave } =
    useDispatch(storeName);

  const [showPremiumModal, setShowPremiumModal] = useState(false);
  const showPremiumModalOnClick = (): void => {
    setShowPremiumModal(true);
  };

  const closePremiumModal = (): void => {
    setShowPremiumModal(false);
  };

  const addConditionAction = Hooks.applyFilters(
    'mailpoet_dynamic_segments_form_add_condition_action',
    showPremiumModalOnClick,
  );

  return (
    <div className="mailpoet-form-container">
      <form>
        {errors.length > 0 && (
          <APIErrorsNotice
            errors={errors.map((error) => ({ message: error }))}
          />
        )}
        <FieldsSection
          title={__('Details', 'mailpoet')}
          description={__('What do you want to call this segment?', 'mailpoet')}
        >
          <FieldWrapper slug="name" title={__('Name', 'mailpoet')}>
            <Input
              isFullWidth
              type="text"
              name="name"
              id="field_name"
              value={segment.name || ''}
              data-automation-id="input-name"
              onChange={(e): void => {
                void updateSegment({ name: e.target.value });
              }}
              placeholder={__('Enter name', 'mailpoet')}
            />
          </FieldWrapper>
          <FieldWrapper
            slug="description"
            title={__('Description', 'mailpoet')}
            description={__(
              'This text box is for your own use and is never shown to your subscribers.',
              'mailpoet',
            )}
          >
            <Input
              isFullWidth
              name="description"
              id="field_description"
              value={segment.description || ''}
              data-automation-id="input-description"
              onChange={(e): void => {
                void updateSegment({ description: e.target.value });
              }}
              placeholder={__('Enter description', 'mailpoet')}
            />
          </FieldWrapper>
        </FieldsSection>

        <FieldsSection
          title={__('Conditions', 'mailpoet')}
          description={__('Set your segment constraints', 'mailpoet')}
        >
          <FieldWrapper slug="conditions">
            <div className="mailpoet-segments-segments-section">
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
                          placeholder={__('Select action', 'mailpoet')}
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
              <div className="mailpoet-segments-conditions-bottom">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={(e): void => {
                    e.preventDefault();
                    addConditionAction(segment, updateSegment);
                  }}
                >
                  {__('Add a condition', 'mailpoet')}
                </Button>

                {(!MailPoet.premiumActive ||
                  !MailPoet.hasValidPremiumKey ||
                  MailPoet.subscribersLimitReached) && (
                  <LockedBadge text={__('UPGRADE', 'mailpoet')} />
                )}

                {showPremiumModal && (
                  <PremiumModal onRequestClose={closePremiumModal}>
                    {__(
                      'Multiple filters per segment are not available in the free version of the MailPoet plugin.',
                      'mailpoet',
                    )}
                  </PremiumModal>
                )}
              </div>
            </div>
          </FieldWrapper>
        </FieldsSection>

        <div className="mailpoet-admin-fields-bottom">
          <div className="mailpoet-segments-counter-section">
            <SubscribersCounter />
            <PrivacyProtectionNotice />
          </div>
          <div className="mailpoet-form-actions">
            <Button
              variant="primary"
              type="submit"
              onClick={(e): void => {
                e.preventDefault();
                void handleSave(isNewSegment);
              }}
              disabled={
                !isFormValid(segment.filters) ||
                subscriberCount.count === undefined
              }
            >
              {__('Save', 'mailpoet')}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
