import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import ImportContext from '../context.jsx';

function SelectImportMethod({
  activeMethod,
  onMethodChange,
}) {
  const renderSelection = () => (
    <form className="import_selection_form">
      <span>{MailPoet.I18n.t('methodSelectionHead')}</span>
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
          checked={activeMethod === 'csv-method'}
          onChange={() => onMethodChange('csv-method')}
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
  );

  return (
    <ImportContext.Consumer>
      {({ isNewUser }) => {
        const badgeClasses = classNames(
          'mailpoet_badge',
          'mailpoet_badge_video',
          { mailpoet_badge_video_grey: isNewUser }
        );
        return (
          <>
            {renderSelection()}
            <a
              className={badgeClasses}
              href="https://beta.docs.mailpoet.com/article/242-video-guide-importing-subscribers-using-a-csv-file"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="dashicons dashicons-format-video" />
              {MailPoet.I18n.t('seeVideo')}
            </a>
          </>
        );
      }}
    </ImportContext.Consumer>
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
