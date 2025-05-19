<?php
/**
 * Plugin Name: Bulk Installer Server
 * Plugin URI: https://wpmultisite.com/plugins/bulk-installer-server/
 * Description: Create and manage plugin/theme collections that can be used with the Bulk Plugin Installer client.
 * Version: 1.0.1
 * Author: WPMultisite.com
 * Author URI: https://wpmultisite.com
 * License: GPL v2 or later
 * Text Domain: bulk-installer-server
 * Requires PHP: 7.4
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

define('BIS_VERSION', '1.0.1');
define('BIS_PATH', plugin_dir_path(__FILE__));
define('BIS_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the plugin
 */
function bis_init() {
    load_plugin_textdomain('bulk-installer-server', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    add_action('admin_menu', 'bis_add_menu_page');
    add_action('admin_enqueue_scripts', 'bis_admin_scripts');
    add_action('rest_api_init', 'bis_register_rest_routes');

    // Ajax handlers
    add_action('wp_ajax_bis_save_collection', 'bis_ajax_save_collection');
    add_action('wp_ajax_bis_delete_collection', 'bis_ajax_delete_collection');
    add_action('wp_ajax_bis_import_collection', 'bis_ajax_import_collection');
    add_action('wp_ajax_bis_get_collection', 'bis_ajax_get_collection');
}
add_action('plugins_loaded', 'bis_init');

/**
 * Register REST API routes
 */
function bis_register_rest_routes() {
    // 获取所有合集
    register_rest_route('bulk-installer-server/v1', '/collections', [
        'methods' => 'GET',
        'callback' => 'bis_rest_get_collections',
        'permission_callback' => '__return_true'
    ]);

    // 按 slug 获取特定合集 - 使用 WordPress 标准的 slug 格式（字母、数字、连字符）
    register_rest_route('bulk-installer-server/v1', '/collection/(?P<slug>[\w-]+)', [
        'methods' => 'GET',
        'callback' => 'bis_rest_get_collection',
        'permission_callback' => '__return_true'
    ]);
}

/**
 * REST API handler for getting all collections
 */
function bis_rest_get_collections() {
    $collections = bis_get_collections();

    return new WP_REST_Response([
        'version' => BIS_VERSION,
        'last_updated' => gmdate('Y-m-d'),
        'collections' => $collections
    ], 200);
}

/**
 * REST API handler for getting a specific collection
 */
function bis_rest_get_collection($request) {
    $slug = $request['slug'];
    $collections = bis_get_collections();

    if (!isset($collections[$slug])) {
        return new WP_REST_Response([
            'error' => 'Collection not found',
            'requested_slug' => $slug,
            'available_slugs' => array_keys($collections)
        ], 404);
    }

    return new WP_REST_Response([
        'version' => BIS_VERSION,
        'last_updated' => gmdate('Y-m-d'),
        'collections' => [
            $slug => $collections[$slug]
        ]
    ], 200);
}

/**
 * Add admin menu page
 */
function bis_add_menu_page() {
    add_menu_page(
        __('Collection Manager', 'bulk-installer-server'),
        __('Collection Manager', 'bulk-installer-server'),
        'manage_options',
        'bulk-installer-server',
        'bis_render_admin_page',
        'dashicons-layout',
        65
    );
}

/**
 * Enqueue admin scripts and styles
 */
function bis_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_bulk-installer-server') {
        return;
    }

    wp_enqueue_style('bis-admin-style', BIS_URL . 'assets/css/admin.css', [], BIS_VERSION);
    wp_enqueue_script('bis-admin', BIS_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], BIS_VERSION, true);

    wp_localize_script('bis-admin', 'bisAjax', [
        'nonce' => wp_create_nonce('bis_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'rest_url' => rest_url('bulk-installer-server/v1/collections'),
        'siteurl' => site_url(),
        'i18n' => [
            'confirm_delete' => __('Are you sure you want to delete this collection?', 'bulk-installer-server'),
            'saving' => __('Saving...', 'bulk-installer-server'),
            'save_error' => __('Error saving collection', 'bulk-installer-server'),
            'add_plugin' => __('Add Plugin', 'bulk-installer-server'),
            'add_theme' => __('Add Theme', 'bulk-installer-server'),
            'invalid_json' => __('Invalid JSON format', 'bulk-installer-server'),
            'add_collection' => __('Create New Collection', 'bulk-installer-server'),
            'edit_collection' => __('Edit Collection', 'bulk-installer-server'),
            'slug_generated' => __('A slug has been automatically generated for this collection', 'bulk-installer-server'),
            'name_required' => __('Collection name is required', 'bulk-installer-server'),
            'item_required' => __('Please add at least one plugin or theme', 'bulk-installer-server'),
            'confirm_regenerate_slug' => __('This will generate a new slug for this collection. API URLs and bookmarks to this collection may break. Continue?', 'bulk-installer-server')
        ]
    ]);

    // Enqueue WordPress media scripts
    wp_enqueue_media();
}

