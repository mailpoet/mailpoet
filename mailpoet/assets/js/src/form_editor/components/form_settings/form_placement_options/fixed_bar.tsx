import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './icons/fixed_bar_icon';

function FixedBar(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.fixedBar.enabled}
      label={MailPoet.I18n.t('placeFixedBarFormOnPages')}
      icon={Icon}
      onClick={(): void => showPlacementSettings('fixed_bar')}
      canBeActive
    />
  );
}

export default FixedBar;
