/* eslint-disable import/no-unresolved */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import OpenGraphSlotfill from '@/components/open-graph';
import SearchEngineSlotfill from '@/components/search-engine';

function MetaSidebar() {
  return (
    <PluginSidebar
      name="plugin-sidebar-wp-seo"
      icon="share"
      title={__('WP SEO', 'wp-seo')}
    >
      <SearchEngineSlotfill />
      <OpenGraphSlotfill />
    </PluginSidebar>
  );
}

registerPlugin('wp-seo-meta-sidebar', { render: MetaSidebar });
