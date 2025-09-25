import { useMedia } from '@alleyinteractive/block-editor-tools';

interface SocialPreviewProps {
  title: string;
  description: string;
  imageId?: number;
  siteUrl: string;
}

function SocialPreview({
  title,
  description,
  imageId,
  siteUrl,
}: SocialPreviewProps) {
  const socialImage: any = useMedia(imageId);

  return (
    <div className="wp-seo-social-preview">
      <div className="social-preview-container">
        {socialImage ? (
          <img src={socialImage.media_details?.sizes?.large?.source_url} alt="" />
        ) : null}

        <div className="social-preview-text">
          <p className="social-preview-url">{siteUrl}</p>
          <h2>{title}</h2>
          <p>{description}</p>
        </div>
      </div>
    </div>
  );
}

export default SocialPreview;
