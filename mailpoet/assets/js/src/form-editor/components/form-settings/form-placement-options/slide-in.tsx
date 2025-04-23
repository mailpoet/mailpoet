import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { SlideInIcon } from './icons/slide-in-icon';
import { FormPlacementOption } from './form-placement-option';
import { storeName } from '../../../store';

export function SlideIn(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );
  const { showPlacementSettings } = useDispatch(storeName);

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.slideIn.enabled}
      label={__('Slide–in', 'mailpoet')}
      icon={SlideInIcon}
      onClick={(): void => {
        void showPlacementSettings('slide_in');
      }}
      canBeActive
    />
  );
}
