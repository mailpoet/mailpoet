import React from 'react';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';
import {
  Segment,
  SegmentConnectTypes,
} from './types';

interface Props {
  index: number;
}

const FilterSeparator: React.FunctionComponent<Props> = ({ index }) => {
  const segment: Segment = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  if (segment.filters.length <= 1 || index === segment.filters.length - 1) {
    return <div className="mailpoet-gap" />;
  }

  return (
    <p>
      <span className="mailpoet-gap" />
      <strong>
        {segment.filters_connect === SegmentConnectTypes.AND
          ? MailPoet.I18n.t('filterConnectAnd').toUpperCase()
          : MailPoet.I18n.t('filterConnectOr').toUpperCase()}
      </strong>
    </p>
  );
};

export { FilterSeparator };
