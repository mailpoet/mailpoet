export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',
  FEATURE_SEND_BY_TIMEZONE: 'send_by_timezone',

  isSupported: (feature) => {
    return (config && config[feature]) || false;
  },
});
