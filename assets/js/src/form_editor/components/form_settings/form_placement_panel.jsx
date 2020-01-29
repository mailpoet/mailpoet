import React, { useState } from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
  ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { curry } from 'lodash';
import PropTypes from 'prop-types';

const FormPlacementPanel = ({ onToggle, isOpened }) => {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    []
  );

  const placeFormBellowAllPages = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPages(),
    []
  );

  const placeFormBellowAllPosts = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPosts(),
    []
  );

  const { setPlaceFormBellowAllPages, setPlaceFormBellowAllPosts } = useDispatch('mailpoet-form-editor');

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
        <ToggleControl
          label={MailPoet.I18n.t('placeFormBellowAllPages')}
          checked={placeFormBellowAllPages}
          onChange={setPlaceFormBellowAllPages}
        />
        <div data-automation-id="place-form-bellow-all-posts-toggle">
          <ToggleControl
            label={MailPoet.I18n.t('placeFormBellowAllPosts')}
            checked={placeFormBellowAllPosts}
            onChange={setPlaceFormBellowAllPosts}
          />
        </div>
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
