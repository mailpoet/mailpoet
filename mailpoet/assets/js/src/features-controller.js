export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',
  FEATURE_EMAIL_PRESETS: 'email_presets',

  isSupported: (feature) => {
    return (config && config[feature]) || false;
  },
});
