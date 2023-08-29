import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { Link, useRouteMatch, useLocation } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { Background } from 'common/background/background';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { Form } from './form';

import { storeName } from './store';

export function Editor(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();

  const { pageLoaded, pageUnloaded } = useDispatch(storeName);
  const previousPage = useSelect((select) =>
    select(storeName).getPreviousPage(),
  );
  const returnPage = previousPage || '/';

  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const newsletterId = params.get('newsletterId') || null;

  useEffect(() => {
    void pageLoaded(match.params.id);

    return () => {
      void pageUnloaded();
    };
  }, [match.params.id, pageLoaded, pageUnloaded]);

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to={returnPage}
        >
          {MailPoet.I18n.t('backToList')}
        </Link>
      </Heading>

      <Form
        segmentId={Number(match.params.id)}
        newsletterId={Number(newsletterId)}
      />
    </>
  );
}
Editor.displayName = 'SegmentEditor';