/**
 * Render the admin page
 */
function bis_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'bulk-installer-server'));
    }

    $collections = bis_get_collections();
    include BIS_PATH . 'templates/admin-page.php';
}

/**
 * Get all collections
 *
 * @return array Collections data
 */
function bis_get_collections() {
    $collections = get_option('bis_collections', []);
    return $collections;
}

/**
 * Generate a WordPress-compatible slug
 *
 * @param string $name The collection name
 * @param array $existing_collections Existing collections
 * @return string The generated slug
 */
function bis_generate_slug($name, $existing_collections = []) {
    // 使用 WordPress 原生函数生成 slug
    $slug = sanitize_title($name);

    // 如果 slug 为空（可能由于只包含特殊字符），则生成一个默认 slug
    if (empty($slug)) {
        $slug = 'collection-' . substr(md5($name), 0, 8);
    }

    $original_slug = $slug;
    $counter = 1;

    // 确保 slug 的唯一性
    while (isset($existing_collections[$slug])) {
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

/**
 * Save a collection
 *
 * @param array $collection Collection data
 * @param string $slug Collection slug
 * @param bool $force_new_slug Whether to force generating a new slug
 * @return bool|string True if successful, error message if failed
 */
function bis_save_collection($collection, $slug = '', $force_new_slug = false) {
    if (!current_user_can('manage_options')) {
        return __('Insufficient permissions', 'bulk-installer-server');
    }

    $collections = bis_get_collections();

    // 获取集合名称
    $name = isset($collection['name']) ? trim($collection['name']) : '';

    if (empty($name)) {
        return __('Collection name is required', 'bulk-installer-server');
    }

    // 处理 slug 生成
    if (empty($slug) || $force_new_slug) {
        // 生成新的 slug
        $slug = bis_generate_slug($name, $collections);
    } else if (!isset($collections[$slug])) {
        // 如果提供了 slug 但不存在，检查它是否是有效的
        if (!preg_match('/^[\w-]+$/', $slug)) {
            // 无效的 slug，生成新的
            $slug = bis_generate_slug($name, $collections);
        }
    }

    // Sanitize collection data
    $collection['name'] = sanitize_text_field($name);
    $collection['slug'] = $slug; // 存储生成的 slug
    $collection['description'] = isset($collection['description']) ? sanitize_textarea_field($collection['description']) : '';
    $collection['icon'] = isset($collection['icon']) ? sanitize_text_field($collection['icon']) : 'dashicons-admin-plugins';
    $collection['category'] = isset($collection['category']) ? sanitize_text_field($collection['category']) : 'other';
    $collection['level'] = isset($collection['level']) ? sanitize_text_field($collection['level']) : 'beginner';
    $collection['author'] = isset($collection['author']) ? sanitize_text_field($collection['author']) : get_bloginfo('name');

    if (!empty($collection['screenshot'])) {
        $collection['screenshot'] = esc_url_raw($collection['screenshot']);
    }

    // Sanitize plugins/themes
    $collection['plugins'] = isset($collection['plugins']) ? bis_sanitize_items($collection['plugins']) : ['repository' => [], 'wenpai' => [], 'url' => []];
    $collection['themes'] = isset($collection['themes']) ? bis_sanitize_items($collection['themes']) : ['repository' => [], 'wenpai' => [], 'url' => []];

    // Store in the collections array
    $collections[$slug] = $collection;

    // Save to the database
    $updated = update_option('bis_collections', $collections);

    if (!$updated) {
        return __('Failed to save collection', 'bulk-installer-server');
    }

    return true;
}

/**
 * Sanitize plugin/theme items
 *
 * @param array $items Items to sanitize
 * @return array Sanitized items
 */
function bis_sanitize_items($items) {
    $sanitized = [
        'repository' => [],
        'wenpai' => [],
        'url' => []
    ];

    if (!is_array($items)) {
        return $sanitized;
    }

    foreach (['repository', 'wenpai', 'url'] as $source) {
        if (!isset($items[$source]) || !is_array($items[$source])) {
            continue;
        }

        foreach ($items[$source] as $item) {
            if (is_array($item)) {
                $sanitized_item = [
                    'id' => isset($item['id']) ? absint($item['id']) : 0,
                    'slug' => sanitize_text_field($item['slug'] ?? ''),
                    'name' => sanitize_text_field($item['name'] ?? ''),
                    'description' => sanitize_textarea_field($item['description'] ?? ''),
                    'required' => !empty($item['required'])
                ];

                // Add URL for URL source items
                if ($source === 'url' && !empty($item['url'])) {
                    $sanitized_item['url'] = esc_url_raw($item['url']);
                }

                $sanitized[$source][] = $sanitized_item;
            } else if (is_string($item)) {
                $sanitized[$source][] = sanitize_text_field($item);
            }
        }
    }

    return $sanitized;
}

/**
 * Ajax handler for getting a collection
 */
function bis_ajax_get_collection() {
    check_ajax_referer('bis_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions', 'bulk-installer-server')
        ]);
    }

    $slug = isset($_GET['collection_id']) ? sanitize_text_field(wp_unslash($_GET['collection_id'])) : '';

    if (empty($slug)) {
        wp_send_json_error([
            'message' => __('No collection ID provided', 'bulk-installer-server')
        ]);
    }

    $collections = bis_get_collections();

    if (!isset($collections[$slug])) {
        wp_send_json_error([
            'message' => __('Collection not found', 'bulk-installer-server'),
            'requested_slug' => $slug,
            'available_slugs' => array_keys($collections)
        ]);
    }

    wp_send_json_success([
        'collection' => $collections[$slug]
    ]);
}

