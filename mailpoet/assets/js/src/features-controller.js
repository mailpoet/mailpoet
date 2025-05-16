export const FeaturesController = (config) => ({
  FEATURE_BRAND_TEMPLATES: 'brand_templates',
  FEATURE_ODIE_CHATBOT: 'odie_chatbot',

  isSupported: (feature) => {
    return config[feature] || false;
  },
});
