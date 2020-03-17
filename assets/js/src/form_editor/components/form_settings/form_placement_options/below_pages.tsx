import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './below_pages_icon';
import Modal from '../../../../common/modal/modal.jsx';
import Toggle from '../../../../common/toggle';

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
  const { setPlaceFormBellowAllPages, setPlaceFormBellowAllPosts } = useDispatch('mailpoet-form-editor');

  const [
    localPlaceFormBellowAllPages,
    setLocalPlaceFormBellowAllPages,
  ] = useState(placeFormBellowAllPages);
  const [
    localPlaceFormBellowAllPosts,
    setLocalPlaceFormBellowAllPosts,
  ] = useState(placeFormBellowAllPosts);

  const save = () => {
    setDisplaySettings(false);
    setPlaceFormBellowAllPages(localPlaceFormBellowAllPages);
    setPlaceFormBellowAllPosts(localPlaceFormBellowAllPosts);
  };

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
            contentClassName="form-placement-settings"
          >
            <p>
              {MailPoet.I18n.t('placeFormBellowPagesDescription')}
            </p>
            <div className="mailpoet-toggle-list">
              <div className="mailpoet-toggle-list-description">
                {MailPoet.I18n.t('placeFormBellowAllPages')}
              </div>
              <div className="mailpoet-toggle-list-toggle">
                <Toggle
                  name="localPlaceFormBellowAllPages"
                  checked={localPlaceFormBellowAllPages}
                  onCheck={setLocalPlaceFormBellowAllPages}
                />
              </div>
              <div className="mailpoet-toggle-list-description">
                {MailPoet.I18n.t('placeFormBellowAllPosts')}
              </div>
              <div className="mailpoet-toggle-list-toggle">
                <Toggle
                  name="localPlaceFormBellowAllPosts"
                  checked={localPlaceFormBellowAllPosts}
                  onCheck={setLocalPlaceFormBellowAllPosts}
                />
              </div>
            </div>
            <div className="mailpoet-form-placement-save">
              <Button
                onClick={save}
                className="mailpoet-save-button"
              >
                {MailPoet.I18n.t('formPlacementSave')}
              </Button>
            </div>
          </Modal>
        )
      }
    </>
  );
};

export default BelowPages;