/**
 * Ajax handler for saving collections
 */
function bis_ajax_save_collection() {
    check_ajax_referer('bis_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions', 'bulk-installer-server')
        ]);
    }

    $collection_data = isset($_POST['collection']) ? json_decode(stripslashes($_POST['collection']), true) : [];
    $slug = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';
    $force_new_slug = isset($_POST['force_new_slug']) && $_POST['force_new_slug'] === 'true';

    if (empty($collection_data) || !is_array($collection_data)) {
        wp_send_json_error([
            'message' => __('Invalid collection data', 'bulk-installer-server')
        ]);
    }

    $result = bis_save_collection($collection_data, $slug, $force_new_slug);

    if ($result === true) {
        // 如果是新集合，我们需要找到新的 slug
        if (empty($slug) || $force_new_slug) {
            $slug = bis_generate_slug($collection_data['name'], bis_get_collections());

            // 再次检查是否匹配，因为可能在保存过程中其他集合也创建了相同的 slug
            $collections = bis_get_collections();
            foreach ($collections as $collection_slug => $collection) {
                if ($collection['name'] === $collection_data['name'] && $collection_slug !== $slug) {
                    $slug = $collection_slug;
                    break;
                }
            }
        }

        wp_send_json_success([
            'message' => __('Collection saved successfully', 'bulk-installer-server'),
            'collection_id' => $slug,
            'collections' => bis_get_collections()
        ]);
    } else {
        wp_send_json_error([
            'message' => $result
        ]);
    }
}

/**
 * Ajax handler for deleting collections
 */
function bis_ajax_delete_collection() {
    check_ajax_referer('bis_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions', 'bulk-installer-server')
        ]);
    }

    $slug = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';

    if (empty($slug)) {
        wp_send_json_error([
            'message' => __('No collection ID provided', 'bulk-installer-server')
        ]);
    }

    $collections = bis_get_collections();

    if (!isset($collections[$slug])) {
        wp_send_json_error([
            'message' => __('Collection not found', 'bulk-installer-server')
        ]);
    }

    unset($collections[$slug]);

    $updated = update_option('bis_collections', $collections);

    if (!$updated) {
        wp_send_json_error([
            'message' => __('Failed to delete collection', 'bulk-installer-server')
        ]);
    }

    wp_send_json_success([
        'message' => __('Collection deleted successfully', 'bulk-installer-server'),
        'collections' => $collections
    ]);
}

