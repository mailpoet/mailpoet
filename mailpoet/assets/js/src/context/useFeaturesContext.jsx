import { useMemo } from 'react';

export default function useFeaturesContext(data) {
  return useMemo(() => {
    const flags = data.mailpoet_feature_flags;
    const isSupported = (feature) => flags[feature] || false;
    return { isSupported };
  }, [data]);
}
