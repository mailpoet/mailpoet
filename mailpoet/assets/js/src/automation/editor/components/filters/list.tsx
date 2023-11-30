import classNames from 'classnames';
import { ComponentProps, useCallback, useMemo, useState } from 'react';
import { Hooks } from 'wp-js-hooks';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, closeSmall } from '@wordpress/icons';
import { ListGroupHeader } from './list-group-header';
import { Value } from './value';
import { Step } from '../automation/types';
import { storeName } from '../../store';
import { PremiumModal } from '../../../../common/premium-modal';
import {
  FilterGroupOperatorChangeType,
  FilterWrapperType,
} from '../../../types/filters';
import { FilterStrings } from './strings';

type Props = {
  editable?: boolean;
  step: Step;
  strings: FilterStrings;
};

export function FiltersList({
  editable = true,
  step,
  strings,
}: Props): JSX.Element | null {
  const [showPremiumModal, setShowPremiumModal] = useState(false);

  const { fields, filters, errors } = useSelect(
    (select) => ({
      fields: select(storeName).getRegistry().fields,
      filters: select(storeName).getRegistry().filters,
      errors: select(storeName).getStepError(step.id),
    }),
    [],
  );

  const onOperatorChange = useCallback(
    (stepId: string, groupId: string, operator: 'and' | 'or') => {
      const operatorChangeCallback: FilterGroupOperatorChangeType =
        Hooks.applyFilters(
          'mailpoet.automation.filters.group_operator_change_callback',
          () => setShowPremiumModal(true),
        );
      operatorChangeCallback(stepId, groupId, operator);
    },
    [],
  );

  const FilterWrapper = useMemo(() => {
    const wrapper: FilterWrapperType = Hooks.applyFilters(
      'mailpoet.automation.filters.filter_wrapper',
      ({
        editable: isEditable,
        children,
      }: ComponentProps<FilterWrapperType>) => (
        <>
          <Button
            className="mailpoet-automation-filters-list-item-content"
            disabled={!isEditable}
            onClick={() => setShowPremiumModal(true)}
          >
            {children}
          </Button>

          {isEditable && (
            <Button
              className="mailpoet-automation-filters-list-item-remove"
              isSmall
              onClick={() => setShowPremiumModal(true)}
            >
              <Icon icon={closeSmall} />
            </Button>
          )}
        </>
      ),
    );
    return wrapper;
  }, []);

  const groups = step.filters?.groups ?? [];
  if (groups.length === 0) {
    return null;
  }

  const filterErrors = errors?.filters ?? {};

  return (
    <>
      {showPremiumModal && (
        <PremiumModal
          onRequestClose={() => {
            setShowPremiumModal(false);
          }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'automation_premium_filters',
          }}
        >
          {strings.premiumMessage}
        </PremiumModal>
      )}

      {groups.map((group) => (
        <div key={group.id}>
          <ListGroupHeader
            editable={editable}
            group={group}
            onOperatorChange={onOperatorChange}
            strings={strings}
          />

          <div className="mailpoet-automation-filters-list">
            {group.filters.map((filter) => (
              <div key={filter.id}>
                <div
                  className={classNames(
                    'mailpoet-automation-filters-list-item',
                    {
                      'mailpoet-automation-filters-list-item-has-error':
                        !!filterErrors[filter.id],
                    },
                  )}
                >
                  <FilterWrapper
                    step={step}
                    filter={filter}
                    editable={editable}
                  >
                    <span className="mailpoet-automation-filters-list-item-field">
                      {fields[filter.field_key]?.name ??
                        sprintf(
                          __('Unknown field "%s"', 'mailpoet'),
                          filter.field_key,
                        )}
                    </span>{' '}
                    <span className="mailpoet-automation-filters-list-item-condition">
                      {filters[filter.field_type]?.conditions.find(
                        ({ key }) => key === filter.condition,
                      )?.label ?? __('unknown condition', 'mailpoet')}
                    </span>{' '}
                    <Value filter={filter} />
                  </FilterWrapper>
                </div>
                {filterErrors[filter.id] && (
                  <div className="mailpoet-automation-filters-list-item-error">
                    {filterErrors[filter.id]}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      ))}
    </>
  );
}
