import React, { useState } from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { curry } from 'lodash';
import PropTypes from 'prop-types';

import FormPlacementOptionBelowPages from './form_placement_options/below_pages';

const FormPlacementPanel = ({ onToggle, isOpened }) => {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    []
  );

  const exportLinkClicked = curry((type, event) => {
    event.preventDefault();
    MailPoet.trackEvent('Forms > Embed', {
      'Embed type': type,
      'MailPoet Free version': window.mailpoet_version,
    });
    if (type === 'php') {
      return setCopyAreaContent(formExports.php);
    }
    return setCopyAreaContent(formExports.iframe);
  });

  const addFormWidgetHint = ReactStringReplace(
    MailPoet.I18n.t('addFormWidgetHint'),
    /\[link](.*?)\[\/link]/g,
    (match) => (
      <a key="addFormWidgetHintLink" href="widgets.php" target="_blank">{match}</a>
    )
  );

  const addFormShortcodeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormShortcodeHint'),
    /(\[link].*\[\/link])|(\[shortcode])/g,
    (match) => {
      if (match === '[shortcode]') {
        return (<code key={match}>{formExports.shortcode}</code>);
      }
      if (typeof match === 'string' && match.includes('[link]')) {
        const link = match.replace(/\[.?link]/g, '');
        return (
          <button
            key={match}
            className="button-link"
            type="button"
            data-beacon-article="5e3a166204286364bc94dda4"
          >
            {link}
          </button>
        );
      }
      return match;
    }
  );

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
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formPlacement')}
        opened={isOpened}
        onToggle={onToggle}
        className="form-sidebar-form-placement-panel"
      >
        <FormPlacementOptionBelowPages />
        <p>{addFormShortcodeHint}</p>
        <p>{addFormWidgetHint}</p>
        <p>{addFormPhpIframeHint}</p>
        {getCopyTextArea()}
      </PanelBody>
    </Panel>
  );
};

FormPlacementPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export default FormPlacementPanel;
