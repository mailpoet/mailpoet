export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',

  isSupported: (feature) => {
    return config[feature] || false;
  },
});
