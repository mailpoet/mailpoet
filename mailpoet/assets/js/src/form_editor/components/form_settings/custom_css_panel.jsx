import { Panel, PanelBody } from '@wordpress/components';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';
import CodeMirror from './codemirror_wrap.jsx';

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
        <CodeMirror value={styles} onChange={changeFormStyles} />
      </PanelBody>
    </Panel>
  );
}

CustomCssPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export default CustomCssPanel;
