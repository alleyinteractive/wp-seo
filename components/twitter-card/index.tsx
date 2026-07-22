import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import {
  PanelBody,
  TextareaControl,
  TextControl,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import {
  ImagePicker,
  usePostMetaValue,
} from '@alleyinteractive/block-editor-tools';

function TwitterCardSlotfill() {
  const currentPostType = select('core/editor').getCurrentPostType();
  const postType = select('core').getEntityRecord('root', 'postType', currentPostType);

  const [title, setTitle] = usePostMetaValue('wp_seo_twitter_card_title');
  const [description, setDescription] = usePostMetaValue('wp_seo_twitter_card_description');
  const [image, setImage] = usePostMetaValue('wp_seo_twitter_card_image');

  if (!postType?.supports['wp-seo-twitter-card']) {
    return null;
  }

  return (
    <PanelBody
      title={__('Twitter Card', 'wp-seo')}
      initialOpen
    >
      <VStack spacing="3">
        <TextControl
          label={__('Title', 'wp-seo')}
          onChange={setTitle}
          value={title}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
        <TextareaControl
          label={__('Description', 'wp-seo')}
          onChange={setDescription}
          value={description}
          __nextHasNoMarginBottom
        />
        <div>
          <p style={{
            fontSize: '11px',
            marginBottom: '0.5rem',
            color: '#1E1E1E',
            textTransform: 'uppercase',
            fontWeight: '500',
          }}
          >
            {__('Image', 'wp-seo')}
          </p>
          <ImagePicker
            onReset={() => setImage(0)}
            onUpdate={({ id: next }) => setImage(next)}
            value={image}
          />
        </div>
      </VStack>
    </PanelBody>
  );
}

export default TwitterCardSlotfill;
