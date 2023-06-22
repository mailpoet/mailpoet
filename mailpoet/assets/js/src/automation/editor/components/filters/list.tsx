import { ComponentProps, useCallback, useMemo, useState } from 'react';
import { Hooks } from 'wp-js-hooks';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, closeSmall } from '@wordpress/icons';
import { ListGroupHeader } from './list-group-header';
import { Value } from './value';
import { storeName } from '../../store';
import { PremiumModal } from '../../../../common/premium_modal';
import {
  FilterGroupOperatorChangeType,
  FilterWrapperType,
} from '../../../types/filters';

type Props = {
  editable?: boolean;
};

export function FiltersList({ editable = true }: Props): JSX.Element | null {
  const [showPremiumModal, setShowPremiumModal] = useState(false);

  const { step, fields, filters } = useSelect(
    (select) => ({
      step: select(storeName).getSelectedStep(),
      fields: select(storeName).getRegistry().fields,
      filters: select(storeName).getRegistry().filters,
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
          {__('Managing trigger filters is a premium feature.', 'mailpoet')}
        </PremiumModal>
      )}

      {groups.map((group) => (
        <div key={group.id}>
          <ListGroupHeader
            editable={editable}
            group={group}
            onOperatorChange={onOperatorChange}
          />

          <div className="mailpoet-automation-filters-list">
            {group.filters.map((filter) => (
              <div
                key={filter.id}
                className="mailpoet-automation-filters-list-item"
              >
                <FilterWrapper step={step} filter={filter} editable={editable}>
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
            ))}
          </div>
        </div>
      ))}
    </>
  );
}
