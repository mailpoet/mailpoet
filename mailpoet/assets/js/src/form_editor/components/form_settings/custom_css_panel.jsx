import { Panel, PanelBody } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

import { MailPoet } from 'mailpoet';
import { CodemirrorWrap } from './codemirror_wrap.jsx';

function CustomCssPanel({ onToggle, isOpened }) {
  const styles = useSelect(
    (select) => select('mailpoet-form-editor').getFormStyles(),
    [],
  );

  const { changeFormStyles } = useDispatch('mailpoet-form-editor');

  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('customCss')}
        opened={isOpened}
        onToggle={onToggle}
      >
        <CodemirrorWrap value={styles} onChange={changeFormStyles} />
      </PanelBody>
    </Panel>
  );
}

CustomCssPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export { CustomCssPanel };
