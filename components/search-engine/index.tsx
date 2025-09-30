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
  const [title, setTitle] = usePostMetaValue('search_engine_title');
  const [description, setDescription] = usePostMetaValue('search_engine_description');

  return (
    <PanelBody
      title={__('Search Engine', 'wp-seo')}
      initialOpen
    >
      <VStack spacing="3">
        <div>
          <TextControl
            label={__('Title', 'wp-seo')}
            onChange={setTitle}
            value={title}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <p style={{ fontSize: '0.75rem', marginTop: '0.2rem', color: '#757575' }}>
            {`Character count: ${title.length}`}
          </p>
        </div>
        <div>
          <TextareaControl
            label={__('Description', 'wp-seo')}
            onChange={(next) => setDescription(next)}
            value={description}
            __nextHasNoMarginBottom
          />
          <p style={{ fontSize: '0.75rem', marginTop: '0.2rem', color: '#757575' }}>
            {`Character count: ${description.length}`}
          </p>
        </div>
      </VStack>
    </PanelBody>
  );
}

export default SearchEngineSlotfill;
