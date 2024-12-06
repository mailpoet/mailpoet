
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { TableCard,TablePlaceholder } from '@woocommerce/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { storeName } from '../store/constants';


const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const gutenbergEmailEditorSupported = MailPoet.FeaturesController.isSupported('gutenberg_email_editor');

export const NEWSLETTER_STANDARD_HEADERS = [
        {
          key: gutenbergEmailEditorSupported ? 'name' : 'subject',
          label: gutenbergEmailEditorSupported ? __('Name', 'mailpoet') : __('Subject', 'mailpoet'),
          isSortable: true,
        },
        {
          key: 'status',
          label: __('Status', 'mailpoet'),
          isSortable: false,
        },
        {
          key: 'segments',
          label: __('Lists', 'mailpoet'),
          isSortable: false,
        },
        {
          key: 'statistics',
          label: __('Clicked, Opened', 'mailpoet'),
          display: mailpoetTrackingEnabled,
          isSortable: false,
        },
        {
          key: 'sent_at',
          label: __('Sent on', 'mailpoet'),
          isSortable: true,
        },
      ];


function transformToTableCardRows(standard) {
  return standard.map(item => {
    const totalStats = `${item.statistics_clicked}%, ${item.statistics_opened}%`;
    return [
      { display: item.subject, value: item.subject },
      { display: item.status, value: item.status },
      { display: item.segment_names.join(', '), value: item.segment_names.join(', ') },
      { display: totalStats.toString(), value: totalStats },
      { display: item.sent_at ? item.sent_at : 'Not sent', value: item.sent_at || '' },
    ];
  });
}


export function ListingTabContent() {
    const newsletterStandardData = useSelect((select) => select(storeName).getStandardNewsletters(), []);
    const newsletterStandardRows = transformToTableCardRows(newsletterStandardData);
    const newsletterStandardLoading = useSelect((select) => select(storeName).getStandardNewsletterLoading(), []);

    const rowClasses = classnames(
        'manage-column',
        'column-primary',
        'has-row-actions',
      );

    return (
      <div className={rowClasses}>
        <div>
          <pre> {JSON.stringify(newsletterStandardRows)} </pre>
          <pre> {newsletterStandardLoading ? "true" : "false"} </pre>

          <TableCard
            //title="Revenue last weeks"
            className="mailpoet-listing-table"
            isLoading={ newsletterStandardLoading }
            rows={ newsletterStandardRows }
            headers={ NEWSLETTER_STANDARD_HEADERS }
            query={ { page: 2 } }
            rowsPerPage={ 7 }
            totalRows={ 10 }
         //   summary={ summary }
        />
        </div>
        
      </div>
    );
  } 