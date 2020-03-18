import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { Button } from '@wordpress/components';

import FormPlacementOption from './form_placement_option';
import Icon from './below_pages_icon';
import Modal from '../../../../common/modal/modal.jsx';

type Props = {
  children: React.ReactNode,
  onSave: () => void,
  active: boolean,
  label: string,
  description: string,
}

const BelowPages = ({
  description,
  label,
  active,
  onSave,
  children,
}: Props) => {
  const [displaySettings, setDisplaySettings] = useState(false);

  const save = () => {
    setDisplaySettings(false);
    onSave();
  };

  return (
    <>
      <FormPlacementOption
        label={label}
        icon={Icon}
        active={active}
        onClick={() => setDisplaySettings(true)}
      />
      {
        displaySettings
        && (
          <Modal
            title={label}
            onRequestClose={() => setDisplaySettings(false)}
            contentClassName="form-placement-settings"
          >
            <p>
              {description}
            </p>
            {children}
            <div className="mailpoet-form-placement-save">
              <Button
                onClick={save}
                className="mailpoet-save-button automation-id-save-form-placement"
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
