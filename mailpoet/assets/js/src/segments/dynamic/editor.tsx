import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { useRouteMatch, useLocation } from 'react-router-dom';

import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { Form } from './form';
import { storeName } from './store';
import { BackButton, PageHeader } from '../../common/page-header';

export function Editor(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();

  const { pageLoaded, pageUnloaded } = useDispatch(storeName);
  const previousPage: string = useSelect((select) =>
    select(storeName).getPreviousPage(),
  );
  const returnPage: string = previousPage || '/';

  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const newsletterId = params.get('newsletterId') || null;

  useEffect(() => {
    void pageLoaded(match.params.id);

    return () => {
      void pageUnloaded();
    };
  }, [match.params.id, pageLoaded, pageUnloaded]);

  const isNewSegment =
    match.params.id === undefined || Number.isNaN(Number(match.params.id));

  return (
    <div className="mailpoet-main-container">
      <TopBarWithBeamer />
      <HideScreenOptions />

      <PageHeader
        heading={
          match.params.id
            ? __('Edit segment', 'mailpoet')
            : __('New segment', 'mailpoet')
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
