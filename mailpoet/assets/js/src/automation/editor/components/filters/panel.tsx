import { useMemo, useState } from 'react';
import { Hooks } from 'wp-js-hooks';
import { Button, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { plus } from '@wordpress/icons';
import { storeName } from '../../store';
import { FiltersPanelContentType } from '../../../types/filters';
import { PremiumModal } from '../../../../common/premium-modal';
import { FiltersList } from './list';
import { FilterStrings } from './strings';
import { Filter } from '../automation/types';

type ContentProps = {
  strings: FilterStrings;
};

function FiltersPanelContent({ strings }: ContentProps): JSX.Element {
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
          {strings.premiumMessage}
        </PremiumModal>
      )}
      <Button
        className="mailpoet-automation-filters-panel-add-filter"
        variant="secondary"
        icon={plus}
        onClick={() => setShowPremiumModal(true)}
      >
        {strings.addFilter}
      </Button>
    </>
  );
}

type Props = {
  strings: FilterStrings;
  onChange?: (data: Filter) => void;
};

export function FiltersPanel({ strings, onChange }: Props): JSX.Element {
  const selectedStep = useSelect(
    (select) => select(storeName).getSelectedStep(),
    [],
  );

  const content: FiltersPanelContentType = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet.automation.filters.panel.content',
        () => <FiltersPanelContent strings={strings} />,
        strings,
        onChange,
      ) as FiltersPanelContentType,
    [strings, onChange],
  );

  return (
    <PanelBody initialOpen title={strings.title}>
      <FiltersList step={selectedStep} strings={strings} />
      <div className="mailpoet-automation-filters-panel-content">
        {content(selectedStep)}
      </div>
    </PanelBody>
  );
}
