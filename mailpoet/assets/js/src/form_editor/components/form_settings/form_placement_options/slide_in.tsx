import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { SlideInIcon } from './icons/slide_in_icon';
import { FormPlacementOption } from './form_placement_option';

export function SlideIn(): JSX.Element {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.slideIn.enabled}
      label={MailPoet.I18n.t('placeSlideInFormOnPages')}
      icon={SlideInIcon}
      onClick={(): void => {
        void showPlacementSettings('slide_in');
      }}
      canBeActive
    />
  );
}
