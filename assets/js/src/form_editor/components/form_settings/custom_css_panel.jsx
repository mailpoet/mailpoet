import React, { useRef } from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import CodeMirror from './codemirror_wrap.jsx';

export default () => {
  const styles = useSelect(
    (select) => select('mailpoet-form-editor').getFormStyles(),
    []
  );
  const options = useRef({
    lineNumbers: true,
    tabMode: 'indent',
    matchBrackets: true,
    theme: 'neo',
    mode: 'css',
  });

  const { changeFormStyles } = useDispatch('mailpoet-form-editor');

  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('customCss')} initialOpen={false}>
        <CodeMirror
          value={styles}
          options={options.current}
          onChange={changeFormStyles}
        />
      </PanelBody>
    </Panel>
  );
};
