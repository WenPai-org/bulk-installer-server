<?php
/**
 * Admin page template for Collection Manager
 */
?>
<div class="wrap bis-wrap">
    <h1><?php echo esc_html__('Collection Manager', 'bulk-installer-server'); ?>
        <span class="bis-version-tag"><?php printf(esc_html__('Version: %s', 'bulk-installer-server'), esc_html(BIS_VERSION)); ?></span>
    </h1>

    <div class="bis-container">
        <div class="bis-sidebar">
            <div class="bis-card">
                <h3><?php _e('Your Collections', 'bulk-installer-server'); ?></h3>
                <p><?php _e('Create and manage plugin & theme collections for bulk installation.', 'bulk-installer-server'); ?></p>

                <div class="bis-collections-list">
                    <?php if (empty($collections)) : ?>
                        <p class="bis-empty-message"><?php _e('No collections found. Create one now!', 'bulk-installer-server'); ?></p>
                    <?php else : ?>
                        <ul>
                            <?php foreach ($collections as $slug => $collection) : ?>
                                <li class="bis-collection-item" data-id="<?php echo esc_attr($slug); ?>">
                                    <div class="bis-collection-icon">
                                        <span class="dashicons <?php echo esc_attr($collection['icon'] ?? 'dashicons-admin-plugins'); ?>"></span>
                                    </div>
                                    <div class="bis-collection-details">
                                        <h4><?php echo esc_html($collection['name']); ?></h4>
                                        <div class="bis-collection-meta">
                                            <?php
                                            $plugin_count = 0;
                                            $theme_count = 0;

                                            if (!empty($collection['plugins'])) {
                                                foreach ($collection['plugins'] as $source => $plugins) {
                                                    $plugin_count += count($plugins);
                                                }
                                            }

                                            if (!empty($collection['themes'])) {
                                                foreach ($collection['themes'] as $source => $themes) {
                                                    $theme_count += count($themes);
                                                }
                                            }

                                            $category = !empty($collection['category']) ? $collection['category'] : '';
                                            $level = !empty($collection['level']) ? $collection['level'] : '';
                                            ?>
                                            <span class="bis-item-count bis-plugin-count">
                                                <span class="dashicons dashicons-admin-plugins"></span>
                                                <?php echo sprintf(_n('%s plugin', '%s plugins', $plugin_count, 'bulk-installer-server'), $plugin_count); ?>
                                            </span>
                                            <span class="bis-item-count bis-theme-count">
                                                <span class="dashicons dashicons-admin-appearance"></span>
                                                <?php echo sprintf(_n('%s theme', '%s themes', $theme_count, 'bulk-installer-server'), $theme_count); ?>
                                            </span>
                                            <?php if ($category) : ?>
                                                <span class="bis-meta-tag bis-category-tag"><?php echo esc_html(ucfirst($category)); ?></span>
                                            <?php endif; ?>
                                            <?php if ($level) : ?>
                                                <span class="bis-meta-tag bis-level-tag"><?php echo esc_html(ucfirst($level)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="bis-collection-actions">
                                        <button type="button" class="bis-edit-collection button button-small" data-id="<?php echo esc_attr($slug); ?>">
                                            <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'bulk-installer-server'); ?>
                                        </button>
                                        <button type="button" class="bis-delete-collection button button-small" data-id="<?php echo esc_attr($slug); ?>">
                                            <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="bis-actions">
                    <button type="button" class="button button-primary bis-new-collection">
                        <span class="dashicons dashicons-plus"></span> <?php _e('Create New Collection', 'bulk-installer-server'); ?>
                    </button>
                    <button type="button" class="button bis-import-collection">
                        <span class="dashicons dashicons-upload"></span> <?php _e('Import Collection', 'bulk-installer-server'); ?>
                    </button>
                </div>
            </div>

            <div class="bis-card">
                <h3><?php _e('Export Collections', 'bulk-installer-server'); ?></h3>
                <p><?php _e('Export collections to JSON for use in other sites:', 'bulk-installer-server'); ?></p>

                <div class="bis-export-options">
                    <div class="bis-form-row">
                        <label class="bis-export-label"><?php _e('Export Format:', 'bulk-installer-server'); ?></label>
                        <select id="bis-export-format" class="bis-select">
                            <option value="all"><?php _e('All Collections (Complete JSON)', 'bulk-installer-server'); ?></option>
                            <option value="selected"><?php _e('Selected Collection Only', 'bulk-installer-server'); ?></option>
                        </select>
                    </div>

                    <div id="bis-export-collection-select" class="bis-form-row" style="display: none;">
                        <label class="bis-export-label"><?php _e('Collection:', 'bulk-installer-server'); ?></label>
                        <select id="bis-export-collection" class="bis-select">
                            <?php if (!empty($collections)) : ?>
                                <?php foreach ($collections as $slug => $collection) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($collection['name']); ?></option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value=""><?php _e('No collections available', 'bulk-installer-server'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="button" class="button button-secondary bis-export-collections">
                        <span class="dashicons dashicons-download"></span> <?php _e('Export JSON', 'bulk-installer-server'); ?>
                    </button>
                </div>
            </div>

            <div class="bis-card">
                <h3><?php _e('Collections API', 'bulk-installer-server'); ?></h3>
                <p><?php _e('Access your collections via these API URLs:', 'bulk-installer-server'); ?></p>

                <div class="bis-api-info">
                    <div class="bis-api-endpoint">
                        <label><?php _e('All Collections:', 'bulk-installer-server'); ?></label>
                        <div class="bis-endpoint-url">
                            <code><?php echo esc_url(rest_url('bulk-installer-server/v1/collections')); ?></code>
                            <button type="button" class="button button-small bis-copy-url" data-url="<?php echo esc_url(rest_url('bulk-installer-server/v1/collections')); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($collections)) : ?>
                        <div class="bis-collection-endpoints">
                            <label><?php _e('Individual Collections:', 'bulk-installer-server'); ?></label>
                            <?php foreach ($collections as $slug => $collection) : ?>
                                <div class="bis-endpoint-url">
                                    <strong><?php echo esc_html($collection['name']); ?>:</strong>
                                    <code><?php echo esc_url(bis_get_collection_json_url($slug)); ?></code>
                                    <button type="button" class="button button-small bis-copy-url" data-url="<?php echo esc_url(bis_get_collection_json_url($slug)); ?>">
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bis-client-instructions">
                    <h4><?php _e('How to use with Bulk Plugin Installer', 'bulk-installer-server'); ?></h4>
                    <ol>
                        <li><?php _e('Copy the URL for "All Collections" or an individual collection', 'bulk-installer-server'); ?></li>
                        <li><?php _e('In the client site, go to Bulk Plugin Installer > Settings', 'bulk-installer-server'); ?></li>
                        <li><?php _e('Add this URL as a Collection Source', 'bulk-installer-server'); ?></li>
                        <li><?php _e('Your collections will be available in the Collections tab', 'bulk-installer-server'); ?></li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="bis-content">
            <div class="bis-card" id="bis-editor-card" style="display: none;">
                <h2 id="bis-editor-title"><?php _e('Create New Collection', 'bulk-installer-server'); ?></h2>

                <form id="bis-collection-form">
                    <input type="hidden" id="bis-collection-id" name="collection_id" value="">

                    <div class="bis-form-section">
                        <h3 class="bis-section-title"><?php _e('Basic Information', 'bulk-installer-server'); ?></h3>

                        <div class="bis-form-row">
                            <label for="bis-collection-name"><?php _e('Collection Name:', 'bulk-installer-server'); ?> <span class="bis-required">*</span></label>
                            <input type="text" id="bis-collection-name" name="collection_name" class="regular-text" required>
                        </div>

                        <!-- 新增：显示 slug 信息 -->
                        <div class="bis-form-row bis-slug-display" style="display: none;">
                            <label><?php _e('Collection Slug:', 'bulk-installer-server'); ?></label>
                            <div class="bis-slug-info-wrapper">
                                <code class="bis-slug-info"></code>
                                <button type="button" class="button button-small bis-regenerate-slug" title="<?php esc_attr_e('Regenerate slug based on collection name. Warning: this will change API URLs', 'bulk-installer-server'); ?>">
                                    <span class="dashicons dashicons-update-alt"></span> <?php _e('Regenerate', 'bulk-installer-server'); ?>
                                </button>
                                <span class="bis-tooltip" data-tooltip="<?php esc_attr_e('This is the unique identifier used in API URLs. It is automatically generated from the collection name.', 'bulk-installer-server'); ?>">
                                    <span class="dashicons dashicons-info-outline"></span>
                                </span>
                            </div>
                        </div>

                        <div class="bis-form-row">
                            <label for="bis-collection-description"><?php _e('Description:', 'bulk-installer-server'); ?></label>
                            <textarea id="bis-collection-description" name="collection_description" rows="3" class="large-text"></textarea>
                        </div>

                        <div class="bis-form-grid">
                            <div class="bis-form-group">
                                <label for="bis-collection-icon"><?php _e('Icon:', 'bulk-installer-server'); ?></label>
                                <div class="bis-icon-select-wrapper">
                                    <select id="bis-collection-icon" name="collection_icon" class="bis-select">
                                        <option value="dashicons-admin-plugins"><?php _e('Plugins', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-admin-appearance"><?php _e('Themes', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-building"><?php _e('Business', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-cart"><?php _e('E-Commerce', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-groups"><?php _e('Community', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-edit"><?php _e('Blog', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-format-gallery"><?php _e('Portfolio', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-calendar"><?php _e('Events', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-admin-users"><?php _e('Membership', 'bulk-installer-server'); ?></option>
                                        <option value="dashicons-networking"><?php _e('Network', 'bulk-installer-server'); ?></option>
                                    </select>
                                    <span class="bis-selected-icon dashicons dashicons-admin-plugins"></span>
                                </div>
                            </div>

                            <div class="bis-form-group">
                                <label for="bis-collection-category"><?php _e('Category:', 'bulk-installer-server'); ?></label>
                                <select id="bis-collection-category" name="collection_category" class="bis-select">
                                    <option value="business"><?php _e('Business', 'bulk-installer-server'); ?></option>
                                    <option value="ecommerce"><?php _e('E-Commerce', 'bulk-installer-server'); ?></option>
                                    <option value="community"><?php _e('Community', 'bulk-installer-server'); ?></option>
                                    <option value="content"><?php _e('Content', 'bulk-installer-server'); ?></option>
                                    <option value="utilities"><?php _e('Utilities', 'bulk-installer-server'); ?></option>
                                    <option value="development"><?php _e('Development', 'bulk-installer-server'); ?></option>
                                    <option value="other"><?php _e('Other', 'bulk-installer-server'); ?></option>
                                </select>
                            </div>

                            <div class="bis-form-group">
                                <label for="bis-collection-level"><?php _e('Level:', 'bulk-installer-server'); ?></label>
                                <select id="bis-collection-level" name="collection_level" class="bis-select">
                                    <option value="beginner"><?php _e('Beginner', 'bulk-installer-server'); ?></option>
                                    <option value="intermediate"><?php _e('Intermediate', 'bulk-installer-server'); ?></option>
                                    <option value="advanced"><?php _e('Advanced', 'bulk-installer-server'); ?></option>
                                </select>
                            </div>

                            <div class="bis-form-group">
                                <label for="bis-collection-author"><?php _e('Author:', 'bulk-installer-server'); ?></label>
                                <input type="text" id="bis-collection-author" name="collection_author" class="regular-text" value="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            </div>
                        </div>

                        <div class="bis-form-row">
                            <label for="bis-collection-screenshot"><?php _e('Screenshot:', 'bulk-installer-server'); ?></label>
                            <div class="bis-screenshot-field">
                                <input type="text" id="bis-collection-screenshot" name="collection_screenshot" class="regular-text" placeholder="<?php esc_attr_e('Enter URL or upload image', 'bulk-installer-server'); ?>">
                                <button type="button" class="button bis-upload-screenshot">
                                    <span class="dashicons dashicons-upload"></span> <?php _e('Upload', 'bulk-installer-server'); ?>
                                </button>
                            </div>
                            <div id="bis-screenshot-preview" class="bis-screenshot-preview"></div>
                        </div>
                    </div>

                    <div class="bis-form-section">
                        <h3 class="bis-section-title"><?php _e('Plugins & Themes', 'bulk-installer-server'); ?></h3>
                        <p class="bis-section-description"><?php _e('Add the plugins and themes you want to include in this collection.', 'bulk-installer-server'); ?></p>

                        <div class="bis-tabs">
                            <div class="bis-tabs-nav">
                                <button type="button" class="bis-tab active" data-tab="plugins">
                                    <span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugins', 'bulk-installer-server'); ?>
                                </button>
                                <button type="button" class="bis-tab" data-tab="themes">
                                    <span class="dashicons dashicons-admin-appearance"></span> <?php _e('Themes', 'bulk-installer-server'); ?>
                                </button>
                            </div>

                            <div id="bis-tab-plugins" class="bis-tab-content active">
                                <div class="bis-source-sections">
                                    <div class="bis-source-section" data-source="repository">
                                        <h4>
                                            <span class="dashicons dashicons-wordpress"></span>
                                            <?php _e('WordPress.org Repository', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add plugins from the official WordPress.org plugin repository.', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-plugins-repository"></div>
                                        <button type="button" class="button bis-add-item" data-type="plugin" data-source="repository">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add Repository Plugin', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>

                                    <div class="bis-source-section" data-source="wenpai">
                                        <h4>
                                            <span class="dashicons dashicons-share-alt"></span>
                                            <?php _e('WenPai.org Repository', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add plugins from the WenPai.org plugin repository.', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-plugins-wenpai"></div>
                                        <button type="button" class="button bis-add-item" data-type="plugin" data-source="wenpai">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add WenPai Plugin', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>

                                    <div class="bis-source-section" data-source="url">
                                        <h4>
                                            <span class="dashicons dashicons-admin-links"></span>
                                            <?php _e('Remote URLs', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add plugins from direct download URLs (ZIP files).', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-plugins-url"></div>
                                        <button type="button" class="button bis-add-item" data-type="plugin" data-source="url">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add URL Plugin', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="bis-tab-themes" class="bis-tab-content">
                                <div class="bis-source-sections">
                                    <div class="bis-source-section" data-source="repository">
                                        <h4>
                                            <span class="dashicons dashicons-wordpress"></span>
                                            <?php _e('WordPress.org Repository', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add themes from the official WordPress.org theme repository.', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-themes-repository"></div>
                                        <button type="button" class="button bis-add-item" data-type="theme" data-source="repository">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add Repository Theme', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>

                                    <div class="bis-source-section" data-source="wenpai">
                                        <h4>
                                            <span class="dashicons dashicons-share-alt"></span>
                                            <?php _e('WenPai.org Repository', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add themes from the WenPai.org theme repository.', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-themes-wenpai"></div>
                                        <button type="button" class="button bis-add-item" data-type="theme" data-source="wenpai">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add WenPai Theme', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>

                                    <div class="bis-source-section" data-source="url">
                                        <h4>
                                            <span class="dashicons dashicons-admin-links"></span>
                                            <?php _e('Remote URLs', 'bulk-installer-server'); ?>
                                        </h4>
                                        <p class="bis-source-description"><?php _e('Add themes from direct download URLs (ZIP files).', 'bulk-installer-server'); ?></p>
                                        <div class="bis-items-container" id="bis-themes-url"></div>
                                        <button type="button" class="button bis-add-item" data-type="theme" data-source="url">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add URL Theme', 'bulk-installer-server'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bis-form-actions">
                        <button type="submit" class="button button-primary button-large bis-save-collection">
                            <span class="dashicons dashicons-saved"></span> <?php _e('Save Collection', 'bulk-installer-server'); ?>
                        </button>
                        <button type="button" class="button button-large bis-cancel-edit">
                            <span class="dashicons dashicons-no"></span> <?php _e('Cancel', 'bulk-installer-server'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bis-card" id="bis-welcome-card">
                <h2><?php _e('Welcome to Collection Manager', 'bulk-installer-server'); ?></h2>
                <p class="bis-welcome-intro"><?php _e('Create and manage collections of plugins and themes that can be easily installed with the Bulk Plugin Installer client.', 'bulk-installer-server'); ?></p>

                <div class="bis-welcome-content">
                    <div class="bis-welcome-step">
                        <div class="bis-step-icon">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </div>
                        <div class="bis-step-content">
                            <h3><?php _e('Create Collections', 'bulk-installer-server'); ?></h3>
                            <p><?php _e('Build collections of plugins and themes for different website types like e-commerce, business, portfolio, etc.', 'bulk-installer-server'); ?></p>
                        </div>
                    </div>

                    <div class="bis-welcome-step">
                        <div class="bis-step-icon">
                            <span class="dashicons dashicons-rest-api"></span>
                        </div>
                        <div class="bis-step-content">
                            <h3><?php _e('Share via API', 'bulk-installer-server'); ?></h3>
                            <p><?php _e('Your collections are automatically available via the REST API. Simply share the URL with your clients or team.', 'bulk-installer-server'); ?></p>
                        </div>
                    </div>

                    <div class="bis-welcome-step">
                        <div class="bis-step-icon">
                            <span class="dashicons dashicons-download"></span>
                        </div>
                        <div class="bis-step-content">
                            <h3><?php _e('Export and Import', 'bulk-installer-server'); ?></h3>
                            <p><?php _e('Export collections as JSON files that can be imported on other sites or shared with your team.', 'bulk-installer-server'); ?></p>
                        </div>
                    </div>

                    <div class="bis-welcome-step">
                        <div class="bis-step-icon">
                            <span class="dashicons dashicons-admin-tools"></span>
                        </div>
                        <div class="bis-step-content">
                            <h3><?php _e('Faster Site Setup', 'bulk-installer-server'); ?></h3>
                            <p><?php _e('Create website templates with pre-defined sets of plugins and themes for faster site setup.', 'bulk-installer-server'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bis-welcome-actions">
                    <button type="button" class="button button-hero button-primary bis-new-collection">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Create Your First Collection', 'bulk-installer-server'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Template -->
    <script type="text/template" id="bis-item-template">
        <div class="bis-item" data-id="{{id}}">
            <div class="bis-item-header">
                <div class="bis-item-info">
                    <h4 class="bis-item-title">{{name}}</h4>
                    <div class="bis-item-slug">{{slug}}</div>
                </div>
                <div class="bis-item-actions">
                    <label class="bis-required-toggle">
                        <input type="checkbox" class="bis-item-required" {{required}}>
                        <?php _e('Required', 'bulk-installer-server'); ?>
                    </label>
                    <button type="button" class="bis-edit-item button button-small">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="bis-remove-item button button-small">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            </div>
            <div class="bis-item-description">{{description}}</div>
        </div>
    </script>

    <!-- Item Edit Modal -->
    <div id="bis-item-modal" class="bis-modal" style="display:none;">
        <div class="bis-modal-content">
            <div class="bis-modal-header">
                <h3 id="bis-modal-title"><?php _e('Edit Item', 'bulk-installer-server'); ?></h3>
                <button type="button" class="bis-modal-close">&times;</button>
            </div>
            <div class="bis-modal-body">
                <form id="bis-item-form">
                    <input type="hidden" id="bis-item-id" name="item_id" value="">
                    <input type="hidden" id="bis-item-type" name="item_type" value="">
                    <input type="hidden" id="bis-item-source" name="item_source" value="">

                    <div class="bis-form-row">
                        <label for="bis-item-slug"><?php _e('Slug:', 'bulk-installer-server'); ?> <span class="bis-required">*</span></label>
                        <input type="text" id="bis-item-slug" name="item_slug" required>
                        <p class="description"><?php _e('The plugin/theme slug from the repository or a unique identifier for URL items.', 'bulk-installer-server'); ?></p>
                    </div>

                    <div class="bis-form-row">
                        <label for="bis-item-name"><?php _e('Name:', 'bulk-installer-server'); ?> <span class="bis-required">*</span></label>
                        <input type="text" id="bis-item-name" name="item_name" required>
                    </div>

                    <div class="bis-form-row">
                        <label for="bis-item-description"><?php _e('Description:', 'bulk-installer-server'); ?></label>
                        <textarea id="bis-item-description" name="item_description" rows="3"></textarea>
                    </div>

                    <div id="bis-url-field" class="bis-form-row" style="display:none;">
                        <label for="bis-item-url"><?php _e('URL:', 'bulk-installer-server'); ?> <span class="bis-required">*</span></label>
                        <input type="url" id="bis-item-url" name="item_url">
                        <p class="description"><?php _e('Direct download URL for the ZIP file.', 'bulk-installer-server'); ?></p>
                    </div>

                    <div class="bis-form-row">
                        <label class="bis-checkbox-label">
                            <input type="checkbox" id="bis-item-required" name="item_required">
                            <?php _e('Required', 'bulk-installer-server'); ?>
                        </label>
                        <p class="description"><?php _e('Mark as required if this item is essential for the collection.', 'bulk-installer-server'); ?></p>
                    </div>
                </form>
            </div>
            <div class="bis-modal-footer">
                <button type="button" class="button bis-modal-cancel"><?php _e('Cancel', 'bulk-installer-server'); ?></button>
                <button type="button" class="button button-primary bis-save-item"><?php _e('Save Item', 'bulk-installer-server'); ?></button>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="bis-import-modal" class="bis-modal" style="display:none;">
        <div class="bis-modal-content">
            <div class="bis-modal-header">
                <h3><?php _e('Import Collection', 'bulk-installer-server'); ?></h3>
                <button type="button" class="bis-modal-close">&times;</button>
            </div>
            <div class="bis-modal-body">
                <p><?php _e('Paste the JSON collection data below:', 'bulk-installer-server'); ?></p>
                <textarea id="bis-import-data" rows="10" class="large-text code" placeholder="<?php esc_attr_e('Paste JSON collection data here...', 'bulk-installer-server'); ?>"></textarea>
            </div>
            <div class="bis-modal-footer">
                <button type="button" class="button bis-modal-cancel"><?php _e('Cancel', 'bulk-installer-server'); ?></button>
                <button type="button" class="button button-primary bis-import-submit"><?php _e('Import', 'bulk-installer-server'); ?></button>
            </div>
        </div>
    </div>
</div>
