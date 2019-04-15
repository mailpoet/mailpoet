import React, { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import Selection from '../../../../form/fields/selection.jsx';
import PreviousNextStepButtons from '../previous_next_step_buttons.jsx';

const MethodMailChimp = ({ onFinish }) => {
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
      .done(response => setMailChimpLoadedLists(response.data))
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
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
      .done(response => onFinish(response.data))
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(error => error.message),
            { scroll: true }
          );
        }
      });
  };

  const showListsSelection = () => {
    if (!mailChimpLoadedLists) return null;
    return (
      <>
        <div>
          <span className="import_heading">{MailPoet.I18n.t('methodMailChimpSelectList')}</span>
        </div>
        <Selection
          field={{
            id: 'segments',
            name: 'list-selection',
            multiple: true,
            placeholder: MailPoet.I18n.t('methodMailChimpSelectPlaceholder'),
            forceSelect2: true,
            values: mailChimpLoadedLists,
          }}
          onValueChange={e => setSelectedLists(e.target.value)}
        />
      </>
    );
  };

  const statusClasses = classNames(
    'mailpoet_mailchimp-key-status',
    { 'mailpoet_mailchimp-ok': Array.isArray(mailChimpLoadedLists) }
  );

  return (
    <>
      <label htmlFor="paste_input" className="import_method_paste">
        <div>
          <span className="import_heading">{MailPoet.I18n.t('methodMailChimpLabel')}</span>
        </div>
        <input
          id="paste_input"
          type="text"
          onChange={keyChange}
        />
        <button className="button" type="button" onClick={verifyButtonClicked}>
          {MailPoet.I18n.t('methodMailChimpVerify')}
        </button>
        <span className={statusClasses}>
          { Array.isArray(mailChimpLoadedLists) && mailChimpLoadedLists.length === 0
            ? MailPoet.I18n.t('noMailChimpLists')
            : null
          }
        </span>
        {showListsSelection()}
      </label>
      <PreviousNextStepButtons
        canGoNext={Array.isArray(selectedLists) && selectedLists.length > 0}
        hidePrevious
        onNextAction={process}
      />
    </>
  );
};

MethodMailChimp.propTypes = {
  onFinish: PropTypes.func,
};

MethodMailChimp.defaultProps = {
  onFinish: () => {},
};

export default MethodMailChimp;
