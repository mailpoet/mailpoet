import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { Button } from '@wordpress/components';

import FormPlacementOption from './form_placement_option';
import Modal from '../../../../common/modal/modal.jsx';

type Props = {
  children: React.ReactNode,
  onSave: () => void,
  active: boolean,
  label: string,
  header?: string,
  description?: string,
  icon: JSX.Element,
}

const BelowPages = ({
  description,
  label,
  header,
  active,
  onSave,
  children,
  icon,
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
        icon={icon}
        active={active}
        onClick={() => setDisplaySettings(true)}
      />
      {
        displaySettings
        && (
          <Modal
            title={header ?? label}
            onRequestClose={() => setDisplaySettings(false)}
            contentClassName="form-placement-settings"
          >
            {
              description !== undefined && (
                <p>
                  {description}
                </p>
              )
            }
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
