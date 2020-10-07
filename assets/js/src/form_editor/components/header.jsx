import React from 'react';
import {
  Button,
  ToolbarItem,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { plus } from '@wordpress/icons';
import { __, _x } from '@wordpress/i18n';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

const Header = ({ isInserterOpened, setIsInserterOpened }) => {
  const sidebarOpened = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpened(),
    []
  );
  const isFormSaving = useSelect(
    (select) => select('mailpoet-form-editor').getIsFormSaving(),
    []
  );
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );
  const { toggleSidebar, saveForm, showPreview } = useDispatch('mailpoet-form-editor');

  return (
    <div className="edit-post-header">
      <div className="edit-post-header__settings">
        <Button
          isSecondary
          onClick={showPreview}
          isPressed={isPreview}
          className="mailpoet-preview-button"
          data-automation-id="form_preview_button"
        >
          {__('Preview')}
        </Button>
        <Button
          isPrimary
          className="editor-post-publish-button"
          data-automation-id="form_save_button"
          isBusy={isFormSaving}
          onClick={saveForm}
        >
          {isFormSaving ? `${__('Saving')}` : __('Save')}
        </Button>
        <Button
          icon="admin-generic"
          label={MailPoet.I18n.t('formSettings')}
          tooltipPosition="down"
          onClick={() => toggleSidebar(!sidebarOpened)}
          isPressed={sidebarOpened}
          className="mailpoet-editor-header-button"
        />
      </div>
      <div className="edit-post-header__toolbar">
        <div className="toolbar">
          <Button
            isSecondary
            href="?page=mailpoet-forms#/"
            className="mailpoet-editor-header-button"
          >
            {MailPoet.I18n.t('back')}
          </Button>
          <ToolbarItem
            as={Button}
            data-automation-id="form_inserter_open"
            className="edit-post-header-toolbar__inserter-toggle"
            isPrimary
            isPressed={isInserterOpened}
            onClick={() => setIsInserterOpened(!isInserterOpened)}
            icon={plus}
            label={_x(
              'Add block',
              'Generic label for block inserter button'
            )}
          />
        </div>
      </div>
    </div>
  );
};

Header.propTypes = {
  isInserterOpened: PropTypes.bool.isRequired,
  setIsInserterOpened: PropTypes.func.isRequired,
};

export default Header;
