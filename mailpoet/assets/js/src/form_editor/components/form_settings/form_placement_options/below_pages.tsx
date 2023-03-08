import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { BelowPageIcon } from './icons/below_pages_icon';
import { FormPlacementOption } from './form_placement_option';
import { store } from '../../../store';

export function BelowPages(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(store).getFormSettings(),
    [],
  );

  const { showPlacementSettings } = useDispatch(store);

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.belowPosts.enabled}
      label={MailPoet.I18n.t('placeFormBellowPages')}
      icon={BelowPageIcon}
      onClick={(): void => {
        void showPlacementSettings('below_posts');
      }}
      canBeActive
    />
  );
}
