import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

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
      label={MailPoet.I18n.t('placeFixedBarFormOnPages')}
      icon={FixedBarIcon}
      onClick={(): void => {
        void showPlacementSettings('fixed_bar');
      }}
      canBeActive
    />
  );
}
