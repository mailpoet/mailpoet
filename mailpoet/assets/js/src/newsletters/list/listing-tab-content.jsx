
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


function renderSegments(segment_ids = [], segments = []) {
  return (
    <div className="mailpoet-tags">
      {segment_ids.map(segmentId => {
        const segment = segments.find(s => s.id === segmentId);
        return (
          <div key={segmentId}>
            <a href={`admin.php?page=mailpoet-subscribers#/filter[segment=${segmentId}]`}>
              <div className="mailpoet-tag mailpoet-tag-list mailpoet-tag-large">
                {segment ? segment.name : ''}
              </div>
            </a>
          </div>
        );
      })}
    </div>
  );
}


function renderOpenedClickedStats(clicked, opened) {
  return (
    <div className="mailpoet-listing-stats">
      <div className="mailpoet-listing-stats-opened-clicked">
        <div className="mailpoet-listing-stats-percentages">
          {clicked.toFixed(1)}%<br />
          <span className="mailpoet-listing-stats-percentages-opens">{opened.toFixed(1)}%</span>
        </div>
      </div>
    </div>
  );
}


function transformToTableCardRows(standard, segments) {
  return standard.map(item => {
    return [
      { display: item.subject, value: item.subject },
      { display: item.status, value: item.status },
      { display: renderSegments(item.segment_ids, segments), value: item.segment_ids },
      { display: renderOpenedClickedStats(item.statistics_clicked, item.statistics_opened), value: [item.statistics_clicked, item.statistics_opened] },
      { display: item.sent_at ? item.sent_at : 'Not sent', value: item.sent_at || '' },
    ];
  });
}



export function ListingTabContent() {
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