import React, { useLayoutEffect } from 'react';
import {
  Button,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomFieldDelete = ({
  isBusy,
  displayConfirm,
  onDeleteCancel,
  onDeleteClick,
  onDeleteConfirm,
}) => {
  useLayoutEffect(() => {
    if (displayConfirm) {
      const result = window.confirm(MailPoet.I18n.t('customFieldDeleteConfirm'));// eslint-disable-line no-alert
      if (result) {
        onDeleteConfirm();
      } else {
        onDeleteCancel();
      }
    }
  });

  return (
    <Button
      isDestructive
      isLink
      isBusy={isBusy}
      onClick={onDeleteClick}
      className="button-on-top"
    >
      {MailPoet.I18n.t('customFieldDeleteCTA')}
    </Button>
  );
};

CustomFieldDelete.propTypes = {
  isBusy: PropTypes.bool,
  displayConfirm: PropTypes.bool,
  onDeleteClick: PropTypes.func,
  onDeleteCancel: PropTypes.func,
  onDeleteConfirm: PropTypes.func,
};

CustomFieldDelete.defaultProps = {
  isBusy: false,
  displayConfirm: false,
  onDeleteCancel: () => {},
  onDeleteClick: () => {},
  onDeleteConfirm: () => {},
};

export default CustomFieldDelete;
