
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
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { ErrorBoundary, withBoundary } from '../../common';
import { SegmentTags, FilterSegmentTag } from '../../common/tag/tags';


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



const selectSegementsFromIds = (segment_ids, segments) => {
  return segment_ids.map(segment_id => segments.find(segment => segment.id === segment_id));
}


// TODO receive meta from the store
function transformToTableCardRows(standard, segments, meta = { mta_log: {}, current_time: '' }) {
  return standard.map(newsletter => {

    return [
      { display: newsletter.subject, value: newsletter.subject },
      { display: <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />, value: newsletter.status },
      { display: <div
          className="column mailpoet-hide-on-mobile"
          data-colname={__('Lists', 'mailpoet')}>
          <ErrorBoundary>
            <SegmentTags segments={selectSegementsFromIds(newsletter.segment_ids, segments)} dimension="large" />
            <FilterSegmentTag newsletter={newsletter} dimension="large" />
          </ErrorBoundary>
        </div>, value: newsletter.segment_ids },
      { display: <Statistics newsletter={newsletter} currentTime={meta.current_time} />, value: newsletter.statistics },
      { display: newsletter.sent_at ? newsletter.sent_at : 'Not sent', value: newsletter.sent_at || '' },
    ];
  });
}



export function StandardTabContent() {
    const newsletterStandardData = useSelect((select) => select(storeName).getStandardNewsletters(), []);
    const standardSegmentsData = useSelect((select) => select(storeName).getStandardSegments(), []);
    const newsletterStandardRows = transformToTableCardRows(newsletterStandardData, standardSegmentsData);

    const newsletterStandardLoading = useSelect((select) => select(storeName).getStandardNewsletterLoading(), []);

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
         //   summary={ summary }
        />
        </div>
        
      </div>
    );
  } 