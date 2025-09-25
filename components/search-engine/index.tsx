import { __ } from '@wordpress/i18n';
import {
  PanelBody,
  TextareaControl,
  TextControl,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import {
  usePostMetaValue,
} from '@alleyinteractive/block-editor-tools';

function SearchEngineSlotfill() {
  const [title, setTitle] = usePostMetaValue('_meta_title');
  const [description, setDescription] = usePostMetaValue('_meta_description');

  return (
    <PanelBody
      title={__('Search Engine', 'wp-seo')}
      initialOpen
    >
      <VStack spacing="3">
        <TextControl
          label={__('Title', 'wp-seo')}
          onChange={(next) => setTitle(next)}
          value={title}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
        <TextareaControl
          label={__('Description', 'wp-seo')}
          onChange={(next) => setDescription(next)}
          value={description}
          __nextHasNoMarginBottom
        />
      </VStack>
    </PanelBody>
  );
}

export default SearchEngineSlotfill;
