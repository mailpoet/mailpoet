import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { SlideInIcon } from './icons/slide_in_icon';
import { FormPlacementOption } from './form_placement_option';
import { store } from '../../../store';

export function SlideIn(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(store).getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch(store);

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
