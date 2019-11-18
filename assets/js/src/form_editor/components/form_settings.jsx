import React, { useState } from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

export default () => {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    []
  );

  const addFormWidgetHint = ReactStringReplace(
    MailPoet.I18n.t('addFormWidgetHint'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => (
      <a key="addFormWidgetHintLink" href="widgets.php" target="_blank">{match}</a>
    )
  );

  const exportLinkClicked = (event, type) => {
    event.preventDefault();
    if (type === 'php') {
      return setCopyAreaContent(formExports.php);
    }
    if (type === 'iframe') {
      return setCopyAreaContent(formExports.iframe);
    }
    return setCopyAreaContent(formExports.shortcode);
  };

  const addFormShortcodeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormShortcodeHint'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => (
      <a key="exportShorcode" href="#" onClick={(e) => exportLinkClicked(e, 'shortcode')}>{match}</a>
    )
  );

  const addFormPhpIframeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormPhpIframeHint'),
    /%s(.*?)%s/g,
    (match) => {
      if (match === 'PHP') {
        return (<a key="exportPHP" href="#" onClick={(e) => exportLinkClicked(e, 'php')}>{match}</a>);
      }
      return (<a key="exportIframe" href="#" onClick={(e) => exportLinkClicked(e, 'iframe')}>{match}</a>);
    }
  );

  const getCopyTextArea = () => {
    if (!copyAreaContent) return null;
    return (
      <TextareaControl
        key="copyTextArea"
        readOnly
        onClick={(event) => (event.target.select())}
        rows={8}
        value={copyAreaContent}
      />
    );
  };

  return (
    <>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')}>
          <p>TODO Basic Settings</p>
        </PanelBody>
      </Panel>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formPlacement')} initialOpen={false}>
          <p>{addFormWidgetHint}</p>
          <p>{addFormShortcodeHint}</p>
          <p>{addFormPhpIframeHint}</p>
          {getCopyTextArea()}
        </PanelBody>
      </Panel>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customCss')} initialOpen={false}>
          <p>TODO Custom CSS</p>
        </PanelBody>
      </Panel>
    </>
  );
};
