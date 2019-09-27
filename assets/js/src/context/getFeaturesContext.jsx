export default function getFeaturesContext(data) {
  const flags = data.mailpoet_feature_flags;
  const isSupported = (feature) => flags[feature] || false;
  return { isSupported };
}
