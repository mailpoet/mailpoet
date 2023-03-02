import { useMemo, useState } from 'react';
import { Hooks } from 'wp-js-hooks';
import { Button, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';
import { FiltersPanelContentType } from '../../../types/filters';
import { PremiumModal } from '../../../../common/premium_modal';

function FiltersPanelContent(): JSX.Element {
  const [showPremiumModal, setShowPremiumModal] = useState(false);
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
          {__('Adding trigger filters is a premium feature.', 'mailpoet')}
        </PremiumModal>
      )}
      <Button
        className="mailpoet-automation-filters-panel-add-filter"
        variant="secondary"
        icon={plus}
        onClick={() => setShowPremiumModal(true)}
      >
        {__('Add trigger filter', 'mailpoet')}
      </Button>
    </>
  );
}

export function FiltersPanel(): JSX.Element {
  const selectedStep = useSelect(
    (select) => select(storeName).getSelectedStep(),
    [],
  );

  const content: FiltersPanelContentType = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet.automation.filters.panel.content',
        FiltersPanelContent,
      ) as FiltersPanelContentType,
    [],
  );

  return (
    <PanelBody initialOpen title={__('Trigger filters', 'mailpoet')}>
      <div className="mailpoet-automation-panel-description">
        {__(
          'The automation would only be started if the following trigger conditions are met:',
          'mailpoet',
        )}
      </div>
      {content(selectedStep)}
    </PanelBody>
  );
}
