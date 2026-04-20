export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',
  FEATURE_BIRTHDAY_EMAILS: 'birthday_emails',

  isSupported: (feature) => {
    return config[feature] || false;
  },
});
