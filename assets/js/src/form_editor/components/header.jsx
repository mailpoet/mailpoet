import React from 'react';
import { IconButton, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import MailPoet from 'mailpoet';

export default () => {
  const sidebarOpened = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpened(),
    []
  );
  const isFormSaving = useSelect(
    (select) => select('mailpoet-form-editor').getIsFormSaving(),
    []
  );
  const { toggleSidebar, saveForm } = useDispatch('mailpoet-form-editor');

  return (
    <div className="edit-post-header">
      <div className="edit-post-header-toolbar" />
      <div className="edit-post-header__settings">
        <Button
          isPrimary
          isLarge
          isDefault
          className="editor-post-publish-button"
          isBusy={isFormSaving}
          onClick={saveForm}
        >
          {isFormSaving ? `${__('Saving')}` : __('Save')}
        </Button>
        <IconButton
          icon="admin-generic"
          label={MailPoet.I18n.t('formSettings')}
          labelPosition="down"
          onClick={() => toggleSidebar(!sidebarOpened)}
          isToggled={sidebarOpened}
        />
      </div>
    </div>
  );
};
