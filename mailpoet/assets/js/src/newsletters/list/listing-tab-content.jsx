
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { TableCard,TablePlaceholder } from '@woocommerce/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { storeName, NEWSLETTER_STANDARD_HEADERS } from '../store/constants';








  

export function ListingTabContent() {
    const newsletterData = useSelect((select) => select(storeName).getNewsletterRows(), []);
    const newsletterLoading = useSelect((select) => select(storeName).getNewsletterLoading(), []);

    const rowClasses = classnames(
        'manage-column',
        'column-primary',
        'has-row-actions',
      );

    return (
      <div className={rowClasses}>
        <div>
          <pre> {JSON.stringify(newsletterData)} </pre>
          <pre> {newsletterLoading ? "true" : "false"} </pre>

          <TableCard
            //title="Revenue last weeks"
            className="mailpoet-listing-table"
            isLoading={ newsletterLoading }
            rows={ newsletterData }
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