import { date as wpDate } from '@wordpress/date';
import { useMedia } from '@alleyinteractive/block-editor-tools';

interface SearchPreviewProps {
  title: string;
  description: string;
  imageId?: number;
  date: string;
  link: string;
  postType: string;
  siteTitle: string;
  siteIcon?: string;
  siteTimezone: string;
}

function SearchPreview({
  title,
  description,
  imageId,
  date,
  link,
  postType,
  siteTitle,
  siteIcon,
  siteTimezone,
}: SearchPreviewProps) {
  const icon: any = useMedia(siteIcon);
  const searchImage: any = useMedia(imageId);

  const previewDate = postType === 'post' ? (
    <span className="search-preview-date">{wpDate('F j, Y', date, siteTimezone)}</span>
  ) : null;

  return (
    <div className="wp-seo-search-preview">
      <div className="search-preview-container">
        <div className="search-preview-header">
          {icon ? (
            <div className="search-preview-icon">
              <img src={icon?.media_details?.sizes?.thumbnail?.source_url} alt="" />
            </div>
          ) : null}
          <div className="search-preview-site">
            <p>{siteTitle}</p>
            <p>{link}</p>
          </div>
        </div>

        <div className="search-preview-text">
          <h2>{title}</h2>
          <p>
            {previewDate}
            {previewDate && description ? (
              <span> &mdash; </span>
            ) : null}
            {description}
          </p>
        </div>
      </div>

      {searchImage ? (
        <div className="search-preview-image">
          <img src={searchImage?.media_details?.sizes?.medium?.source_url} alt="" />
        </div>
      ) : null}
    </div>
  );
}

export default SearchPreview;
