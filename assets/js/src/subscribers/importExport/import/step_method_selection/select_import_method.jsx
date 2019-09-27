import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { GlobalContext } from 'context/index.jsx';

function SelectImportMethod({
  activeMethod,
  onMethodChange,
}) {
  const { constants } = React.useContext(GlobalContext);
  const { isNewUser } = constants;
  const badgeClasses = classNames(
    'mailpoet_badge',
    'mailpoet_badge_video',
    { mailpoet_badge_video_grey: !isNewUser }
  );
  return (
    <>
      <form className="mailpoet_import_selection_form">
        <span className="mailpoet_import_heading">{MailPoet.I18n.t('methodSelectionHead')}</span>
        <label htmlFor="import-paste-method">
          <input
            type="radio"
            name="select_method"
            data-automation-id="import-paste-method"
            id="import-paste-method"
            checked={activeMethod === 'paste-method'}
            onChange={() => onMethodChange('paste-method')}
          />
          {MailPoet.I18n.t('methodPaste')}
        </label>
        <label htmlFor="import-csv-method">
          <input
            type="radio"
            name="select_method"
            data-automation-id="import-csv-method"
            id="import-csv-method"
            checked={activeMethod === 'file-method'}
            onChange={() => onMethodChange('file-method')}
          />
          {MailPoet.I18n.t('methodUpload')}
        </label>
        <label htmlFor="import-mailchimp-method">
          <input
            type="radio"
            name="select_method"
            data-automation-id="import-mailchimp-method"
            id="import-mailchimp-method"
            checked={activeMethod === 'mailchimp-method'}
            onChange={() => onMethodChange('mailchimp-method')}
          />
          {MailPoet.I18n.t('methodMailChimp')}
        </label>
      </form>
      <a
        className={badgeClasses}
        href="https://kb.mailpoet.com/article/242-video-guide-importing-subscribers-using-a-csv-file"
        data-beacon-article="5a8e8f0204286305fbc9be9a"
        target="_blank"
        rel="noopener noreferrer"
      >
        <span className="dashicons dashicons-format-video" />
        {MailPoet.I18n.t('seeVideo')}
      </a>
    </>
  );
}

SelectImportMethod.propTypes = {
  activeMethod: PropTypes.string,
  onMethodChange: PropTypes.func.isRequired,
};

SelectImportMethod.defaultProps = {
  activeMethod: undefined,
};

export default SelectImportMethod;
