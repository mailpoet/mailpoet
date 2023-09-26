import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { Link, useRouteMatch } from 'react-router-dom';

import { MailPoet } from 'mailpoet';
import { Background } from 'common/background/background';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { Form } from './form';

import { storeName } from './store';

export function Editor(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();

  const { pageLoaded, pageUnloaded } = useDispatch(storeName);
  const previousPage = useSelect((select) =>
    select(storeName).getPreviousPage(),
  );
  const returnPage = previousPage || '/';

  useEffect(() => {
    void pageLoaded(match.params.id);

    return () => {
      void pageUnloaded();
    };
  }, [match.params.id, pageLoaded, pageUnloaded]);

  const isNewSegment =
    match.params.id === undefined || Number.isNaN(Number(match.params.id));

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

      <Form isNewSegment={isNewSegment} />
    </>
  );
}
Editor.displayName = 'SegmentEditor';
