import React from 'react';

export default function useSegmentsContext(data) {
  return React.useMemo(() => ({
    all: data.mailpoetSegments,
  }), [data]);
}
