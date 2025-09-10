<?php
// Activation / Deactivation Hooks
function aiccgen_google_activate() {
    add_filter('cron_schedules', 'aiccgen_google_add_cron_schedules');
    aiccgen_google_reschedule_all_tasks();
}
register_activation_hook(__FILE__, 'aiccgen_google_activate');

function aiccgen_google_deactivate() {
    $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK);
    while($timestamp) {
        $scheduled_event = get_scheduled_event(AICCG_GOOGLE_CRON_HOOK, [], $timestamp);
        if ($scheduled_event) {
            wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK, $scheduled_event->args);
        } else {
            // Fallback if getting args fails (shouldn't happen often)
            wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK);
        }
        $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK); // Find the next one
    }
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $prompts = isset($options['prompts']) ? $options['prompts'] : [];
    if (is_array($prompts)) {
        foreach (array_keys($prompts) as $cat_id) {
            wp_clear_scheduled_hook(AICCG_GOOGLE_CRON_HOOK, ['category_id' => absint($cat_id)]);
        }
    }
    remove_filter('cron_schedules', 'aiccgen_google_add_cron_schedules'); // Remove custom schedule definitions
}
register_deactivation_hook(__FILE__, 'aiccgen_google_deactivate');
// Activation / Deactivation Hooks


// Cron Schedules
function aiccgen_google_add_cron_schedules($schedules) {
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = [
            'interval' => MONTH_IN_SECONDS,
            'display' => __('Once Monthly', 'ai-cat-content-gen-google')
        ];
    }
    if (!isset($schedules['weekly'])) {
         $schedules['weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'ai-cat-content-gen-google')
        ];
    }
    return $schedules;
}
add_filter('cron_schedules', 'aiccgen_google_add_cron_schedules');

