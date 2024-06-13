import { useCallback } from 'react';
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';

function CustomFieldDelete({ isBusy = false, onDelete = () => {} }) {
  const displayConfirm = useCallback(() => {
    const result = window.confirm(MailPoet.I18n.t('customFieldDeleteConfirm')); // eslint-disable-line no-alert
    if (result) {
      onDelete();
    }
  }, [onDelete]);

  return (
    <Button
      isDestructive
      isLink
      isBusy={isBusy}
      onClick={displayConfirm}
      className="button-on-top"
    >
      {MailPoet.I18n.t('customFieldDeleteCTA')}
    </Button>
  );
}

CustomFieldDelete.propTypes = {
  isBusy: PropTypes.bool,
  onDelete: PropTypes.func,
};

export { CustomFieldDelete };
