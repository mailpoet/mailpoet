import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import Icon from './icons/slide_in_icon';
import FormPlacementOption from './form_placement_option';

function SlideIn(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.slideIn.enabled}
      label={MailPoet.I18n.t('placeSlideInFormOnPages')}
      icon={Icon}
      onClick={(): void => showPlacementSettings('slide_in')}
      canBeActive
    />
  );
}

export default SlideIn;
