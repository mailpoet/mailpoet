import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './below_pages_icon';
import Modal from '../../../../common/modal/modal.jsx';

const BelowPages = () => {
  const [displaySettings, setDisplaySettings] = useState(false);

  const placeFormBellowAllPages = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPages(),
    []
  );

  const placeFormBellowAllPosts = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPosts(),
    []
  );

  return (
    <>
      <FormPlacementOption
        label={MailPoet.I18n.t('placeFormBellowPages')}
        icon={Icon}
        active={placeFormBellowAllPages || placeFormBellowAllPosts}
        onClick={() => setDisplaySettings(true)}
      />
      {
        displaySettings
        && (
          <Modal
            title={MailPoet.I18n.t('placeFormBellowPages')}
            onRequestClose={() => setDisplaySettings(false)}
          >
            <p>
              {MailPoet.I18n.t('placeFormBellowPagesDescription')}
            </p>
          </Modal>
        )
      }
    </>
  );
};

export default BelowPages;
