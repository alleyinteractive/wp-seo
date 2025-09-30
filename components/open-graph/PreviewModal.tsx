import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import { Modal, TabPanel } from '@wordpress/components';
import SocialPreview from './SocialPreview';
import SearchPreview from './SearchPreview';

interface PreviewModalProps {
  onClose: () => void;
  openGraphTitle: string;
  openGraphDescription: string;
  openGraphImageId: number;
}

function PreviewModal({
  onClose,
  openGraphTitle,
  openGraphDescription,
  openGraphImageId,
}: PreviewModalProps) {
  const postType = select('core/editor').getCurrentPostType();
  const postTitle = select('core/editor').getEditedPostAttribute('title');
  const postLink = select('core/editor').getEditedPostAttribute('link');
  const postModified = select('core/editor').getEditedPostAttribute('modified');
  const postExcerpt = select('core/editor').getEditedPostAttribute('excerpt');
  const featuredImageId = select('core/editor').getEditedPostAttribute('featured_media');
  const siteData = select('core').getEditedEntityRecord('root', 'site');

  // Use open graph title, fall back to current post title
  const previewTitle = openGraphTitle || postTitle;
  // If title is longer than 60 characters, truncate
  const searchTitle = previewTitle.length > 60 ? `${previewTitle.substring(0, 60).trim()}…` : previewTitle;

  // Use open graph description, fall back to custom excerpt or auto-generated excerpt
  const previewDescription = openGraphDescription || postExcerpt.replace(/<\/?[^>]+(>|$)/g, '');
  // If description is longer than 160 characters, truncate
  const searchDescription = previewDescription.length > 160 ? `${previewDescription.substring(0, 160).trim()}…` : previewDescription;

  // Use open graph image, fall back to featured image
  const previewImageId = openGraphImageId || featuredImageId;

  const siteTitle = siteData.title;
  // Remove prototcol from site URL
  const siteUrl = siteData.url.replace(/(^\w+:|^)\/\//, '');
  const siteIcon = siteData.site_icon;
  // If the site has no timezone set, use the browser's current timezone
  const siteTimezone = siteData.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;

  return (
    <Modal
      onRequestClose={onClose}
      className="open-graph-preview"
      title={__('Open Graph Preview', 'wp-seo')}
    >
      <TabPanel
        onSelect={() => { }}
        tabs={[
          {
            name: 'search',
            title: 'Search',
            content: <SearchPreview
              title={searchTitle}
              description={searchDescription}
              imageId={previewImageId}
              link={postLink}
              date={postModified}
              postType={postType}
              siteIcon={siteIcon}
              siteTitle={siteTitle}
              siteTimezone={siteTimezone}
            />,
          },
          {
            name: 'social',
            title: 'Social',
            content: <SocialPreview
              title={previewTitle}
              description={previewDescription}
              imageId={previewImageId}
              siteUrl={siteUrl}
            />,
          },
        ]}
      >
        {({ content }) => (
          content
        )}
      </TabPanel>
    </Modal>
  );
}

export default PreviewModal;
