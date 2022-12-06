import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { Radio } from 'common/form/radio/radio';
import { Tag } from 'common/tag/tag';

function SelectImportMethod({ activeMethod, onMethodChange }) {
  return (
    <>
      <div className="mailpoet-settings-label">
        <span className="mailpoet_import_heading">
          {MailPoet.I18n.t('methodSelectionHead')}
        </span>
        <div className="mailpoet-settings-inputs-row">
          <a
            href="https://kb.mailpoet.com/article/242-video-guide-importing-subscribers-using-a-csv-file"
            data-beacon-article="5a8e8f0204286305fbc9be9a"
            target="_blank"
            rel="noopener noreferrer"
          >
            <Tag dimension="large" variant="excellent" isInverted>
              {MailPoet.I18n.t('seeVideo')}
            </Tag>
          </a>
        </div>
      </div>
      <div className="mailpoet-settings-inputs">
        <div className="mailpoet-settings-inputs-row">
          <Radio
            name="select_method"
            automationId="import-paste-method"
            id="import-paste-method"
            checked={activeMethod === 'paste-method'}
            value="paste-method"
            onCheck={onMethodChange}
          />
          <label htmlFor="import-paste-method">
            {MailPoet.I18n.t('methodPaste')}
          </label>
        </div>

        <div className="mailpoet-settings-inputs-row">
          <Radio
            name="select_method"
            automationId="import-csv-method"
            id="import-csv-method"
            checked={activeMethod === 'file-method'}
            value="file-method"
            onCheck={onMethodChange}
          />
          <label htmlFor="import-csv-method">
            {MailPoet.I18n.t('methodUpload')}
          </label>
        </div>

        <div className="mailpoet-settings-inputs-row">
          <Radio
            name="select_method"
            automationId="import-mailchimp-method"
            id="import-mailchimp-method"
            checked={activeMethod === 'mailchimp-method'}
            value="mailchimp-method"
            onCheck={onMethodChange}
          />
          <label htmlFor="import-mailchimp-method">
            {MailPoet.I18n.t('methodMailChimp')}
          </label>
        </div>
      </div>
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
SelectImportMethod.displayName = 'SelectImportMethod';
export { SelectImportMethod };
