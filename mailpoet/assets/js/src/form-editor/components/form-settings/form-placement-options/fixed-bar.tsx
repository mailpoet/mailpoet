import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { FormPlacementOption } from './form-placement-option';
import { FixedBarIcon } from './icons/fixed-bar-icon';
import { storeName } from '../../../store';

export function FixedBar(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch(storeName);

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.fixedBar.enabled}
      label={__('Fixed bar', 'mailpoet')}
      icon={FixedBarIcon}
      onClick={(): void => {
        void showPlacementSettings('fixed_bar');
      }}
      canBeActive
    />
  );
}
