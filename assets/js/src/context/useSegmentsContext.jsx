import React from 'react';

export default function useSegmentsContext(data) {
  return React.useMemo(() => ({
    all: data.mailpoetSegments,
    // eslint-disable-next-line no-param-reassign
    updateAll: (segments) => { data.mailpoetSegments = segments; },
    /* eslint-disable-next-line react-hooks/exhaustive-deps -- No change for safety reason */
  }), [data.mailpoetSegments]);
}
