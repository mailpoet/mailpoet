import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { useSelect } from '@wordpress/data';
import { curry } from 'lodash';
import { TextareaControl } from '@wordpress/components';

import FormPlacementSettings from './form_placement_settings';
import Icon from './icons/sidebar_icon';

const Popup = () => {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    []
  );

  const addFormWidgetHint = ReactStringReplace(
    MailPoet.I18n.t('addFormWidgetHint'),
    /\[link](.*?)\[\/link]/g,
    (match) => (
      <a key="addFormWidgetHintLink" href="widgets.php" target="_blank">{match}</a>
    )
  );

  const addFormShortcodeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormShortcodeHint'),
    /\[shortcode]/g,
    (match) => (<code key={match}>{formExports.shortcode}</code>)
  );

  const exportLinkClicked = curry((type, event) => {
    event.preventDefault();
    MailPoet.trackEvent('Forms > Embed', {
      'Embed type': type,
      'MailPoet Free version': (window as any).mailpoet_version,
    });
    if (type === 'php') {
      return setCopyAreaContent(formExports.php);
    }
    return setCopyAreaContent(formExports.iframe);
  });

  const addFormPhpIframeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormPhpIframeHint'),
    /\[link](.*?)\[\/link]/g,
    (match) => {
      if (match === 'PHP') {
        return (<a key="exportPHP" href="#" onClick={exportLinkClicked('php')}>{match}</a>);
      }
      return (<a key="exportIframe" href="#" onClick={exportLinkClicked('iframe')}>{match}</a>);
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
    <FormPlacementSettings
      active={false}
      onSave={() => {}}
      header={MailPoet.I18n.t('formPlacementOther')}
      label={MailPoet.I18n.t('formPlacementOtherLabel')}
      icon={Icon}
    >
      <p>{addFormWidgetHint}</p>
      <p>{addFormShortcodeHint}</p>
      <p>{addFormPhpIframeHint}</p>
      {getCopyTextArea()}
    </FormPlacementSettings>
  );
};

export default Popup;