// Register AI Suggestion Custom Post Type (AI Machine)
if ( ! function_exists( 'aiccgen_google_register_ai_suggestion_cpt' ) ) {
    function aiccgen_google_register_ai_suggestion_cpt() {
        $labels = array(
            'name'                  => _x( 'AI Machine', 'Post type general name', 'ai-cat-content-gen-google' ),
            'singular_name'         => _x( 'AI Machine', 'Post type singular name', 'ai-cat-content-gen-google' ),
            'menu_name'             => _x( 'AI Machine', 'Admin Menu text', 'ai-cat-content-gen-google' ),
            'name_admin_bar'        => _x( 'AI Machine', 'Add New on Toolbar', 'ai-cat-content-gen-google' ),
            'add_new'               => __( 'Add New', 'ai-cat-content-gen-google' ),
            'add_new_item'          => __( 'Add New', 'ai-cat-content-gen-google' ),
            'new_item'              => __( 'New AI Machine', 'ai-cat-content-gen-google' ),
            'edit_item'             => __( 'Edit AI Machine', 'ai-cat-content-gen-google' ),
            'view_item'             => __( 'View AI Machine', 'ai-cat-content-gen-google' ),
            'all_items'             => __( 'Needs Your Review', 'ai-cat-content-gen-google' ),
            'search_items'          => __( 'Search AI Machine', 'ai-cat-content-gen-google' ),
            'parent_item_colon'     => __( 'Parent AI Machine:', 'ai-cat-content-gen-google' ),
            'not_found'             => __( 'No AI Machine found.', 'ai-cat-content-gen-google' ),
            'not_found_in_trash'    => __( 'No AI Machine found in Trash.', 'ai-cat-content-gen-google' ),
            'featured_image'        => _x( 'Featured Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'ai-cat-content-gen-google' ),
            'set_featured_image'    => _x( 'Set featured image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'ai-cat-content-gen-google' ),
            'remove_featured_image' => _x( 'Remove featured image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'ai-cat-content-gen-google' ),
            'use_featured_image'    => _x( 'Use as featured image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'ai-cat-content-gen-google' ),
            'archives'              => _x( 'AI Machine archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'ai-cat-content-gen-google' ),
            'insert_into_item'      => _x( 'Insert into AI machine', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'ai-cat-content-gen-google' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this AI machine', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'ai-cat-content-gen-google' ),
            'filter_items_list'     => _x( 'Filter AI machines list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'ai-cat-content-gen-google' ),
            'items_list_navigation' => _x( 'AI Machines list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'ai-cat-content-gen-google' ),
            'items_list'            => _x( 'AI Machines list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'ai-cat-content-gen-google' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'ai_suggestion' ),
            'capability_type'    => 'post', // Use 'post' capabilities for simplicity, or define custom ones
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 3, // Below Posts, above Media
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', 'revisions' ),
            'taxonomies'         => array( 'category' ), // WP categories with posts
        );
        register_post_type( 'ai_suggestion', $args );
    }
}
add_action( 'init', 'aiccgen_google_register_ai_suggestion_cpt' );

// Enqueue Scripts and localize scripts and Style
function aiccgen_google_enqueue_admin_scripts($hook_suffix)
{
    if (strpos($hook_suffix, AICCG_GOOGLE_SETTINGS_SLUG) !== false) {
       // return;
        wp_enqueue_script(
            'aiccgen-google-admin-js',
            plugin_dir_url(__FILE__) . 'js/aiccgen-google-admin.js',
            ['jquery'],
            '2.0.0',
            true
        );
    
        wp_enqueue_style(
            'aiccgen-google-admin-css',
            plugin_dir_url(__FILE__) . 'css/aiccgen-google-admin.css',
            [],
            '2.0.0'
        );
    
        // WP localize Scripts for main plugin settings page
        wp_localize_script('aiccgen-google-admin-js', 'aiccgen_google_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(AICCG_GOOGLE_NONCE_ACTION),
            'plugin_base_url'        => plugin_dir_url(__FILE__),
    
            'ajax_generate_action' => AICCG_GOOGLE_AJAX_ACTION,
            'ajax_refine_action' => AICCG_GOOGLE_AJAX_REFINE_ACTION,
            'ajax_create_post_action' => AICCG_GOOGLE_AJAX_CREATE_POST_ACTION,
    
            'error_no_category' => __('Please select a category first.', 'ai-cat-content-gen-google'),
            'error_ajax' => __('An error occurred during the request. Please try again. Check browser console for details.', 'ai-cat-content-gen-google'),
            'error_title' => __('Error', 'ai-cat-content-gen-google'),
            'success_title' => __('Generated Content Suggestion', 'ai-cat-content-gen-google'),
            'for_category' => __('Result for category: %s', 'ai-cat-content-gen-google'),
            'copy_notice' => __('Please review, edit, and copy the content below to create a new post manually.', 'ai-cat-content-gen-google'),
            'label_formatting_instructions' => __('Formatting & Content Rules:', 'ai-cat-content-gen-google'),
            'placeholder_formatting_instructions' => __('e.g., Use H2 for headings, wrap paragraphs in <p>, avoid words like "example", "another example", ensure professional tone.', 'ai-cat-content-gen-google'),
            // Manual Process refine Area
            'refine_title' => __('Refine Content', 'ai-cat-content-gen-google'),
            'refine_instructions' => __('Enter instructions below to modify the text above (e.g., "make it shorter", "focus more on local business news", "rewrite the first paragraph").', 'ai-cat-content-gen-google'),
            'refine_placeholder' => __('Manual refinement instructions...', 'ai-cat-content-gen-google'),
            'refine_button_text' => __('Refine Now', 'ai-cat-content-gen-google'),
            'refine_success' => __('Content refined successfully.', 'ai-cat-content-gen-google'),
            'error_refine_no_original' => __('Could not find original content to refine.', 'ai-cat-content-gen-google'),
            'error_refine_no_prompt' => __('Please enter refinement instructions.', 'ai-cat-content-gen-google'),
            'error_ajax_refine' => __('An error occurred during the refinement request. Please try again.', 'ai-cat-content-gen-google'),
            'create_post_title' => __('Create Post', 'ai-cat-content-gen-google'),
            'create_post_label_title' => __('Post Title:', 'ai-cat-content-gen-google'),
            'create_post_placeholder_title' => __('Enter a title for the new post...', 'ai-cat-content-gen-google'),
            'create_post_button_text' => __('Create Post (Draft)', 'ai-cat-content-gen-google'),
            'error_create_post_no_category' => __('Cannot create post: Category information is missing.', 'ai-cat-content-gen-google'),
            'error_create_post_no_title' => __('Please enter a post title.', 'ai-cat-content-gen-google'),
            'error_create_post_no_content' => __('Cannot create post: Content is missing.', 'ai-cat-content-gen-google'),
            'error_ajax_create_post' => __('An error occurred while trying to create the post. Please try again.', 'ai-cat-content-gen-google'),
            'saving_generating_notice' => __('Settings saved. Updating schedules...', 'ai-cat-content-gen-google'),
            'image_gen_success' => __('Image generated successfully (Landscape 3:2).', 'ai-cat-content-gen-google'),
            'image_gen_failed' => __('Image generation failed:', 'ai-cat-content-gen-google'),
            'image_gen_skipped_no_prompt' => __('Image generation skipped (no prompt provided).', 'ai-cat-content-gen-google'),
            'image_gen_skipped_no_key' => __('Image generation skipped (Venice API key missing in settings).', 'ai-cat-content-gen-google'),
            'generating_image' => __('Please wait for 2-3 minutes. Do not close this page.', 'ai-cat-content-gen-google'),
            'generated_image_preview' => __('Generated Image Preview:', 'ai-cat-content-gen-google'),
            'create_post_button_text_with_image' => __('Create Draft (with Image)', 'ai-cat-content-gen-google'),
            'create_post_button_text_no_image' => __('Create Draft (Content Only)', 'ai-cat-content-gen-google'),
        ]);
    }
    // WP localize Scripts for CPT AI Suggestion
    
    if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
        global $post_type;
        if ($post_type === 'ai_suggestion' || $post_type === 'post') {
            wp_enqueue_style(
                'aiccgen-google-post-edit-css',
                plugin_dir_url(__FILE__) . 'css/aiccgen-google-post-edit.css',
                [],
                '1.0.0'
            );
        }
        if ($post_type === 'ai_suggestion') {
            wp_enqueue_script(
                'aiccgen-google-post-edit-js',
                plugin_dir_url(__FILE__) . 'js/aiccgen-google-post-featured-img-refine.js',
                ['jquery', 'wp-util'],
                '1.0.0',
                true
            );
            wp_enqueue_style(
                'aiccgen-google-post-edit-css',
                plugin_dir_url(__FILE__) . 'css/aiccgen-google-post-edit.css',
                [],
                '1.0.0'
            );
            wp_enqueue_script(
                'aiccgen-google-post-editor-refine-js',
                plugin_dir_url(__FILE__) . 'js/aiccgen-google-post-editor-refine.js',
                ['jquery', 'wp-i18n'],
                '1.0.0',
                true
            );

            // Get Venice API key status
            $options = get_option(AICCG_GOOGLE_OPTION_NAME);
            $venice_api_key_exists = !empty($options['venice_api_key']);
            $google_api_key_exists = !empty($options['api_key']);
            
            wp_localize_script('aiccgen-google-post-edit-js', 'aiccgen_post_edit_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(AICCG_GOOGLE_NONCE_ACTION),
                'plugin_base_url' => plugin_dir_url(__FILE__),
                'venice_api_key_exists' => $venice_api_key_exists,
                'ajax_post_refine_image_action' => AICCG_GOOGLE_AJAX_POST_REFINE_IMAGE_ACTION,
                'ajax_post_apply_image_action' => AICCG_GOOGLE_AJAX_POST_APPLY_IMAGE_ACTION,
                'text_refine_button' => __('Refine Featured Image with AI', 'ai-cat-content-gen-google'),
                'text_generating_options' => __('Please wait 2-3 minutes. Do not close the page.', 'ai-cat-content-gen-google'),
                'text_apply_selected' => __('Apply Selected', 'ai-cat-content-gen-google'),
                'text_select_an_image' => __('Please select an image option.', 'ai-cat-content-gen-google'),
                'text_confirm_apply' => __('This will set the selected image as the featured image for this post and delete the other generated options. Are you sure?', 'ai-cat-content-gen-google'),
                'text_api_error' => __('An API error occurred. Check console or plugin settings.', 'ai-cat-content-gen-google'),
                'text_ajax_error' => __('An AJAX error occurred. Please try again.', 'ai-cat-content-gen-google'),
                'text_prompt_placeholder' => __('Enter prompt for AI image generation (e.g., "futuristic cityscape at sunset")...', 'ai-cat-content-gen-google'),
                'text_no_venice_key' => __('Venice AI API key missing in plugin settings. Image refinement is disabled.', 'ai-cat-content-gen-google'),
                'text_no_prompt' => __('Please enter an image prompt.', 'ai-cat-content-gen-google'),
                'text_applied_success' => __('New featured image applied successfully. The featured image box should update shortly.', 'ai-cat-content-gen-google'),
                'text_apply_failed' => __('Failed to apply the selected image.', 'ai-cat-content-gen-google'),
                'text_options_generated' => __('Select any one to replace above featured image:', 'ai-cat-content-gen-google'), // %d will be replaced
                'text_all_failed' => __('Failed to generate any image options. Please check API key or try a different prompt.', 'ai-cat-content-gen-google'),
            ]);

            // post editor content refinement script (aiccgen-google-post-editor-refine-js)
            wp_localize_script('aiccgen-google-post-editor-refine-js', 'aiccgen_post_editor_refine_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(AICCG_GOOGLE_NONCE_ACTION),
                'plugin_base_url' => plugin_dir_url(__FILE__),
                'ajax_action' => AICCG_GOOGLE_AJAX_REFINE_POST_EDITOR_ACTION,
                'google_api_key_exists' => $google_api_key_exists,
                'text_reresearching_content' => __('Rebuilding content. Please wait up to 3 minutes. Content above will refresh when done.', 'ai-cat-content-gen-google'),
                'text_refining_content' => __('Rebuilding content. Please wait up to 3 minutes. Content above will refresh when done.', 'ai-cat-content-gen-google'),
                'text_refine_instructions_placeholder' => __('e.g., "Make it more concise", "Add a concluding paragraph".', 'ai-cat-content-gen-google'),
                'text_error_no_instructions' => __('Please enter refinement instructions.', 'ai-cat-content-gen-google'),
                'text_error_no_content' => __('Editor content is empty. Cannot refine.', 'ai-cat-content-gen-google'),
                'text_error_no_google_key' => __('Google AI API key missing in plugin settings. Content refinement is disabled.', 'ai-cat-content-gen-google'),
                'text_refine_success' => __('Content refined successfully and updated in the editor. Please scroll up to review changes. Remember to \'Publish\' post when it\'s ready.', 'ai-cat-content-gen-google'),
                'text_error_no_instructions_for_refine' => __('Please enter refinement instructions for the "Refine" type.', 'ai-cat-content-gen-google'), // Updated
                'text_refine_api_error' => __('Error refining content via API. Please check logs or try again.', 'ai-cat-content-gen-google'),
                'text_refine_ajax_error' => __('An AJAX error occurred while refining content.', 'ai-cat-content-gen-google'),
            ]);
        }
    }
    // Inline CSS for admin panel
    $custom_css = "#adminmenu #menu-posts-ai_suggestion .wp-submenu li:nth-child(3), #adminmenu #menu-posts-ai_suggestion .wp-submenu li:nth-child(4) {display: none;}.post-type-ai_suggestion .page-title-action{display: none;}.wrap-msgbtn {clear: both;width: 100%;margin-top: 10px;display: block;padding-top: 10px;font-weight: 700;}#adminmenu li.wp-has-current-submenu a.wp-has-submenu.wp-has-current-submenu.wp-menu-open.menu-top.menu-icon-ai_suggestion.menu-top-last, #adminmenu li a.wp-has-submenu.wp-not-current-submenu.menu-top.menu-icon-ai_suggestion.menu-top-last, #adminmenu li a.wp-has-submenu.wp-not-current-submenu.menu-top.menu-icon-ai_suggestion, #adminmenu li a.wp-has-submenu.wp-has-current-submenu.wp-menu-open.menu-top.menu-icon-ai_suggestion {background-color: #620909;}li#menu-posts-ai_suggestion ul li.wp-first-item:nth-child(2) a {color: #40f640;}";
    // Output the custom CSS in the admin head
    add_action('admin_head', function() use ($custom_css) {
        echo '<style type="text/css">' . $custom_css . '</style>';
    });

}
add_action('admin_enqueue_scripts', 'aiccgen_google_enqueue_admin_scripts');


// Settings Page, Sections, Fields
function aiccgen_google_add_admin_menu()
{
    add_submenu_page(
        'edit.php?post_type=ai_suggestion', // Parent slug for "Posts"
        __('AI Post Content & Image Settings', 'ai-cat-content-gen-google'),
        __('Settings', 'ai-cat-content-gen-google'),
        'manage_options',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_render_settings_page'
    );
}
add_action('admin_menu', 'aiccgen_google_add_admin_menu');




// Register Settings
function aiccgen_google_register_settings()
{
    register_setting(AICCG_GOOGLE_OPTION_GROUP, AICCG_GOOGLE_OPTION_NAME, [
        'sanitize_callback' => 'aiccgen_google_sanitize_options',
    ]);
    // === API Section ===
    add_settings_section(
        'aiccgen_google_section_api',
        __('API Configuration', 'ai-cat-content-gen-google'),
         'aiccgen_configuration_renderdesp',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    add_settings_field(
        'aiccgen_google_field_api_key',
        __('Google AI API Key (Content)', 'ai-cat-content-gen-google'),
        'aiccgen_google_field_api_key_render',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_section_api'
    );
    // WordAI API Key and Email
    add_settings_section(
        'aiccgen_wordai_section_api',
        __('WordAI API Configuration & Settings', 'ai-cat-content-gen-google'),
        'aiccgen_wordai_section_api_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    add_settings_field(
        'aiccgen_wordai_field_api_key',
        __('WordAI API Key', 'ai-cat-content-gen-google'),
        'aiccgen_wordai_field_api_key_render',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_section_api'
    );
    add_settings_field(
        'aiccgen_wordai_field_email',
        __('WordAI Email', 'ai-cat-content-gen-google'),
        'aiccgen_wordai_field_email_render',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_section_api'
    );
    
    // Add Venice API Key Field
    add_settings_field(
        'aiccgen_google_field_venice_api_key',
        __('Venice AI API Key (Image)', 'ai-cat-content-gen-google'),
        'aiccgen_google_field_venice_api_key_render',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_section_api'
    );
    // === Plugin Usage Instructions ===
    add_settings_section(
        'aiccgen_google_section_plugin_instructions',
        __('', 'ai-cat-content-gen-google'),
        'aiccgen_google_section_plugin_instructions_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );

    add_settings_section(
        'aiccgen_google_section_headglobal_formatting',
        __('Automated Generation', 'ai-cat-content-gen-google'),
        'aiccgen_google_section_headglobal_formatting_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    // === Global Formatting Rules Section ===
    add_settings_section(
        'aiccgen_google_section_global_formatting',
        __('', 'ai-cat-content-gen-google'),
        'aiccgen_google_section_global_formatting_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    // === Category Navigation ===
    add_settings_section(
        'aiccgen_google_category_nav',
        __('', 'ai-cat-content-gen-google'),
        'aiccgen_google_category_nav_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    // === Active Prompts Section ===
    add_settings_section(
        'aiccgen_google_section_prompts_active',
        '<span id="active-cat">' . __('Active Categories', 'ai-cat-content-gen-google') . '</span>',
        'aiccgen_google_section_prompts_active_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );

    
    add_settings_field(
        'aiccgen_google_field_global_formatting_instructions',
        __('Global Formatting & Content Rules', 'ai-cat-content-gen-google'),
        'aiccgen_google_field_global_formatting_instructions_render',
        AICCG_GOOGLE_SETTINGS_SLUG,
        'aiccgen_google_section_global_formatting'
    );
    // === Inactive Prompts Section ===
    add_settings_section(
        'aiccgen_google_section_prompts_inactive',
        '<span id="inactive-cat">' . __('Inactive Categories (No Content Prompt)', 'ai-cat-content-gen-google') . '</span>',
        'aiccgen_google_section_prompts_inactive_callback',
        AICCG_GOOGLE_SETTINGS_SLUG
    );
    

    // --- Prepare to add fields to sections ---
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $prompts = isset($options['prompts']) ? $options['prompts'] : [];
    $categories = get_categories(['hide_empty' => 0, 'exclude' => get_option('default_category'), 'orderby' => 'date', 'order' => 'DESC']);
    
    if ($categories) {
        $has_active = false;
        $has_inactive = false;

        // First Add fields for ACTIVE categories (based on content prompt)
        foreach ($categories as $category) {
            $cat_id = $category->term_id;
            // Active means it has a non-empty content prompt
            $has_content_prompt = isset($prompts[$cat_id]) && !empty(trim($prompts[$cat_id]));

            if ($has_content_prompt) {
                $has_active = true;
                add_settings_field(
                    'aiccgen_google_field_cat_settings_' . $cat_id,
                    '<span id="'.$category->slug.'">' . esc_html($category->name) . '</span>',
                    'aiccgen_google_field_category_settings_render',
                    AICCG_GOOGLE_SETTINGS_SLUG,
                    'aiccgen_google_section_prompts_active',
                    ['category_id' => $cat_id, 'category_name' => $category->name, 'slug' => $category->slug]
                );
            }
        }

        // Second Add fields for INACTIVE categories
        foreach ($categories as $category) {
            $cat_id = $category->term_id;
            $has_content_prompt = isset($prompts[$cat_id]) && !empty(trim($prompts[$cat_id]));

            if (!$has_content_prompt) {
                $has_inactive = true;
                add_settings_field(
                    'aiccgen_google_field_cat_settings_' . $cat_id,
                    '<span class="category-slgname" id="'.$category->slug.'">' . esc_html($category->name) . '<div class="category-collapseicon"><svg fill="#000000" viewBox="-6.5 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>dropdown</title> <path d="M18.813 11.406l-7.906 9.906c-0.75 0.906-1.906 0.906-2.625 0l-7.906-9.906c-0.75-0.938-0.375-1.656 0.781-1.656h16.875c1.188 0 1.531 0.719 0.781 1.656z"></path> </g></svg></div></span>',
                    'aiccgen_google_field_category_settings_render',
                    AICCG_GOOGLE_SETTINGS_SLUG,
                    'aiccgen_google_section_prompts_inactive',
                    ['category_id' => $cat_id, 'category_name' => $category->name, 'slug' => $category->slug]
                );
            }
        }
        // Add placeholder messages if a section ends up empty
        if (!$has_active) {
            add_settings_field(
                'aiccgen_google_field_no_active_prompts',
                 '',
                'aiccgen_google_field_no_active_prompts_render',
                AICCG_GOOGLE_SETTINGS_SLUG,
                'aiccgen_google_section_prompts_active'
            );
        }
        if (!$has_inactive) {
            add_settings_field(
                'aiccgen_google_field_no_inactive_prompts',
                 '',
                'aiccgen_google_field_no_inactive_prompts_render',
                AICCG_GOOGLE_SETTINGS_SLUG,
                'aiccgen_google_section_prompts_inactive'
            );
        }

    } else {
         add_settings_field(
             'aiccgen_google_field_no_categories',
             __('No Categories Found', 'ai-cat-content-gen-google'),
             'aiccgen_google_field_no_categories_render',
             AICCG_GOOGLE_SETTINGS_SLUG,
             'aiccgen_google_section_prompts_active'
         );
    }
}
add_action('admin_init', 'aiccgen_google_register_settings');

// --- Render Callbacks ---
function aiccgen_google_section_headglobal_formatting_callback() {
    echo '<p style="margin-bottom: 40px;">' . esc_html__('Configure generation settings for categories that have a saved content prompt. Posts and Featured images will be generated based on the frequency selected.', 'ai-cat-content-gen-google') . '</p>';

}
function aiccgen_google_section_global_formatting_callback() {}
function aiccgen_wordai_section_api_callback() {
    echo '<p>' . esc_html__('Configure your WordAI API credentials and choose where to apply WordAI rewriting. WordAI can help make the AI-generated content more unique.', 'ai-cat-content-gen-google') . '</p>';
}
function aiccgen_wordai_field_api_key_render() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $api_key = isset($options['wordai_api_key']) ? $options['wordai_api_key'] : '';
    ?>
    <input type="password" name="<?php echo esc_attr(AICCG_GOOGLE_OPTION_NAME); ?>[wordai_api_key]"
        value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="<?php esc_attr_e('Enter your WordAI API Key', 'ai-cat-content-gen-google'); ?>">
    <p class="description">For Rewritten the Google generated content: <strong><a href="https://wai.wordai.com/api" target="_blank">WordAI</a></strong></p>
    <?php
}
function aiccgen_wordai_field_email_render() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $email = isset($options['wordai_email']) ? $options['wordai_email'] : '';
    ?>
    <input type="email" name="<?php echo esc_attr(AICCG_GOOGLE_OPTION_NAME); ?>[wordai_email]"
        value="<?php echo esc_attr($email); ?>" class="regular-text" placeholder="<?php esc_attr_e('Enter your WordAI Account Email', 'ai-cat-content-gen-google'); ?>">
    <p class="description">email address associated with your: <strong><a href="https://wai.wordai.com/account" target="_blank">WordAI account</a></strong></p>
    <?php
}


// PLugin Instructions Text
function aiccgen_google_section_plugin_instructions_callback() { ?>
    <div class="glbl-pluginlistingtp">
        <h2 style="margin-top:0;">Plugin Usage Instructions:</h2>
        <div class="ai-suggestions-rules">
            <div class="ai-suggestions-doc">
                <h2>1. Initial Setup (API Keys &amp; Global Settings)</h2>
                <div class="ai-suggestionsdoc" style="padding-left: 30px;">
                    <strong>Step 1: Configure Google AI API Key (for AI Content Generation)</strong>
                    <ul>
                        <li>This key is essential for the plugin to generate any text content.</li>
                        <li>What it's for: This key connects to Google's Generative AI (Gemini models) to create the base text content for your posts or AI Suggestions based on your prompts.</li>
                        <li>Where to find your key:
                            <ul>
                                <li>Go to Google AI Studio.</li>
                                <li>Sign in with your Google account.</li>
                                <li>Create or retrieve your API key.</li>
                                <li>Create or retrieve your API key.</li>
                            </ul>
                        </li>
                    </ul>
                    <strong>Step 2: Configure WordAI API Key & Email (for Rewriting & Avoiding AI Detection)</strong>
                    <ul>
                        <li>This key is optional but highly recommended if you want to make the AI-generated content more unique and human-like.</li>
                        <li>What it's for: This key (along with your WordAI account email) connects to WordAI, a service that intelligently rewrites text. This helps:
                            <ul>
                                <li>Improve the uniqueness of the content.</li>
                                <li>Reduce the likelihood of the content being flagged as purely AI-generated.</li>
                            </ul>
                        </li>
                        <li>Where to find your key & email:
                            <ul>
                                <li>You'll need both your WordAI API Key and the email address associated with your WordAI account.</li>
                                <li>API Key: Log in to your WordAI account and navigate to the API section (usually at https://wai.wordai.com/api).</li>
                                <li>Email: This is the email you used to sign up for WordAI (account details usually at https://wai.wordai.com/account).</li>
                            </ul>
                        </li>
                    </ul>
                    <strong>Step 3: Configure Venice AI API Key (for AI Imagery)</strong>
                    <ul>
                        <li>This key is optional and allows the plugin to automatically generate featured images for your content.</li>
                        <li>What it's for: This key connects to Venice AI, a service for generating images from text prompts. If configured, the plugin can create relevant featured images to accompany your AI-generated posts.</li>
                        <li>Where to find your key:
                            <ul>
                                <li>Log in to your Venice AI account.</li>
                                <li>Navigate to your API settings (usually at https://venice.ai/settings/api).</li>
                            </ul>
                        </li>
                    </ul>
                    <strong>Step 4: Save Your Settings</strong>
                    <ul>
                        <li>After entering all your desired API keys, scroll to the bottom of the Settings page and click the "Save Settings" button.</li>
                        <li>Your API keys are now configured!</li>
                    </ul>
                </div>
            </div>
            <div class="ai-suggestions-doc">
                <h2>2. Automated Content &amp; Image Generation (Per Category)</h2>
                <ul>
                    <li>
                        <strong>Automated AI Suggestion Drafts:</strong> The plugin can automatically create "AI Suggestion" draft posts for specific categories on a schedule.
                    </li>
                    <li>
                        <strong>Locate Category Settings:</strong>
                        <ul>
                            <li>Scroll past "Global Formatting &amp; Content Rules" to find category settings.</li>
                            <li>
                                <strong>Category Navigation Links:</strong> Quick links to jump to specific category settings.
                            </li>
                            <li>
                                <strong>Active Categories:</strong> Categories with a "Content Prompt" and eligible for automation.
                            </li>
                            <li>
                                <strong>Inactive Categories:</strong> Categories without a "Content Prompt." Automation is disabled for these. Click the category name to expand settings.
                            </li>
                        </ul>
                    </li>
                    <li>
                        <strong>Configure a Category for Automation:</strong>
                        <ul>
                            <li>
                                <strong>Content Prompt (Required):</strong>
                                <ul>
                                    <li>Main instruction for the AI to generate content for this category.</li>
                                    <li>
                                        <strong>Example ("Local News" category):</strong> "Write a summary of the top 3 most important local news stories for [City Name] from the past week, focusing on community events, local government decisions, and business openings. Include a brief outlook for the coming week."
                                    </li>
                                    <li>This field <strong>must</strong> be filled to activate automation for a category.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Formatting &amp; Content Rules (Category Specific):</strong>
                                <ul>
                                    <li>Define rules for this category only (overrides global rules).</li>
                                    <li>If left blank, global rules are used.</li>
                                    <li>Applied during automated post creation.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Frequency (Requires Content Prompt):</strong>
                                <ul>
                                    <li>Select how often to generate content: Daily, Weekly, Monthly, or None.</li>
                                    <li>Dropdown enabled only if "Content Prompt" is filled.</li>
                                    <li>If set to None, automation is disabled for this category.</li>
                                    <li>Next scheduled run time is shown if active.</li>
                                    <li>For reliable scheduling, consider a server-level cron job hitting <code>wp-cron.php</code>.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Featured Image Prompt (Optional):</strong>
                                <ul>
                                    <li>Enter a prompt for AI to generate a featured image for posts in this category.</li>
                                    <li>
                                        <strong>Example:</strong> "A vibrant abstract representation of community and connection, suitable for a local news blog."
                                    </li>
                                    <li>Enabled only if "Content Prompt" is filled and a valid Venice AI API key is set.</li>
                                    <li>Images are generated in Landscape 3:2 aspect ratio.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Save Settings:</strong> After configuring, click "Save Settings" to update schedules.
                            </li>
                        </ul>
                    </li>
                    <li>
                        <strong>Automated Generation Process:</strong>
                        <ul>
                            <li>Based on the selected frequency, the plugin uses the "Content Prompt" and "Formatting &amp; Content Rules" to generate text.</li>
                            <li>If a "Featured Image Prompt" and Venice API key are provided, an image is generated.</li>
                            <li>An "AI Suggestion" draft post is created with the generated content and image (if any), assigned to the specified category.</li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="ai-suggestions-doc">
                <h2>3. Manual Content &amp; Image Generation</h2>
                <ul>
                    <li>
                        <strong>On-Demand Generation:</strong> Generate content and an image for a category instantly, without waiting for a schedule.
                    </li>
                    <li>
                        <strong>Generate for Category:</strong>
                        <ul>
                            <li>Select a category from the dropdown.</li>
                            <li>Only categories with a saved "Content Prompt" (see Section II) are enabled.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Image Prompt (Optional):</strong>
                        <ul>
                            <li>Enter a prompt to generate a featured image with the content.</li>
                            <li>Requires a valid Venice AI API key in global settings.</li>
                            <li>If no key is present, this field is disabled.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Click "Generate Now":</strong>
                        <ul>
                            <li>The plugin contacts Google AI for content and Venice AI for an image (if prompt provided).</li>
                            <li>A result area appears below the form.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Review and Refine the Suggestion:</strong>
                        <ul>
                            <li><strong>Generated Content:</strong> AI-generated text appears in a textarea.</li>
                            <li><strong>Generated Image Preview (if applicable):</strong> Preview of the generated image is shown.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Refine Content (Text):</strong>
                        <ul>
                            <li>Below the generated text, enter "Refinement Instructions" and click "Refine Now."</li>
                            <li>The content in the textarea updates based on your instructions.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Create Post (Draft):</strong>
                        <ul>
                            <li>Enter a "Post Title" for your new draft.</li>
                            <li>Click "Create Post (Draft)" (or "Create Draft (with Image)" / "Create Draft (Content Only)" depending on image generation).</li>
                            <li>An "AI Suggestion" draft is created with the title, content, and (if generated) the featured image.</li>
                            <li>A link to edit the draft is provided.</li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="ai-suggestions-doc">
                <h2>4. Working with "AI Suggestions" (Custom Post Type)</h2>
                <ul>
                    <li><strong>"AI Suggestions"</strong> are the draft posts created by either automated or manual generation. You manage them before they become live posts.</li>
                    <li><strong>Access AI Suggestions:</strong> Go to <code>AI Suggestions &gt; All AI Suggestions</code> in the WordPress admin menu.</li>
                    <li><strong>Note:</strong> The "Add New" button for AI Suggestions is hidden. You must create them through the settings page (Manual Generation) or let Automation  create them.</li>
                    <li><strong>Edit an AI Suggestion:</strong> Click on the title of an AI Suggestion to edit it, similar to a standard WordPress post.</li>
                    <li><strong>Content Refinement Meta Box (in Editor):</strong>
                        <ul>
                            <li>On the AI Suggestion edit screen, you'll find a "Content Refinement" meta box.</li>
                            <li><strong>Refinement Type:</strong>
                                <ul>
                                    <li><strong>Re-Research Refresh:</strong> Discards the current editor content and generates new content based on the original topic and any new "Refinement Instructions" you provide. Useful if the initial generation was way off.</li>
                                    <li><strong>Refine:</strong> Modifies the current text in the editor based on your "Refinement Instructions."</li>
                                </ul>
                            </li>
                            <li><strong>Refinement Instructions:</strong> Enter your instructions (e.g., "Add a concluding paragraph summarizing the key points," "Change the tone to be more enthusiastic").</li>
                            <li><strong>Click "Refine Now":</strong> The content in the main post editor will be updated. The page might require a refresh after a few moments to see changes if the process is long.</li>
                        </ul>
                    </li>
                    <li><strong>Featured Image Refinement Meta Box (in Editor):</strong>
                        <ul>
                            <li>If the AI Suggestion has a featured image, the "Featured Image" meta box on the right will have an additional "Modify Image" section.</li>
                            <li>This requires a valid Venice AI API Key.</li>
                            <li><strong>Refinement Instructions (Image Prompt):</strong> Enter a new prompt to change or refine the existing image (e.g., "Make the colors more vibrant," "Generate a similar image but with a futuristic theme").</li>
                            <li><strong>Click "Refine Now":</strong> The plugin will generate 3 new image options based on your prompt. These will appear below the button.</li>
                            <li><strong>Select an Image Option:</strong> Choose one of the newly generated images.</li>
                            <li><strong>Click "Apply Selected":</strong> The chosen image will become the new featured image for this AI Suggestion. The other 2 generated options will be deleted.</li>
                        </ul>
                    </li>
                    <li><strong>Publishing the AI Suggestion:</strong>
                        <ul>
                            <li>Once you are satisfied with the content and featured image of the AI Suggestion, click the "Publish" button (just like a normal post).</li>
                            <li>Upon publishing:
                                <ul>
                                    <li>The "AI Suggestion" will be converted into a standard WordPress "Post."</li>
                                    <li>The original "AI Suggestion" CPT entry will be automatically deleted.</li>
                                    <li>You will be redirected to the edit screen of the newly created standard "Post."</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="ai-suggestions-doc">
                <h2>5. Important Notes</h2>
                <ul>
                    <li><strong>Cron Jobs:</strong> Automated generation relies on WordPress's cron system. This system is triggered by visits to your website. For highly reliable and timely automated posting, consider setting up a server-level cron job to ping your wp-cron.php file regularly (e.g., every 5-15 minutes).</li>
                    <li><strong>API Usage & Costs:</strong> Be mindful of your API usage with Google AI and Venice AI, as they may have free tiers and paid plans.</li>
                    <li><strong>Content Review:</strong> AI-generated content should always be reviewed and edited for accuracy, tone, and originality before publishing.</li>
                    <li><strong>API Key Security:</strong> Treat your API keys like passwords. Do not share them publicly.</li>
                </ul>
            </div>
        </div>
    </div>
<?php }

// Category Navigation
function aiccgen_google_category_nav_callback() {
    echo '<ul class="top-catlistting">';
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $prompts = isset($options['prompts']) ? $options['prompts'] : [];
    $categories = get_categories(['hide_empty' => 0, 'exclude' => get_option('default_category'), 'orderby' => 'date', 'order' => 'DESC']);
    // All Active
    foreach ($categories as $term) {
        $has_prompt = isset($prompts[$term->term_id]) && !empty(trim($prompts[$term->term_id]));
        if ($has_prompt) {
            echo '<li><a href="#' . esc_html($term->slug) . '">' . esc_html($term->name) . '</a></li>';
        }
    }
    // Inactive
    echo '<li><a href="#inactive-cat">' . esc_html__('INACTIVE', 'ai-cat-content-gen-google') . '</a></li>';
    echo '</ul>';
}


function aiccgen_google_section_prompts_active_callback() {}

function aiccgen_google_section_prompts_inactive_callback() {
     echo '<hr>';
}

function aiccgen_google_field_no_active_prompts_render() {
    echo '<em>' . esc_html__('No categories currently have active content prompts. Add a content prompt to a category in the section below to activate it.', 'ai-cat-content-gen-google') . '</em>';
}

function aiccgen_google_field_no_inactive_prompts_render() {
    echo '<em>' . esc_html__('All categories have active content prompts configured above.', 'ai-cat-content-gen-google') . '</em>';
}

function aiccgen_google_field_no_categories_render() {
    echo '<em>' . esc_html__('No categories found. Please create some post categories.', 'ai-cat-content-gen-google') . '</em>';
}

// Global Formatting & Content Rules textarea
function aiccgen_google_field_global_formatting_instructions_render() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $global_formatting_instructions = isset($options['global_formatting_instructions']) ? $options['global_formatting_instructions'] : ''; ?>
    <textarea name="<?php echo esc_attr(AICCG_GOOGLE_OPTION_NAME); ?>[global_formatting_instructions]"
              id="aiccgen_google_global_formatting_instructions"
              rows="5"
              class="large-text"
              placeholder="<?php esc_attr_e('e.g., Use H2 for headings, wrap paragraphs in <p>, avoid words like "example". Each rule on a new line.', 'ai-cat-content-gen-google'); ?>"><?php echo esc_textarea($global_formatting_instructions); ?></textarea>
    <p class="description">
        <?php esc_html_e('These rules apply if a category does not have its own specific formatting rules defined.', 'ai-cat-content-gen-google'); ?>
    </p>
    <?php
}

function aiccgen_configuration_renderdesp() { ?>
    <p class="description" style="font-weight: 500;font-style: italic;color: #c10c0c;"><?php printf(esc_html__('A valid Gemini API Key is mandatory to generate AI content. You cannot click the "Save Settings" button without entering the Gemini API Key. WordAI API/Email and Venice AI API are optional.', 'ai-cat-content-gen-google')); ?></p>
    <?php
}

// Google AI API Key (Content)
function aiccgen_google_field_api_key_render() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    $model_used = 'gemini-2.5-flash'; // Hardcoded model ?>
    <input type="password" name="<?php echo esc_attr(AICCG_GOOGLE_OPTION_NAME); ?>[api_key]"
        value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="<?php esc_attr_e('Enter your Gemini API Key', 'ai-cat-content-gen-google'); ?>" required>
    <p class="description"><?php printf(esc_html__('For content generation using model: %s', 'ai-cat-content-gen-google'), '<strong><a target="_blank" href="https://aistudio.google.com/apikey">Gemini</a></strong>'); ?></p>
    <?php
}

// Venice AI API Key (Image)	
function aiccgen_google_field_venice_api_key_render() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $venice_api_key = isset($options['venice_api_key']) ? $options['venice_api_key'] : ''; ?>
    <input type="password" name="<?php echo esc_attr(AICCG_GOOGLE_OPTION_NAME); ?>[venice_api_key]" value="<?php echo esc_attr($venice_api_key); ?>" class="regular-text" placeholder="<?php esc_attr_e('Enter your Venice AI API Key', 'ai-cat-content-gen-google'); ?>">
    <p class="description">For image generation: <strong><a href="https://venice.ai/settings/api" target="_blank">Venice.ai</a></strong></p>
    <?php
}

