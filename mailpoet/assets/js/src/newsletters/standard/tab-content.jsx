
import { useState } from 'react';

import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { TableCard } from '@woocommerce/components';
import { TextControl, SelectControl } from '@wordpress/components';


import { useSelect, useDispatch } from '@wordpress/data';
import { storeName } from '../store/constants';

import { getRow } from './get-row';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

function SelectAll(){

  return (
    <input
      checked={false}
      type="checkbox"
      data-automation-id="select_all"
      onChange={()=> {console.log("selected")}}
    />
  );
}

const columns = [
  {
    key: 'checkbox',
    label: <SelectAll />,
    cellClassName: 'mailpoet-listing-checkbox',
  },
  {
    key: MailPoet.FeaturesController.isSupported('gutenberg_email_editor')
      ? 'name'
      : 'subject',
    label: MailPoet.FeaturesController.isSupported('gutenberg_email_editor')
      ? __('Name', 'mailpoet')
      : __('Subject', 'mailpoet'),
    sortable: true,
  },
  {
    key: 'status',
    label: __('Status', 'mailpoet'),
  },
  {
    key: 'segments',
    label: __('Lists', 'mailpoet'),
  },
  {
    key: 'statistics',
    label: __('Clicked, Opened', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    key: 'sent_at',
    label: __('Sent on', 'mailpoet'),
    sortable: true,
  },
  {
    key: 'actions',
    isLeftAligned: false,
  },
];

const bulkActions =
"all" !== 'trash'
  ? [
      {
        value: '0',
        label: __('Bulk actions', 'mailpoet'),
      },
      {
        label: __('Trash', 'mailpoet'),
        value: 'trash',
      },
    ]
  : [
      {
        value: '0',
        label: __('Bulk actions', 'mailpoet'),
      },
      {
        label: __('Restore', 'mailpoet'),
        value: 'restore',
      },
      {
        label: __('Delete permanently', 'mailpoet'),
        value: 'delete',
      },
    ];



export function StandardTabContent() {
    const dispatch = useDispatch(); 

    const [currentAction, setCurrentAction] = useState(null);
    const [currentSelected, setCurrentSelected] = useState([]);
    const newsletterStandardData = useSelect((select) => select(storeName).getStandardNewsletters(), []);
    const metaData = useSelect((select) => select(storeName).getMeta(), []);

    const newsletterStandardLoading = useSelect((select) => select(storeName).getStandardNewsletterLoading(), []);
    const newsletterStandardRows = newsletterStandardData.map(newsletter =>  getRow(newsletter, metaData, dispatch, (action, segment) => {
      setCurrentSelected([segment]);
      setCurrentAction(action);
    }) );

    return (
      <>
        <div className="mailpoet-newsletters-listing-header">
          <SelectControl
            multiple={false}
            hideLabelFromVision
            className="mailpoet-newsletters-listing-bulk-actions"
            label={__('Bulk actions', 'mailpoet')}
            options={bulkActions}
            value="0"
            onChange=""
          />
          <TextControl
            className="mailpoet-newsletters-listing-search"
            placeholder={__('Search', 'mailpoet')}
            onChange=""
            value=""
          />
        </div>
        <div>
          <pre> {newsletterStandardLoading ? "true" : "false"} </pre>
          <pre> {currentAction} </pre>

          <TableCard
            //title="Revenue last weeks"
            className="mailpoet-listing-card mailpoet-newsletters-listing"
            isLoading={ newsletterStandardLoading }
            rows={ newsletterStandardRows }
            headers={ columns }
            query={ { page: 2 } }
            rowsPerPage={ 7 }
            totalRows={ 10 }
        />
        </div>
        
      </>
    );
  } 