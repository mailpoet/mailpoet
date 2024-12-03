
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { TableCard } from '@woocommerce/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { storeName } from '../store/constants';


const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;



const columns = [
    {
        key: MailPoet.FeaturesController.isSupported('gutenberg_email_editor')
        ? 'name'
        : 'subject',
      label: MailPoet.FeaturesController.isSupported('gutenberg_email_editor')
        ? __('Name', 'mailpoet')
        : __('Subject', 'mailpoet'),
        isSortable: true,
    },
    {
        key: 'status',
      label: __('Status', 'mailpoet'),
      isSortable: false

    },
    {
        key: 'segments',
      label: __('Lists', 'mailpoet'),
      isSortable: false
    },
    {
        key: 'statistics',
      label: __('Clicked, Opened', 'mailpoet'),
      display: mailpoetTrackingEnabled,
      isSortable: false
    },
    {
        key: 'sent_at',
      label: __('Sent on', 'mailpoet'),
      isSortable: true,
    },
  ];


  

export function ListingTabContent() {
    const newsletterData = useSelect((select) => select(storeName).getNewsletterRows(), []);

    const rowClasses = classnames(
        'manage-column',
        'column-primary',
        'has-row-actions',
      );

    return (
      <div className={rowClasses}>
        <div>
          <pre> {JSON.stringify(newsletterData)} </pre>
          <TableCard
            //title="Revenue last weeks"
            rows={ newsletterData }
            headers={ columns }
            query={ { page: 2 } }
            rowsPerPage={ 7 }
            totalRows={ 10 }
         //   summary={ summary }
        />
        </div>
        
      </div>
    );
  } 