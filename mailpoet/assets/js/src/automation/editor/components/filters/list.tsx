import { useCallback, useState } from 'react';
import { Hooks } from 'wp-js-hooks';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, closeSmall } from '@wordpress/icons';
import { Value } from './value';
import { Filter } from '../automation/types';
import { storeName } from '../../store';
import { PremiumModal } from '../../../../common/premium_modal';
import { DeleteStepFilterType } from '../../../types/filters';

export function FiltersList(): JSX.Element | null {
  const [showPremiumModal, setShowPremiumModal] = useState(false);

  const { step, fields, filters } = useSelect(
    (select) => ({
      step: select(storeName).getSelectedStep(),
      fields: select(storeName).getRegistry().fields,
      filters: select(storeName).getRegistry().filters,
    }),
    [],
  );

  const onDelete = useCallback((stepId: string, filter: Filter) => {
    const deleteFilterCallback: DeleteStepFilterType = Hooks.applyFilters(
      'mailpoet.automation.filters.delete_step_filter_callback',
      () => setShowPremiumModal(true),
    );
    deleteFilterCallback(stepId, filter);
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
          {__('Removing trigger filters is a premium feature.', 'mailpoet')}
        </PremiumModal>
      )}

      <div className="mailpoet-automation-filters-list">
        {groups.map((group) =>
          group.filters.map((filter) => (
            <div
              key={filter.field_key}
              className="mailpoet-automation-filters-list-item"
            >
              <div className="mailpoet-automation-filters-list-item-content">
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
              </div>
              <Button
                className="mailpoet-automation-filters-list-item-remove"
                isSmall
                onClick={() => onDelete(step.id, filter)}
              >
                <Icon icon={closeSmall} />
              </Button>
            </div>
          )),
        )}
      </div>
    </>
  );
}
