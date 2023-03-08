import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { FormPlacementOption } from './form_placement_option';
import { FixedBarIcon } from './icons/fixed_bar_icon';
import { store } from '../../../store';

export function FixedBar(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(store).getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch(store);

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
