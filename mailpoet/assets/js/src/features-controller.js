export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',
  FEATURE_SEND_BY_TIMEZONE: 'send_by_timezone',
  FEATURE_EMAIL_PRESETS: 'email_presets',

  isSupported: (feature) => {
    return (config && config[feature]) || false;
  },
});
