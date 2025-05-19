/**
 * Admin JavaScript for Collection Manager
 */
jQuery(document).ready(function($) {
    // Collection data model
    let currentCollection = {
        name: '',
        description: '',
        icon: 'dashicons-admin-plugins',
        category: 'business',
        level: 'beginner',
        author: '',
        screenshot: '',
        plugins: {
            repository: [],
            wenpai: [],
            url: []
        },
        themes: {
            repository: [],
            wenpai: [],
            url: []
        }
    };

    let editingItemId = null;
    let nextItemId = 1;

    // Initialize UI
    initTabs();
    initItemSortable();
    initEvents();
    initIconSelection();
    initTooltips();

    // Tab navigation
    function initTabs() {
        $('.bis-tab').on('click', function() {
            const tab = $(this).data('tab');
            $('.bis-tab').removeClass('active');
            $(this).addClass('active');
            $('.bis-tab-content').removeClass('active').hide();
            $('#bis-tab-' + tab).addClass('active').show();
        });
    }

    // Initialize tooltips
    function initTooltips() {
        $('.bis-tooltip').hover(function() {
            const tooltip = $(this).attr('data-tooltip');
            if (tooltip) {
                const $tooltipElement = $('<div class="bis-tooltip-content"></div>');
                $tooltipElement.text(tooltip);
                $(this).append($tooltipElement);
            }
        }, function() {
            $(this).find('.bis-tooltip-content').remove();
        });
    }

    // Icon selection functionality
    function initIconSelection() {
        const selectedIcon = $('#bis-collection-icon').val();
        $('.bis-selected-icon').attr('class', 'bis-selected-icon dashicons ' + selectedIcon);

        $('#bis-collection-icon').on('change', function() {
            const selectedIcon = $(this).val();
            $('.bis-selected-icon').attr('class', 'bis-selected-icon dashicons ' + selectedIcon);
        });
    }

    // Make items sortable
    function initItemSortable() {
        $('.bis-items-container').sortable({
            placeholder: 'bis-item-placeholder',
            handle: '.bis-item-header',
            update: function() {
                updateCollectionFromUI();
            }
        });
    }

    // Initialize all event handlers
    function initEvents() {
        // Create new collection
        $('.bis-new-collection').on('click', function() {
            resetForm();
            $('#bis-welcome-card').hide();
            $('#bis-editor-title').text(bisAjax.i18n.add_collection || '创建新集合');
            $('#bis-editor-card').show();
        });

        // Edit collection
        $(document).on('click', '.bis-edit-collection', function() {
            const collectionId = $(this).data('id');
            loadCollection(collectionId);
        });

        // Regenerate slug
        $(document).on('click', '.bis-regenerate-slug', function(e) {
            e.preventDefault();
            if (confirm(bisAjax.i18n.confirm_regenerate_slug || '这将为这个集合生成一个新的 slug。API URL 和这个集合的书签可能会失效。继续吗？')) {
                $(this).data('force-new-slug', true);
                saveCollection(true);
            }
        });

        // Delete collection
        $(document).on('click', '.bis-delete-collection', function() {
            const collectionId = $(this).data('id');
            const collectionName = $(this).closest('.bis-collection-item').find('h4').text();
            if (confirm(bisAjax.i18n.confirm_delete || `确定要删除集合 "${collectionName}" 吗？`)) {
                deleteCollection(collectionId);
            }
        });

        // Cancel edit
        $('.bis-cancel-edit').on('click', function() {
            $('#bis-editor-card').hide();
            $('#bis-welcome-card').show();
        });

        // Save collection
        $('#bis-collection-form').on('submit', function(e) {
            e.preventDefault();
            saveCollection();
            return false;
        });

        // Add item button
        $('.bis-add-item').on('click', function() {
            const itemType = $(this).data('type');
            const itemSource = $(this).data('source');

            // Show modal with empty form
            editingItemId = null;
            $('#bis-item-type').val(itemType);
            $('#bis-item-source').val(itemSource);
            $('#bis-item-id').val('');
            $('#bis-item-slug').val('');
            $('#bis-item-name').val('');
            $('#bis-item-description').val('');
            $('#bis-item-required').prop('checked', false);

            // Show URL field if needed
            $('#bis-url-field').toggle(itemSource === 'url');
            if (itemSource === 'url') {
                $('#bis-item-url').val('');
            }

            $('#bis-modal-title').text(
                itemType === 'plugin' ?
                bisAjax.i18n.add_plugin || '添加插件' :
                bisAjax.i18n.add_theme || '添加主题'
            );

            showModal('#bis-item-modal');
        });

        // Modal close
        $('.bis-modal-close, .bis-modal-cancel').on('click', function() {
            closeModals();
        });

        // Click outside modal to close
        $(document).on('click', '.bis-modal', function(e) {
            if ($(e.target).hasClass('bis-modal')) {
                closeModals();
            }
        });

        // Save item
        $('.bis-save-item').on('click', function() {
            saveItem();
        });

        // Edit item (delegated)
        $(document).on('click', '.bis-edit-item', function() {
            const $item = $(this).closest('.bis-item');
            const itemId = parseInt($item.data('id'));
            const itemType = $item.closest('.bis-tab-content').attr('id') === 'bis-tab-plugins' ? 'plugin' : 'theme';
            const itemSource = $item.closest('.bis-source-section').data('source');

            // Find the item in the collection data
            const items = itemType === 'plugin' ? currentCollection.plugins[itemSource] : currentCollection.themes[itemSource];
            const item = items.find(i => i.id === itemId);

            if (item) {
                editingItemId = itemId;
                $('#bis-item-type').val(itemType);
                $('#bis-item-source').val(itemSource);
                $('#bis-item-id').val(itemId);
                $('#bis-item-slug').val(item.slug);
                $('#bis-item-name').val(item.name);
                $('#bis-item-description').val(item.description || '');
                $('#bis-item-required').prop('checked', item.required || false);

                // Show URL field if needed
                $('#bis-url-field').toggle(itemSource === 'url');
                if (itemSource === 'url') {
                    $('#bis-item-url').val(item.url || '');
                }

                $('#bis-modal-title').text('编辑' + (itemType === 'plugin' ? '插件' : '主题'));
                showModal('#bis-item-modal');
            }
        });

        // Remove item (delegated)
        $(document).on('click', '.bis-remove-item', function() {
            const $item = $(this).closest('.bis-item');
            $item.fadeOut(300, function() {
                $item.remove();
                updateCollectionFromUI();
            });
        });

        // Toggle item required
        $(document).on('change', '.bis-item-required', function() {
            updateCollectionFromUI();
        });

        // Upload screenshot
        $('.bis-upload-screenshot').on('click', function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Create the media frame
            mediaUploader = wp.media({
                title: '选择或上传截图',
                button: {
                    text: '使用此图片'
                },
                multiple: false
            });

            // When an image is selected, run a callback
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#bis-collection-screenshot').val(attachment.url);
                updateScreenshotPreview(attachment.url);
            });

            // Open the media uploader
            mediaUploader.open();
        });

        // Update screenshot preview when URL changes
        $('#bis-collection-screenshot').on('change', function() {
            updateScreenshotPreview($(this).val());
        });

        // Export format change
        $('#bis-export-format').on('change', function() {
            $('#bis-export-collection-select').toggle($(this).val() === 'selected');
        });

        // Export collections
        $('.bis-export-collections').on('click', function() {
            exportCollections();
        });

        // Copy URL buttons
        $('.bis-copy-url').on('click', function() {
            const url = $(this).data('url');
            copyToClipboard(url);

            // Show success message
            const $button = $(this);
            $button.html('<span class="dashicons dashicons-yes"></span>');
            setTimeout(function() {
                $button.html('<span class="dashicons dashicons-clipboard"></span>');
            }, 2000);
        });

        // Import collection
        $('.bis-import-collection').on('click', function() {
            showModal('#bis-import-modal');
        });

        // Import submit
        $('.bis-import-submit').on('click', function() {
            importCollections();
        });

        // Form key event handling
        $('#bis-item-form').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('.bis-save-item').click();
            }
        });

        // Keyboard shortcut (Escape to close modals)
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModals();
            }
        });

        // Prevent modals from closing when clicking inside the modal content
        $('.bis-modal-content').on('click', function(e) {
            e.stopPropagation();
        });

        // Check if URL parameters contain action=edit and id=xyz
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        const id = urlParams.get('id');

        if (action === 'edit' && id) {
            // Auto-load collection for editing
            loadCollection(id);
        }
    }

    // Media uploader instance
    let mediaUploader = null;

    // Show a modal
    function showModal(selector) {
        $(selector).fadeIn(300);
        $('body').addClass('bis-modal-open');
    }

    // Close all modals
    function closeModals() {
        $('.bis-modal').fadeOut(200);
        $('body').removeClass('bis-modal-open');
    }

    // Update screenshot preview
    function updateScreenshotPreview(url) {
        const $preview = $('#bis-screenshot-preview');
        if (url) {
            $preview.html(`<img src="${url}" alt="Screenshot preview" />`);
        } else {
            $preview.empty();
        }
    }

    // Reset form to create a new collection
    function resetForm() {
        $('#bis-collection-id').val('');
        $('#bis-collection-name').val('');
        $('#bis-collection-description').val('');
        $('#bis-collection-icon').val('dashicons-admin-plugins');
        $('#bis-collection-category').val('business');
        $('#bis-collection-level').val('beginner');
        $('#bis-collection-author').val(document.title.split(' ‹ ')[1] || '');
        $('#bis-collection-screenshot').val('');
        $('#bis-screenshot-preview').empty();

        // 隐藏 slug 信息 (只在编辑现有集合时显示)
        $('.bis-slug-display').hide();
        $('.bis-slug-info').text('');
        $('.bis-regenerate-slug').data('force-new-slug', false);

        // Clear all items
        $('.bis-items-container').empty();

        // Reset collection data
        currentCollection = {
            name: '',
            description: '',
            icon: 'dashicons-admin-plugins',
            category: 'business',
            level: 'beginner',
            author: document.title.split(' ‹ ')[1] || '',
            screenshot: '',
            plugins: {
                repository: [],
                wenpai: [],
                url: []
            },
            themes: {
                repository: [],
                wenpai: [],
                url: []
            }
        };

        // Reset item ID counter
        nextItemId = 1;

        // Update icon preview
        initIconSelection();
    }

    // Load collection data into form
    function loadCollection(collectionId) {
        // Add loading state to the editor card
        const $editorCard = $('#bis-editor-card');
        const $loadingOverlay = $('<div class="bis-loading-overlay"><span class="spinner is-active"></span><p>加载中...</p></div>');
        $editorCard.append($loadingOverlay);

        $.ajax({
            url: bisAjax.ajaxurl,
            type: 'GET',
            data: {
                action: 'bis_get_collection',
                nonce: bisAjax.nonce,
                collection_id: collectionId
            },
            success: function(response) {
                $loadingOverlay.remove();

                if (response.success) {
                    const collection = response.data.collection;
                    currentCollection = collection;

                    // Update form fields
                    $('#bis-collection-id').val(collectionId);
                    $('#bis-collection-name').val(collection.name);
                    $('#bis-collection-description').val(collection.description);
                    $('#bis-collection-icon').val(collection.icon);
                    $('#bis-collection-category').val(collection.category);
                    $('#bis-collection-level').val(collection.level);
                    $('#bis-collection-author').val(collection.author);
                    $('#bis-collection-screenshot').val(collection.screenshot);
                    updateScreenshotPreview(collection.screenshot);

                    // 显示 slug 信息
                    $('.bis-slug-display').show();
                    $('.bis-slug-info').text(collectionId);

                    // 重置强制生成新 slug 标记
                    $('.bis-regenerate-slug').data('force-new-slug', false);

                    // Update icon preview
                    initIconSelection();

                    // Clear all items first
                    $('.bis-items-container').empty();

                    // Add plugins and themes to UI
                    renderItems('plugin', 'repository', collection.plugins.repository || []);
                    renderItems('plugin', 'wenpai', collection.plugins.wenpai || []);
                    renderItems('plugin', 'url', collection.plugins.url || []);
                    renderItems('theme', 'repository', collection.themes.repository || []);
                    renderItems('theme', 'wenpai', collection.themes.wenpai || []);
                    renderItems('theme', 'url', collection.themes.url || []);

                    // Find the highest item ID to continue from
                    nextItemId = 1;
                    const updateNextId = (items) => {
                        if (!items) return;
                        items.forEach(item => {
                            if (item.id && item.id >= nextItemId) {
                                nextItemId = item.id + 1;
                            }
                        });
                    };

                    updateNextId(collection.plugins.repository);
                    updateNextId(collection.plugins.wenpai);
                    updateNextId(collection.plugins.url);
                    updateNextId(collection.themes.repository);
                    updateNextId(collection.themes.wenpai);
                    updateNextId(collection.themes.url);

                    // Show editor
                    $('#bis-welcome-card').hide();
                    $('#bis-editor-title').text((bisAjax.i18n.edit_collection || '编辑集合') + ': ' + collection.name);
                    $('#bis-editor-card').show();
                } else {
                    console.error('加载集合失败:', response.data);
                    showNotification(response.data.message || '加载集合时出错', 'error');
                }
            },
            error: function(xhr, status, error) {
                $loadingOverlay.remove();
                console.error('AJAX错误:', xhr.responseText);
                showNotification('加载集合时出错: ' + error, 'error');
            }
        });
    }

    // Render items to the UI
    function renderItems(type, source, items) {
        if (!items || !Array.isArray(items)) return;

        const $container = $(`#bis-${type}s-${source}`);

        items.forEach(item => {
            // Ensure item has an ID
            if (!item.id) {
                item.id = nextItemId++;
            }

            const itemHtml = generateItemHtml(item);
            $container.append(itemHtml);
        });
    }

    // Generate HTML for an item
    function generateItemHtml(item) {
        let template = $('#bis-item-template').html();

        template = template.replace(/{{id}}/g, item.id);
        template = template.replace(/{{name}}/g, escapeHtml(item.name));
        template = template.replace(/{{slug}}/g, escapeHtml(item.slug));
        template = template.replace(/{{description}}/g, escapeHtml(item.description || ''));
        template = template.replace(/{{required}}/g, item.required ? 'checked' : '');

        return template;
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Highlight newly added/edited elements
    function highlightElement($element) {
        $element.addClass('bis-highlight');
        setTimeout(function() {
            $element.removeClass('bis-highlight');
        }, 1500);
    }

    // Save an item from the modal
    function saveItem() {
        const itemType = $('#bis-item-type').val();
        const itemSource = $('#bis-item-source').val();
        const itemId = editingItemId || nextItemId++;
        const itemSlug = $('#bis-item-slug').val();
        const itemName = $('#bis-item-name').val();
        const itemDescription = $('#bis-item-description').val();
        const itemRequired = $('#bis-item-required').is(':checked');

        if (!itemSlug || !itemName) {
            if (!itemSlug) {
                $('#bis-item-slug').focus();
                showNotification('Slug 是必填项', 'warning');
            } else {
                $('#bis-item-name').focus();
                showNotification('名称是必填项', 'warning');
            }
            return;
        }

        // Create item object
        const item = {
            id: itemId,
            slug: itemSlug,
            name: itemName,
            description: itemDescription,
            required: itemRequired
        };

        // Add URL if it's a URL source
        if (itemSource === 'url') {
            item.url = $('#bis-item-url').val();

            if (!item.url) {
                $('#bis-item-url').focus();
                showNotification('URL 是必填项', 'warning');
                return;
            }
        }

        // Add to collection data
        const items = itemType === 'plugin' ? currentCollection.plugins[itemSource] : currentCollection.themes[itemSource];

        if (editingItemId) {
            // Update existing item
            const index = items.findIndex(i => i.id === editingItemId);
            if (index !== -1) {
                items[index] = item;
            }
        } else {
            // Add new item
            items.push(item);
        }

        // Update UI
        const $container = $(`#bis-${itemType}s-${itemSource}`);

        if (editingItemId) {
            // Update existing item in UI
            const $item = $container.find(`.bis-item[data-id="${editingItemId}"]`);
            const $newItem = $(generateItemHtml(item));
            $item.replaceWith($newItem);
            highlightElement($newItem);
        } else {
            // Add new item to UI
            const $newItem = $(generateItemHtml(item));
            $container.append($newItem);
            highlightElement($newItem);
        }

        // Close modal
        closeModals();

        // Show success notification
        showNotification(editingItemId ? '项目已更新' : '项目已添加', 'success');
    }

    // Update collection data from UI
    function updateCollectionFromUI() {
        // Basic info
        currentCollection.name = $('#bis-collection-name').val();
        currentCollection.description = $('#bis-collection-description').val();
        currentCollection.icon = $('#bis-collection-icon').val();
        currentCollection.category = $('#bis-collection-category').val();
        currentCollection.level = $('#bis-collection-level').val();
        currentCollection.author = $('#bis-collection-author').val();
        currentCollection.screenshot = $('#bis-collection-screenshot').val();

        // Get the order and required status of plugins and themes
        updateItemsFromUI('plugin', 'repository');
        updateItemsFromUI('plugin', 'wenpai');
        updateItemsFromUI('plugin', 'url');
        updateItemsFromUI('theme', 'repository');
        updateItemsFromUI('theme', 'wenpai');
        updateItemsFromUI('theme', 'url');
    }

    // Update items from UI
    function updateItemsFromUI(type, source) {
        const $container = $(`#bis-${type}s-${source}`);
        const items = type === 'plugin' ? currentCollection.plugins[source] : currentCollection.themes[source];

        // Get the current order of items
        const itemIds = $container.find('.bis-item').map(function() {
            return parseInt($(this).data('id'));
        }).get();

        // Get required status for each item
        $container.find('.bis-item').each(function() {
            const id = parseInt($(this).data('id'));
            const required = $(this).find('.bis-item-required').is(':checked');

            // Update required status in collection data
            const item = items.find(i => i.id === id);
            if (item) {
                item.required = required;
            }
        });

        // Reorder items based on UI
        const reorderedItems = [];
        itemIds.forEach(id => {
            const item = items.find(i => i.id === id);
            if (item) {
                reorderedItems.push(item);
            }
        });

        // Update collection with reordered items
        if (type === 'plugin') {
            currentCollection.plugins[source] = reorderedItems;
        } else {
            currentCollection.themes[source] = reorderedItems;
        }
    }

    // Add debug logging function
    function logDebug(label, data) {
        console.group('调试: ' + label);
        console.log(data);
        console.groupEnd();
    }

    // Save collection
    function saveCollection(forceNewSlug = false) {
        updateCollectionFromUI();

        // Validate required fields
        if (!currentCollection.name) {
            $('#bis-collection-name').focus();
            showNotification(bisAjax.i18n.name_required || '集合名称是必填项', 'warning');
            return;
        }

        // Check if we have at least one plugin or theme
        let hasItems = false;
        let totalItemCount = 0;

        for (const source in currentCollection.plugins) {
            totalItemCount += currentCollection.plugins[source].length;
            if (currentCollection.plugins[source].length > 0) {
                hasItems = true;
            }
        }

        if (!hasItems) {
            for (const source in currentCollection.themes) {
                totalItemCount += currentCollection.themes[source].length;
                if (currentCollection.themes[source].length > 0) {
                    hasItems = true;
                }
            }
        }

        if (!hasItems) {
            showNotification(bisAjax.i18n.item_required || '请至少添加一个插件或主题到集合中', 'warning');
            // Switch to plugins tab
            $('.bis-tab[data-tab="plugins"]').click();
            return;
        }

        // Prepare data for saving
        const slug = $('#bis-collection-id').val();
        const $saveButton = $('.bis-save-collection');

        // Add loading state to button
        const originalButtonText = $saveButton.html();
        $saveButton.prop('disabled', true)
                  .html('<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>' +
                        (bisAjax.i18n.saving || '保存中...'));

        // 判断是否需要强制生成新的 slug
        if (forceNewSlug || $('.bis-regenerate-slug').data('force-new-slug')) {
            forceNewSlug = true;
        }

        // 添加日志记录
        logDebug('发送数据', {
            collection: currentCollection,
            collection_id: slug,
            force_new_slug: forceNewSlug
        });

        $.ajax({
            url: bisAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bis_save_collection',
                nonce: bisAjax.nonce,
                collection: JSON.stringify(currentCollection),
                collection_id: slug,
                force_new_slug: forceNewSlug
            },
            success: function(response) {
                logDebug('保存响应', response);

                if (response.success) {
                    const newSlug = response.data.collection_id;

                    // 如果 slug 发生了变化，更新表单
                    if (slug !== newSlug) {
                        $('#bis-collection-id').val(newSlug);
                        $('.bis-slug-info').text(newSlug);

                        // 显示 slug 信息区域
                        $('.bis-slug-display').show();

                        if (forceNewSlug) {
                            showNotification('已成功重新生成 Slug', 'success');
                        } else {
                            showNotification(bisAjax.i18n.slug_generated || '已为此集合自动生成 slug', 'info', 5000);
                        }

                        // 重置强制生成新 slug 标记
                        $('.bis-regenerate-slug').data('force-new-slug', false);
                    } else {
                        // 显示成功消息
                        showNotification('集合保存成功！', 'success');
                    }

                    // 重新加载页面以显示更新后的集合
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data.message || bisAjax.i18n.save_error || '保存集合时出错', 'error');
                    $saveButton.prop('disabled', false).html(originalButtonText);
                }
            },
            error: function(xhr, status, error) {
                console.error('保存错误:', xhr.responseText);
                showNotification(bisAjax.i18n.save_error || '保存集合时出错', 'error');
                $saveButton.prop('disabled', false).html(originalButtonText);
            }
        });
    }

    // Show notification messages
    function showNotification(message, type = 'info', duration = 3000) {
        // Remove any existing notifications
        $('.bis-notification').remove();

        // Set notification class based on type
        let notificationClass = 'bis-notification-info';
        let iconClass = 'dashicons-info';

        if (type === 'success') {
            notificationClass = 'bis-notification-success';
            iconClass = 'dashicons-yes';
        } else if (type === 'error') {
            notificationClass = 'bis-notification-error';
            iconClass = 'dashicons-no';
        } else if (type === 'warning') {
            notificationClass = 'bis-notification-warning';
            iconClass = 'dashicons-warning';
        }

        // Create notification element
        const $notification = $(
            `<div class="bis-notification ${notificationClass}">
                <span class="dashicons ${iconClass}"></span>
                <span class="bis-notification-message">${message}</span>
                <button class="bis-notification-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>`
        );

        // Add to page
        $('body').append($notification);

        // Show with animation
        setTimeout(function() {
            $notification.addClass('bis-notification-show');
        }, 10);

        // Close button functionality
        $notification.find('.bis-notification-close').on('click', function() {
            $notification.removeClass('bis-notification-show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        });

        // Auto close after duration
        if (duration > 0) {
            setTimeout(function() {
                $notification.removeClass('bis-notification-show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, duration);
        }
    }

    // Delete collection
    function deleteCollection(slug) {
        // Add loading overlay
        const $loadingOverlay = $('<div class="bis-loading-overlay"><span class="spinner is-active"></span><p>删除中...</p></div>');
        $('body').append($loadingOverlay);

        $.ajax({
            url: bisAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bis_delete_collection',
                nonce: bisAjax.nonce,
                collection_id: slug
            },
            success: function(response) {
                $loadingOverlay.remove();

                if (response.success) {
                    // Show success message
                    showNotification('集合已成功删除', 'success');
                    // Reload page to reflect changes
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || '删除集合时出错', 'error');
                }
            },
            error: function(xhr, status, error) {
                $loadingOverlay.remove();
                console.error('删除错误:', xhr.responseText);
                showNotification('删除集合时出错: ' + error, 'error');
            }
        });
    }

    // Export collections
    function exportCollections() {
        const format = $('#bis-export-format').val();

        if (format === 'all') {
            // Export all collections
            $.ajax({
                url: bisAjax.rest_url,
                type: 'GET',
                success: function(response) {
                    downloadJson(response, 'collections.json');
                    showNotification('所有集合导出成功', 'success');
                },
                error: function(xhr, status, error) {
                    console.error('导出错误:', xhr.responseText);
                    showNotification('导出集合时出错: ' + error, 'error');
                }
            });
        } else {
            // Export selected collection
            const slug = $('#bis-export-collection').val();

            if (!slug) {
                showNotification('请选择要导出的集合', 'warning');
                return;
            }

            // 使用 REST API 路径获取集合
            // 注意：我们使用正确的 URL 路径，"collection" 而不是 "collections"
            const collectionUrl = bisAjax.rest_url.replace('/collections', '/collection/' + slug);

            $.ajax({
                url: collectionUrl,
                type: 'GET',
                success: function(response) {
                    // For single collection, we can optionally strip the wrapper and just export the collection
                    const collection = response.collections[slug];
                    downloadJson(collection, slug + '.json');
                    showNotification('集合导出成功', 'success');
                },
                error: function(xhr, status, error) {
                    console.error('导出失败:', xhr.responseJSON);
                    showNotification('导出集合时出错: ' + error, 'error');
                }
            });
        }
    }

    // Import collections
    function importCollections() {
        const importData = $('#bis-import-data').val();

        if (!importData) {
            showNotification('请粘贴集合 JSON 数据', 'warning');
            return;
        }

        try {
            // Validate JSON
            JSON.parse(importData);

            // Add loading state
            const $importButton = $('.bis-import-submit');
            const originalButtonText = $importButton.text();
            $importButton.prop('disabled', true)
                         .html('<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>导入中...');

            // Submit import
            $.ajax({
                url: bisAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bis_import_collection',
                    nonce: bisAjax.nonce,
                    import_data: importData
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        closeModals();
                        // Show success message
                        showNotification(response.data.message || '集合导入成功', 'success');
                        // Reload page to reflect changes
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        $importButton.prop('disabled', false).text(originalButtonText);
                        showNotification(response.data.message || '导入集合时出错', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $importButton.prop('disabled', false).text(originalButtonText);
                    console.error('导入错误:', xhr.responseText);
                    showNotification('导入集合时出错: ' + error, 'error');
                }
            });
        } catch (e) {
            showNotification(bisAjax.i18n.invalid_json || 'JSON 格式无效', 'error');
        }
    }

    // Utility functions
    function downloadJson(data, filename) {
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(data, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", filename);
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }

    function copyToClipboard(text) {
        // 使用现代 Clipboard API (如果可用)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('已复制到剪贴板', 'success', 2000);
            }).catch(() => {
                // 如果 Clipboard API 失败，回退到传统方法
                fallbackCopyToClipboard(text);
            });
        } else {
            // 对于不支持 Clipboard API 的浏览器使用传统方法
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';  // 防止滚动到视图底部
        document.body.appendChild(textarea);
        textarea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showNotification('已复制到剪贴板', 'success', 2000);
            } else {
                showNotification('复制失败，请手动复制', 'warning');
            }
        } catch (err) {
            showNotification('复制失败: ' + err, 'error');
        }

        document.body.removeChild(textarea);
    }
});
