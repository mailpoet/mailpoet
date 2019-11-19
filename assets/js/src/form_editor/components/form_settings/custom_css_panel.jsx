import React from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

export default () => {
  const styles = useSelect(
    (select) => select('mailpoet-form-editor').getFormStyles(),
    []
  );
  const { changeFormStyles } = useDispatch('mailpoet-form-editor');
  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('customCss')} initialOpen={false}>
        <TextareaControl
          value={styles}
          rows={10}
          onChange={changeFormStyles}
        />
      </PanelBody>
    </Panel>
  );
};
