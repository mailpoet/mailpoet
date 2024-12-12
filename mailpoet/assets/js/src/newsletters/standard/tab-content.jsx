
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { TableCard } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { storeName } from '../store/constants';

import { getRow } from './get-row';

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


export function StandardTabContent() {
    const newsletterStandardData = useSelect((select) => select(storeName).getStandardNewsletters(), []);
    const metaData = useSelect((select) => select(storeName).getMeta(), []);

    const newsletterStandardLoading = useSelect((select) => select(storeName).getStandardNewsletterLoading(), []);
    const newsletterStandardRows = newsletterStandardData.map(newsletter =>  getRow(newsletter, metaData) );

    const rowClasses = classnames(
        'manage-column',
        'column-primary',
        'has-row-actions',
      );

    return (
      <div className={rowClasses}>
        <div>
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
        />
        </div>
        
      </div>
    );
  } 