import React from 'react';

export default function useSegmentsContext(data) {
  return React.useMemo(() => ({
    all: data.mailpoetSegments,
    // eslint-disable-next-line no-param-reassign
    updateAll: (segments) => { data.mailpoetSegments = segments; },
    /* Instead of using "data" as dependency we are using more specific
      "data.mailpoetSegments" to avoid potent unwanted re-renders */
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }), [data.mailpoetSegments]);
}
