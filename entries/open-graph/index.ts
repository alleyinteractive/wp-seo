/**
 * Entry for open-graph slotfill.
 *
 * Register slotfills in child folders under the current directory and import
 * them here.
 */

import { registerPlugin } from '@wordpress/plugins';
import OpenGraphSlotfill from './OpenGraphSlotfill';

registerPlugin('wp-seo-open-graph', { render: OpenGraphSlotfill });
