import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { useParams, useLocation } from 'react-router-dom';

import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { Form } from './form';
import { storeName } from './store';
import { BackButton, PageHeader } from '../../common/page-header';

export function Editor(): JSX.Element {
  const matchParams = useParams();

  const { pageLoaded, pageUnloaded } = useDispatch(storeName);
  const previousPage: string = useSelect((select) =>
    select(storeName).getPreviousPage(),
  );
  const returnPage: string = previousPage || '/';

  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const newsletterId = params.get('newsletterId') || null;

  useEffect(() => {
    void pageLoaded(matchParams.id);

    return () => {
      void pageUnloaded();
    };
  }, [matchParams.id, pageLoaded, pageUnloaded]);

  const isNewSegment =
    matchParams.id === undefined || Number.isNaN(Number(matchParams.id));

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBoundary hideScreenOptions />

      <PageHeader
        heading={
          matchParams.id
            ? __('Edit Segment', 'mailpoet')
            : __('Add New Segment', 'mailpoet')
        }
        headingPrefix={
          <BackButton
            id="mailpoet-segments-back-button"
            href={`#${returnPage}`}
            label={__('Return to previous page', 'mailpoet')}
            onClick={(event) => {
              if (newsletterId) {
                event.preventDefault();
                window.location.href = `admin.php?page=mailpoet-newsletters#/send/${newsletterId}`;
              }
            }}
          />
        }
      />

      <Form isNewSegment={isNewSegment} newsletterId={newsletterId} />
    </div>
  );
}
Editor.displayName = 'SegmentEditor';
