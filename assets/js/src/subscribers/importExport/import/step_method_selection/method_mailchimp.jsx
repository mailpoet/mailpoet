import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Input from 'common/form/input/input';
import Selection from 'form/fields/selection.jsx';
import PreviousNextStepButtons from '../previous_next_step_buttons.jsx';

const MethodMailChimp = ({ onFinish, onPrevious }) => {
  const [key, setKey] = useState('');
  const [mailChimpLoadedLists, setMailChimpLoadedLists] = useState(undefined);
  const [selectedLists, setSelectedLists] = useState([]);

  const keyChange = (e) => {
    setKey(e.target.value);
    if (e.target.value.trim() === '') {
      setMailChimpLoadedLists(undefined);
    }
  };

  const verifyButtonClicked = () => {
    MailPoet.Modal.loading(true);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'importExport',
      action: 'getMailChimpLists',
      data: {
        api_key: key,
      },
    })
      .always(() => {
        MailPoet.Modal.loading(false);
      })
      .done((response) => setMailChimpLoadedLists(response.data))
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true }
          );
        }
      });
  };

  const process = () => {
    MailPoet.Modal.loading(true);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'importExport',
      action: 'getMailChimpSubscribers',
      data: {
        api_key: key,
        lists: selectedLists,
      },
    })
      .always(() => {
        MailPoet.Modal.loading(false);
      })
      .done((response) => onFinish(response.data))
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true }
          );
        }
      });
  };

  const showListsSelection = () => {
    if (!mailChimpLoadedLists) return null;
    return (
      <>
        <div className="mailpoet-settings-label">{MailPoet.I18n.t('methodMailChimpSelectList')}</div>
        <div className="mailpoet-settings-inputs">
          <Selection
            field={{
              id: 'segments',
              name: 'list-selection',
              multiple: true,
              placeholder: MailPoet.I18n.t('methodMailChimpSelectPlaceholder'),
              forceSelect2: true,
              values: mailChimpLoadedLists,
            }}
            onValueChange={(e) => setSelectedLists(e.target.value)}
          />
        </div>
      </>
    );
  };

  return (
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="mailpoet_mailchimp_key_input">
          <span className="mailpoet_import_heading">{MailPoet.I18n.t('methodMailChimpLabel')}</span>
        </label>
      </div>
      <div className="mailpoet-settings-inputs">
        <div className="mailpoet-settings-inputs-row mailpoet-settings-inputs-row-centered">
          <Input
            id="mailpoet_mailchimp_key_input"
            type="text"
            onChange={keyChange}
          />
          <span className="mailpoet-gap" />
          <Button type="button" dimension="small" onClick={verifyButtonClicked}>
            {MailPoet.I18n.t('methodMailChimpVerify')}
          </Button>
          <span className="mailpoet-gap" />
          <span>
            { Array.isArray(mailChimpLoadedLists) && mailChimpLoadedLists.length === 0
              ? MailPoet.I18n.t('noMailChimpLists')
              : null}
          </span>
        </div>
      </div>
      {showListsSelection()}
      <PreviousNextStepButtons
        canGoNext={Array.isArray(selectedLists) && selectedLists.length > 0}
        onPreviousAction={onPrevious}
        onNextAction={process}
      />
    </>
  );
};

MethodMailChimp.propTypes = {
  onFinish: PropTypes.func,
  onPrevious: PropTypes.func,
};

MethodMailChimp.defaultProps = {
  onFinish: () => {},
  onPrevious: () => {},
};

export default MethodMailChimp;
