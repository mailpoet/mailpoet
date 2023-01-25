import { MailPoet } from 'mailpoet';
import { ContentSection } from './content-section';
import { ResourcePost } from './resource-post';

export function Resources(): JSX.Element {
  const posts = [
    <ResourcePost
      key="createAnEmail"
      link="https://kb.mailpoet.com/article/141-create-an-email-types-of-campaigns?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('createAnEmailAbstract')}
      title={MailPoet.I18n.t('createAnEmailTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_form.png`}
    />,
    <ResourcePost
      key="createAForm"
      link="https://kb.mailpoet.com/article/297-create-a-form-with-our-new-editor?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('createAFormAbstract')}
      title={MailPoet.I18n.t('createAFormTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_email.png`}
    />,
    <ResourcePost
      key="segmentationGuide"
      link="https://www.mailpoet.com/blog/email-segmentation/?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('segmentationGuideAbstract')}
      title={MailPoet.I18n.t('segmentationGuideTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_form.png`}
    />,
    <ResourcePost
      key="reEngagement"
      link="https://www.mailpoet.com/blog/re-engagement-emails/?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('reEngagementAbstract')}
      title={MailPoet.I18n.t('reEngagementTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_email.png`}
    />,
    <ResourcePost
      key="marketingStrategy"
      link="https://www.mailpoet.com/blog/newsletter-marketing-strategy/?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('marketingStrategyAbstract')}
      title={MailPoet.I18n.t('marketingStrategyTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_form.png`}
    />,
    <ResourcePost
      key="promotingSales"
      link="https://www.mailpoet.com/blog/how-to-promote-your-sales-with-email-marketing-mailpoet-woocommerce-segmentation/?utm_source=plugin&utm_medium=homepage&utm_campaign=resources"
      abstract={MailPoet.I18n.t('promotingSalesAbstract')}
      title={MailPoet.I18n.t('promotingSalesTitle')}
      imgSrc={`${MailPoet.cdnUrl}homepage/resources/add_email.png`}
    />,
  ];
  return (
    <ContentSection
      className="mailpoet-homepage-resources"
      heading={MailPoet.I18n.t('learnMoreAboutEmailMarketing')}
    >
      {posts.map((post) => post)}
    </ContentSection>
  );
}
