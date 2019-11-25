import React, { useState } from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import _ from 'underscore';

export default () => {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    []
  );

  const exportLinkClicked = (type, event) => {
    event.preventDefault();
    if (type === 'php') {
      return setCopyAreaContent(formExports.php);
    }
    if (type === 'iframe') {
      return setCopyAreaContent(formExports.iframe);
    }
    return setCopyAreaContent(formExports.shortcode);
  };

  const addFormWidgetHint = ReactStringReplace(
    MailPoet.I18n.t('addFormWidgetHint'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => (
      <a key="addFormWidgetHintLink" href="widgets.php" target="_blank">{match}</a>
    )
  );

  const addFormShortcodeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormShortcodeHint'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => (
      <a key="exportShortcode" href="#" onClick={_.partial(exportLinkClicked, 'shortcode')}>{match}</a>
    )
  );

  const addFormPhpIframeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormPhpIframeHint'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => {
      if (match === 'PHP') {
        return (<a key="exportPHP" href="#" onClick={_.partial(exportLinkClicked, 'php')}>{match}</a>);
      }
      return (<a key="exportIframe" href="#" onClick={_.partial(exportLinkClicked, 'iframe')}>{match}</a>);
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
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formPlacement')} initialOpen={false}>
        <p>{addFormWidgetHint}</p>
        <p>{addFormShortcodeHint}</p>
        <p>{addFormPhpIframeHint}</p>
        {getCopyTextArea()}
      </PanelBody>
    </Panel>
  );
};