/**
 * Ajax handler for importing collections
 */
function bis_ajax_import_collection() {
    check_ajax_referer('bis_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions', 'bulk-installer-server')
        ]);
    }

    $json_data = isset($_POST['import_data']) ? json_decode(stripslashes($_POST['import_data']), true) : [];

    if (empty($json_data) || !is_array($json_data)) {
        wp_send_json_error([
            'message' => __('Invalid JSON data', 'bulk-installer-server')
        ]);
    }

    $imported = 0;
    $existing_collections = bis_get_collections();

    // Check if we have a "collections" key (full format) or direct collection data
    if (isset($json_data['collections']) && is_array($json_data['collections'])) {
        foreach ($json_data['collections'] as $imported_slug => $collection) {
            if (isset($existing_collections[$imported_slug])) {
                // 已存在同名集合，生成新 slug
                $result = bis_save_collection($collection);
            } else {
                // 使用导入文件中的 slug
                $result = bis_save_collection($collection, $imported_slug);
            }

            if ($result === true) {
                $imported++;
            }
        }
    } else {
        // Assume it's a single collection
        $result = bis_save_collection($json_data);

        if ($result === true) {
            $imported++;
        }
    }

    if ($imported > 0) {
        wp_send_json_success([
            'message' => sprintf(_n('%d collection imported successfully', '%d collections imported successfully', $imported, 'bulk-installer-server'), $imported),
            'collections' => bis_get_collections()
        ]);
    } else {
        wp_send_json_error([
            'message' => __('No collections were imported', 'bulk-installer-server')
        ]);
    }
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'bis_activate');
function bis_activate() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires PHP 7.4 or higher.', 'bulk-installer-server'));
    }

    // Create default collection if none exist
    $collections = bis_get_collections();

    if (empty($collections)) {
        $default_collection = [
            'name' => __('Business Website', 'bulk-installer-server'),
            'description' => __('Essential plugins for a professional business website.', 'bulk-installer-server'),
            'icon' => 'dashicons-building',
            'category' => 'business',
            'level' => 'beginner',
            'author' => get_bloginfo('name'),
            'plugins' => [
                'repository' => [
                    [
                        'id' => 1,
                        'slug' => 'wordpress-seo',
                        'name' => 'Yoast SEO',
                        'description' => __('The leading SEO plugin for WordPress', 'bulk-installer-server'),
                        'required' => true
                    ],
                    [
                        'id' => 2,
                        'slug' => 'contact-form-7',
                        'name' => 'Contact Form 7',
                        'description' => __('Simple but flexible contact form plugin', 'bulk-installer-server'),
                        'required' => true
                    ]
                ],
                'wenpai' => [],
                'url' => []
            ],
            'themes' => [
                'repository' => [
                    [
                        'id' => 3,
                        'slug' => 'astra',
                        'name' => 'Astra',
                        'description' => __('Fast, lightweight theme for business websites', 'bulk-installer-server'),
                        'required' => false
                    ]
                ],
                'wenpai' => [],
                'url' => []
            ]
        ];

        bis_save_collection($default_collection, 'business');
    }

    // Create required directories
    $upload_dir = wp_upload_dir();
    $export_dir = $upload_dir['basedir'] . '/bis-exports';

    if (!file_exists($export_dir)) {
        wp_mkdir_p($export_dir);
    }

    // Create .htaccess file to protect directory
    $htaccess_file = $export_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "<Files \"*.json\">\n";
        $htaccess_content .= "Header set Access-Control-Allow-Origin \"*\"\n";
        $htaccess_content .= "Header set Content-Type \"application/json\"\n";
        $htaccess_content .= "</Files>\n";

        file_put_contents($htaccess_file, $htaccess_content);
    }
}

/**
 * Get collection JSON URL
 *
 * @param string $slug Collection slug
 * @return string Collection JSON URL
 */
function bis_get_collection_json_url($slug) {
    return rest_url("bulk-installer-server/v1/collection/" . urlencode($slug));
}
