<?php
// ALL settings HTML Content Prompt, Formatting & Content Rules, Frequency, Refine Content, Featured Image Prompt
function aiccgen_google_field_category_settings_render($args) {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $category_id = $args['category_id'];
    $category_name = $args['category_name'];
    $category_slug = $args['slug'];

    $prompt = isset($options['prompts'][$category_id]) ? $options['prompts'][$category_id] : '';
    $frequency = isset($options['frequency'][$category_id]) ? $options['frequency'][$category_id] : 'none';
    $image_prompt = isset($options['image_prompts'][$category_id]) ? $options['image_prompts'][$category_id] : '';
    $formatting_instructions = isset($options['formatting_instructions'][$category_id]) ? $options['formatting_instructions'][$category_id] : '';
    $has_content_prompt = !empty(trim($prompt));

    $has_image_prompt_in_settings = !empty(trim($image_prompt));

    $option_name_base = AICCG_GOOGLE_OPTION_NAME;

    // Frequency options
    $frequencies = [
        'none' => __('None', 'ai-cat-content-gen-google'),
        'daily' => __('Daily', 'ai-cat-content-gen-google'),
        'weekly' => __('Weekly', 'ai-cat-content-gen-google'),
        'monthly' => __('Monthly', 'ai-cat-content-gen-google'),
    ];

    // Get schedule info 
    $schedule_info = '';
    $args_for_cron = ['category_id' => $category_id];
    $next_run = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args_for_cron);


    if ($frequency !== 'none' && $has_content_prompt) {
        if ($next_run) {
            $schedule_info = sprintf(
                __('Next scheduled run: %s (UTC)', 'ai-cat-content-gen-google'),
                '<code>' . date('Y-m-d H:i:s', $next_run) . '</code>'
            );
        } else {
             $schedule_info = __('Scheduling pending or check WP Cron.', 'ai-cat-content-gen-google');
        }
    } elseif (!$has_content_prompt) {
         $schedule_info = __('Enter a content prompt and select frequency to activate generation.', 'ai-cat-content-gen-google');
    } else {
         $schedule_info = __('Select Daily/Weekly/Monthly frequency to schedule content generation.', 'ai-cat-content-gen-google');
    }

    $formatting_textarea_id = esc_attr($option_name_base) . '_formatting_instructions_' . intval($category_id);
    $image_prompt_textarea_id = esc_attr($option_name_base) . '_image_prompt_' . intval($category_id); ?>

    <div class="category-settings-group <?php echo empty(trim($prompt)) ? 'plgcollapse' : 'plgexpand'; ?>" id="<?php echo esc_attr($category_slug); ?>">
        <!-- Content Prompt Textarea -->
        <div class="aiccgen-field-group">
            <label for="<?php echo esc_attr($option_name_base); ?>_prompts_<?php echo intval($category_id); ?>"><strong><?php esc_html_e('Content Prompt:', 'ai-cat-content-gen-google'); ?></strong></label>
            <textarea id="<?php echo esc_attr($option_name_base); ?>_prompts_<?php echo intval($category_id); ?>" name="<?php echo esc_attr($option_name_base); ?>[prompts][<?php echo intval($category_id); ?>]" rows="4" class="large-text" placeholder="<?php esc_attr_e('Enter prompt for content generation...', 'ai-cat-content-gen-google'); ?>"><?php echo esc_textarea($prompt); ?></textarea>
        </div>

        <!-- Formatting & Content Rules Textarea -->
        <div class="aiccgen-field-group">
            <label for="<?php echo $formatting_textarea_id; ?>"><strong><?php esc_html_e('Formatting & Content Rules:', 'ai-cat-content-gen-google'); ?></strong></label>
            <textarea id="<?php echo $formatting_textarea_id; ?>" name="<?php echo esc_attr($option_name_base); ?>[formatting_instructions][<?php echo intval($category_id); ?>]" rows="4" class="large-text" placeholder="<?php esc_attr_e('e.g., Use H2 for headings, wrap paragraphs in <p>, avoid words like "Jeff", "Abusive words", ensure professional tone.', 'ai-cat-content-gen-google'); ?>"><?php echo esc_textarea($formatting_instructions); ?></textarea>
            <p class="description"><em>These formatting are applied during automated posting.</em></p>
        </div>

        <!-- Frequency Dropdown for schedules-->
         <div class="aiccgen-field-group">
            <label for="<?php echo esc_attr($option_name_base); ?>_frequency_<?php echo intval($category_id); ?>"><strong><?php esc_html_e('Frequency:', 'ai-cat-content-gen-google'); ?></strong></label>
            <select id="<?php echo esc_attr($option_name_base); ?>_frequency_<?php echo intval($category_id); ?>" name="<?php echo esc_attr($option_name_base); ?>[frequency][<?php echo intval($category_id); ?>]" <?php echo empty(trim($prompt)) ? 'disabled' : ''; ?>>
                <?php foreach ($frequencies as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($frequency, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <div id="<?php echo esc_attr($option_name_base); ?>_api_key_notice_<?php echo intval($category_id); ?>"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const promptField = document.getElementById('<?php echo esc_attr($option_name_base); ?>_prompts_<?php echo intval($category_id); ?>');
                    const frequencyField = document.getElementById('<?php echo esc_attr($option_name_base); ?>_frequency_<?php echo intval($category_id); ?>');

                    promptField.addEventListener('blur', function () {
                        if (promptField.value.trim() === '') {
                            frequencyField.disabled = true;
                        } else {
                            frequencyField.disabled = false;
                        }
                    });

                    var frequencySelect = document.getElementById('<?php echo esc_attr($option_name_base); ?>_frequency_<?php echo intval($category_id); ?>');
                    if (!frequencySelect) return;

                    // Create or get notice area
                    var noticeId = '<?php echo esc_attr($option_name_base); ?>_api_key_notice_<?php echo intval($category_id); ?>';
                    var noticeArea = document.getElementById(noticeId);
                    if (!noticeArea) {
                        noticeArea = document.createElement('div');
                        noticeArea.id = noticeId;
                        frequencySelect.parentNode.appendChild(noticeArea);
                    }

                    frequencySelect.addEventListener('change', function () {
                        noticeArea.innerHTML = '<span class="aiccgen-refine-draft-loader" id="" style="display: none; margin-left: 5px;">' +
                            '<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'img/loading.gif'); ?>" alt="Loading..."></span>';
                        jQuery.post(ajaxurl, {
                            action: 'aiccgen_google_check_api_key'
                        }, function(response) {
                            if (response.success) {
                                //noticeArea.innerHTML = '<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>';
                            } else {
                                noticeArea.innerHTML = '<div class="notice notice-error is-dismissible" style="margin-bottom: 0;"><p style="font-size: 13px;">' + response.data.message + '</p></div>';
                            }
                        });
                    });
                });
            </script>
            <p class="description">
                <?php if ($schedule_info) : ?>
                     <span style="color:#666; font-style: italic;font-size:13px;"><?php echo wp_kses($schedule_info, ['code' => []]); ?></span>
                <?php endif; ?>
            </p>
         </div>

        <!-- Featured Image Prompt Textarea -->
        <div class="aiccgen-field-group">
            <label for="<?php echo $image_prompt_textarea_id; ?>"><strong><?php esc_html_e('Featured Image Prompt:', 'ai-cat-content-gen-google'); ?></strong></label>
            <textarea <?php echo empty(trim($prompt)) ? 'disabled' : ''; ?> id="<?php echo $image_prompt_textarea_id; ?>" name="<?php echo esc_attr($option_name_base); ?>[image_prompts][<?php echo intval($category_id); ?>]" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter prompt for image generation...', 'ai-cat-content-gen-google'); ?>"><?php echo esc_textarea($image_prompt); ?></textarea>
            <p style="color: #666;font-style: italic;font-size: 13px;">Aspect Ratio (Landscape 3:2)</p>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const promptField = document.getElementById('<?php echo esc_attr($option_name_base); ?>_prompts_<?php echo intval($category_id); ?>');
                    const FeaturedField = document.getElementById('<?php echo $image_prompt_textarea_id; ?>');

                    promptField.addEventListener('blur', function () {
                        if (promptField.value.trim() === '') {
                            FeaturedField.disabled = true;
                        } else {
                            FeaturedField.disabled = false;
                        }
                    });
                });
            </script>
        </div>
        <!-- Refine Featured Image Button Section -->        
    </div>
    <?php
}


// Sanitize Options based on Frequency
function aiccgen_google_sanitize_options($input) {
    $sanitized_input = [];
    $options = get_option(AICCG_GOOGLE_OPTION_NAME); // Get old options

    // Sanitize Google API Key
    $sanitized_input['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : (isset($options['api_key']) ? $options['api_key'] : '');
    // Sanitize Venice API Key
    $sanitized_input['venice_api_key'] = isset($input['venice_api_key']) ? sanitize_text_field($input['venice_api_key']) : (isset($options['venice_api_key']) ? $options['venice_api_key'] : '');

    // Sanitize Model (using default)
    $sanitized_input['model'] = 'gemini-2.5-flash'; // Hardcoded default model

    $sanitized_input['wordai_api_key'] = isset($input['wordai_api_key']) ? sanitize_text_field($input['wordai_api_key']) : (isset($options['wordai_api_key']) ? $options['wordai_api_key'] : '');
    $sanitized_input['wordai_email'] = isset($input['wordai_email']) ? sanitize_email($input['wordai_email']) : (isset($options['wordai_email']) ? $options['wordai_email'] : '');
    $sanitized_input['wordai_enable_automated'] = isset($input['wordai_enable_automated']) ? true : false;
    $sanitized_input['wordai_enable_manual'] = isset($input['wordai_enable_manual']) ? true : false;

    // Sanitize Global Formatting Instructions
    if (isset($input['global_formatting_instructions'])) {
        $sanitized_input['global_formatting_instructions'] = sanitize_textarea_field(wp_unslash($input['global_formatting_instructions']));
    } else {
        $sanitized_input['global_formatting_instructions'] = isset($options['global_formatting_instructions']) ? $options['global_formatting_instructions'] : '';
    }

    // Allowed frequencies
    $allowed_frequencies = ['none', 'daily', 'weekly', 'monthly'];

    // Sanitize Prompts, Frequency, and Image Prompts per category
    $sanitized_input['prompts'] = [];
    $sanitized_input['frequency'] = [];
    $sanitized_input['image_prompts'] = []; // Initialize image prompts array
    $sanitized_input['formatting_instructions'] = [];

    // Get all possible category IDs to ensure we process deletions correctly
    $all_possible_cat_ids = get_terms(['taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false]);
    if (is_wp_error($all_possible_cat_ids) || !is_array($all_possible_cat_ids)) {
        $all_possible_cat_ids = [];
    }
    // Determine which categories were submitted (based on content prompts array presence)
    $submitted_cat_ids = isset($input['prompts']) && is_array($input['prompts']) ? array_keys($input['prompts']) : [];

    if (isset($input['formatting_instructions']) && is_array($input['formatting_instructions'])) { $submitted_cat_ids = array_merge($submitted_cat_ids, array_keys($input['formatting_instructions'])); }

    // Process all categories that exist or were submitted
    $process_cat_ids = array_unique(array_merge($submitted_cat_ids, $all_possible_cat_ids));


    foreach ($process_cat_ids as $cat_id) {
        $cat_id_int = absint($cat_id);
        if ($cat_id_int === 0) continue;

        $has_content_prompt = false;
        // Sanitize Content Prompt
        if (isset($input['prompts'][$cat_id_int])) {
            $sanitized_prompt = sanitize_textarea_field(wp_unslash($input['prompts'][$cat_id_int]));
             if (!empty(trim($sanitized_prompt))) {
                $sanitized_input['prompts'][$cat_id_int] = $sanitized_prompt;
                $has_content_prompt = true;
            }
        }

        // Sanitize Frequency - Only if content prompt exists
        if ($has_content_prompt) {
            if (isset($input['frequency'][$cat_id_int])) {
                $submitted_frequency = sanitize_text_field($input['frequency'][$cat_id_int]);
                $sanitized_input['frequency'][$cat_id_int] = in_array($submitted_frequency, $allowed_frequencies) ? $submitted_frequency : 'none';
            } else {
                 $sanitized_input['frequency'][$cat_id_int] = 'none'; // Default to none if not submitted
            }
        } else {
             // Force frequency to 'none' if no content prompt
             $sanitized_input['frequency'][$cat_id_int] = 'none';
        }

        // Sanitize Image Prompt - Only save if content prompt exists
        if ($has_content_prompt) {
             if (isset($input['image_prompts'][$cat_id_int])) {
                $sanitized_image_prompt = sanitize_textarea_field(wp_unslash($input['image_prompts'][$cat_id_int]));
                if (!empty(trim($sanitized_image_prompt))) {
                     $sanitized_input['image_prompts'][$cat_id_int] = $sanitized_image_prompt;
                }
             }
        }

        if (isset($input['formatting_instructions'][$cat_id_int])) {
            $sanitized_formatting_instructions = sanitize_textarea_field(wp_unslash($input['formatting_instructions'][$cat_id_int]));
            
            $sanitized_input['formatting_instructions'][$cat_id_int] = $sanitized_formatting_instructions;
        } else {
             
             $sanitized_input['formatting_instructions'][$cat_id_int] = '';
        }

        
        if (!$has_content_prompt) {
            unset($sanitized_input['frequency'][$cat_id_int]);
            unset($sanitized_input['image_prompts'][$cat_id_int]);
        }

    }

    return $sanitized_input;
}

// Settings Page HTML Manual Process
function aiccgen_google_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'ai-cat-content-gen-google'));
    }
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $venice_api_key_exists = !empty($options['venice_api_key']); ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php aiccgen_google_show_save_notices(); // Display feedback notices ?>

        <form action="options.php" method="post">
            <?php settings_fields(AICCG_GOOGLE_OPTION_GROUP);
            do_settings_sections(AICCG_GOOGLE_SETTINGS_SLUG);
            submit_button(__('Save Settings', 'ai-cat-content-gen-google')); ?>
        </form>

        <hr>
        <h2><?php esc_html_e('Manual Generation (Content & Image)', 'ai-cat-content-gen-google'); ?></h2>
        <p><?php esc_html_e('Use this section to manual generation for a each category. Select a category with a saved content prompt and enter an image prompt to featured image generation.', 'ai-cat-content-gen-google'); ?></p>
         <form id="aiccgen-google-generate-form">
             <table class="form-table" role="presentation">
                 <tbody>
                     <tr>
                         <th scope="row"><label for="aiccgen_google_category_to_generate"><?php esc_html_e('Generate for Category', 'ai-cat-content-gen-google'); ?></label></th>
                         <td>
                             <?php
                             // Options already fetched above
                             $categories = get_categories(['hide_empty' => 0, 'exclude' => get_option('default_category'), 'orderby' => 'date', 'order' => 'DESC']);
                             $prompts = isset($options['prompts']) ? $options['prompts'] : [];
                             ?>
                             <select name="aiccgen_google_category_to_generate" id="aiccgen_google_category_to_generate" required>
                                 <option value=""><?php esc_html_e('-- Select Category --', 'ai-cat-content-gen-google'); ?></option>
                                 <?php if ($categories):
                                     usort($categories, function($a, $b) use ($prompts) { /* ... sort logic same as before ... */
                                         $a_has_prompt = isset($prompts[$a->term_id]) && !empty(trim($prompts[$a->term_id]));
                                         $b_has_prompt = isset($prompts[$b->term_id]) && !empty(trim($prompts[$b->term_id]));
                                         if ($a_has_prompt == $b_has_prompt) {
                                             return strcmp($a->name, $b->name);
                                         }
                                         return $a_has_prompt ? -1 : 1;
                                     });
                                     foreach ($categories as $category):
                                         $has_prompt = isset($prompts[$category->term_id]) && !empty(trim($prompts[$category->term_id]));
                                         $prompt_indicator = $has_prompt ? '' : __(' (No content prompt)', 'ai-cat-content-gen-google');
                                         ?>
                                             <option value="<?php echo intval($category->term_id); ?>" <?php disabled(!$has_prompt); ?>>
                                                 <?php echo esc_html($category->name . $prompt_indicator); ?>
                                             </option>
                                         <?php
                                     endforeach;
                                 endif; ?>
                             </select>
                             <p class="description"><em><?php esc_html_e('Only categories with saved content prompts are enabled.', 'ai-cat-content-gen-google'); ?></em></p>
                         </td>
                     </tr>
                     <?php // --- Manual Image Prompt Textarea --- ?>
                     <tr>
                         <th scope="row"><label for="aiccgen_google_image_prompt_manual"><?php esc_html_e('Image Prompt (Optional)', 'ai-cat-content-gen-google'); ?></label></th>
                         <td>
                             <textarea id="aiccgen_google_image_prompt_manual" name="aiccgen_google_image_prompt_manual" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter prompt for AI image generation...', 'ai-cat-content-gen-google'); ?>" <?php disabled(!$venice_api_key_exists); ?>></textarea>
                         </td>
                     </tr>
                      <tr>
                         <th scope="row"></th>
                         <td>
                              <span class="wrploader-wrap">
                                 <input type="submit" name="aiccgen_google_submit_generate" class="button button-primary" value="<?php esc_attr_e('Generate Now', 'ai-cat-content-gen-google'); ?>"> <?php // Changed button text & style ?>
                                 <span id="aiccgen-google-loader" style="display: none;"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/loading.gif" alt="Loading..."></span>
                             </span>
                             <span id="aiccgen-google-loader" style="display: none;"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/loading.gif" alt="Loading..."></span>
                            <div id="aiccgen-google-api-key-notice" style="margin-top:10px;"></div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var form = document.getElementById('aiccgen-google-generate-form');
                                var submitBtn = form.querySelector('input[type="submit"][name="aiccgen_google_submit_generate"]');
                                var loader = document.getElementById('aiccgen-google-loader');
                                var noticeArea = document.getElementById('aiccgen-google-api-key-notice');

                                if (!form || !submitBtn) return;

                                form.addEventListener('submit', function (e) {
                                    // Show loader, clear notice
                                    if (loader) loader.style.display = 'inline-block';
                                    if (noticeArea) noticeArea.innerHTML = '';

                                    // Prevent default submit
                                    e.preventDefault();

                                    // Check API key via AJAX
                                    jQuery.post(ajaxurl, {
                                        action: 'aiccgen_google_check_api_key'
                                    }, function(response) {
                                        if (response.success) {
                                            
                                        } else {
                                            if (loader) loader.style.display = 'none';
                                            if (noticeArea) {
                                                noticeArea.innerHTML = '<div class="notice notice-error is-dismissible" style="margin-bottom: 0;"><p style="font-size: 13px;">' + response.data.message + '</p></div>';
                                            }
                                        }
                                    });
                                });
                            });
                            </script>
                         </td>
                     </tr>
                 </tbody>
             </table>
         </form>
         <div id="aiccgen-google-result-area" style="margin-top: 20px; display: none;">
             <!-- AJAX results loaded here -->
         </div>

    </div><!-- /.wrap -->
    <?php
}

// Refine Meta box for the post featured images
function aiccgen_google_add_refine_to_post_thumbnail_meta_box($content, $post_id, $thumbnail_id) {
    // Only add for 'post' post type for now, can be expanded
    if (get_post_type($post_id) !== 'ai_suggestion') {
        return $content;
    }

    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $venice_api_key_exists = !empty($options['venice_api_key']);

    $refine_ui = '<div id="aiccgen-post-refine-image-wrapper" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;"><div class="postbox-header"><h2 class="hndle ui-sortable-handle">Modify Image</h2></div>';
    
    if (!$venice_api_key_exists) {
        $refine_ui .= '<p class="notice notice-warning inline"><small>' . esc_html__('Venice AI API Key is missing in plugin settings. AI Image Refinement is disabled.', 'ai-cat-content-gen-google') . '</small></p>';
    } else {
        $refine_ui .= '<p class="description reinfe-cntbx">Now that you can check the image, if it is not what you want; please use the field below to refine it and then click the "Refine Now" button below to make it better.</p>';
        $refine_ui .= '<p><label for="aiccgen-post-image-prompt"><strong>' . esc_html__('Refinement Instructions:', 'ai-cat-content-gen-google') . '</strong></label><br>';
        $refine_ui .= '<textarea id="aiccgen-post-image-prompt" rows="2" style="width:100%;" placeholder="' . esc_attr__('Make generated featured image more casual and refine...', 'ai-cat-content-gen-google') . '"></textarea></p>';        
        $refine_ui .= '<button type="button" id="aiccgen-post-refine-image-button" class="button button-postmetabx">';
        $refine_ui .= esc_html__('Refine Now', 'ai-cat-content-gen-google');
        $refine_ui .= '</button>';
        $refine_ui .= '<span id="aiccgen-post-refine-loader" style="display:none; margin-left:10px; vertical-align:middle;"><img src="' . esc_url(plugin_dir_url(__FILE__) . 'img/loading.gif') . '" alt="Loading..." style="width:16px;height:16px;"></span>';
        $refine_ui .= '<div id="aiccgen-post-refine-status" style="margin-top:5px;"></div>';
        $refine_ui .= '<div id="aiccgen-post-image-options-area" style="margin-top:10px; display:none;"></div>';
    }
    
    $refine_ui .= '</div>';

    return $content . $refine_ui;
}
add_filter('admin_post_thumbnail_html', 'aiccgen_google_add_refine_to_post_thumbnail_meta_box', 10, 3);

// Add a meta box to the post editor for Refine post content 
function aiccgen_google_add_content_refinement_meta_box() {
    add_meta_box(
        'aiccgen_content_refinement_meta_box',
        __('Content Refinement', 'ai-cat-content-gen-google'),
        'aiccgen_google_render_content_refinement_meta_box',
        'ai_suggestion',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aiccgen_google_add_content_refinement_meta_box');

// Refine content Post editor meta box
function aiccgen_google_render_content_refinement_meta_box($post) {
    wp_nonce_field(AICCG_GOOGLE_NONCE_ACTION, 'aiccgen_content_refine_nonce');
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $google_api_key_exists = !empty($options['api_key']); ?>
    <script>
        jQuery(document).ready(function($) {
            jQuery('.post-type-ai_suggestion div#publishing-action').after('<div class=\"wrap-msgbtn\">After clicking the \"Publish\" button the AI suggestion will automatically move under the Posts and published.</div>');
        });
    </script>
    <div id="aiccgen-post-editor-refine-wrapper">
        <?php if (!$google_api_key_exists): ?>
            <p class="notice notice-warning inline"><small><?php esc_html_e('Google AI API Key is missing in plugin settings. Content refinement is disabled.', 'ai-cat-content-gen-google'); ?></small></p>
        <?php else: ?>
            <p class="description reinfe-cntbx"><?php esc_html_e('Now that you have read the content above, if it is not what you want; please use the field below to refine it and then click the "Refine Now" button below to make it better. (after clicking button please wait 4 minutes, and then refresh page)', 'ai-cat-content-gen-google'); ?></p>

                <select id="aiccgen-post-editor-refine-type" name="aiccgen_post_editor_refine_type" style="width:100%;">
                    <option value="reresearch_refresh"><?php esc_html_e('Re-Research Refresh (Rewrite based on instructions (optional))', 'ai-cat-content-gen-google'); ?></option>
                    <option value="refine" selected><?php esc_html_e('Refine (Take current text, and rewrite based on instructions)', 'ai-cat-content-gen-google'); ?></option>
                </select>
            
            <p>
                <label for="aiccgen-post-editor-refine-instructions"><strong><?php esc_html_e('Refinement Instructions:', 'ai-cat-content-gen-google'); ?></strong></label><br>
                <textarea id="aiccgen-post-editor-refine-instructions" rows="3" style="width:100%;" placeholder="<?php esc_attr_e('Make generated content more casual and refine...', 'ai-cat-content-gen-google'); ?>"></textarea>
            </p>
            <p>
                <button type="button" id="aiccgen-post-editor-refine-button" class="button button-postmetabx">
                    <?php esc_html_e('Refine Now', 'ai-cat-content-gen-google'); ?>
                </button>
                
                <span id="aiccgen-post-editor-refine-loader" style="display:none; margin-left:10px; vertical-align:middle;">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'img/loading.gif'); ?>" alt="Loading..." style="width:16px;height:16px;">
                </span>
            </p>
            <div id="aiccgen-post-editor-refine-status" style="margin-top:5px;"></div>
        <?php endif; ?>
    </div>
    <?php
}

// "Re-Research Refresh" Selection form dropdown (Instructions are optional)
function aiccgen_google_build_reresearch_refresh_prompt($original_content_for_topic_context, $user_refinement_instructions = '') { 
    $current_date = date_i18n(get_option('date_format'));

    $instructions_guidance = "";
    if (!empty(trim($user_refinement_instructions))) {
        $instructions_guidance = sprintf(
            "4.  After generating the new content, **strictly apply the 'User's Instructions for the New Content'** provided below to modify, structure, and tone this new content.\n" .
            "**User's Instructions for the New Content (apply these to your re-researched content):**\n---\n%s\n---\n\n",
            esc_html($user_refinement_instructions)
        );
    } else {
        // If no instructions, guide the AI to generate good default content
        $instructions_guidance = "4.  Focus on generating fresh, comprehensive, well-structured, and engaging HTML content based on the identified topic. Ensure the tone is informative and professional.\n\n";
    }

    return sprintf(
        "You are an expert content generator. A user wants to refresh content on a specific topic for their blog.\n" .
        "The 'Previous Content Context' below is provided **only to help you identify the core topic**. Do NOT refine or directly use parts of this previous content; your task is to research and write *entirely new* content on the identified subject.\n\n" .
        "**Your Task:**\n" .
        "1.  Analyze the 'Previous Content Context' to clearly understand the core topic.\n" .
        "2.  Perform new web searches (using information current up to %s) for fresh, relevant, and factual information specifically on this identified topic.\n" .
        "3.  Generate completely new content based on your fresh research. This new content should be distinct from the 'Previous Content Context'.\n" .
        "%s" .
        "5.  The final output must be well-structured HTML. Use `<p>` tags for paragraphs. If headings are appropriate for structuring the new content, use `<h2>` or `<h3>`. If lists are suitable, use `<ul>/<li>` or `<ol>/<li>`. Ensure all text is within appropriate HTML tags.\n" .
        "6.  Avoid using Markdown syntax (like `## Heading`, `**bold**`) in the final HTML output. Output only valid HTML.\n\n" .
        "**Previous Content Context (for topic identification only):**\n---\n%s\n---\n",
        esc_html($current_date),
        $instructions_guidance,
        $original_content_for_topic_context
    );
}

// Refine content Post editor meta box
function aiccgen_google_ajax_refine_post_editor_content() {
    check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Permission denied to edit this post.', 'ai-cat-content-gen-google')], 403);
    }

    $original_content = isset($_POST['original_content']) ? wp_kses_post(wp_unslash($_POST['original_content'])) : '';
    $refinement_instructions = isset($_POST['refinement_instructions']) ? sanitize_textarea_field(wp_unslash($_POST['refinement_instructions'])) : '';
    $refinement_type = isset($_POST['refinement_type']) ? sanitize_text_field($_POST['refinement_type']) : 'refine';

    // Original content from editor is always needed for context, even if just for topic identification.
    if (empty($original_content)) {
         wp_send_json_error(['message' => __('Editor content is empty. Cannot proceed.', 'ai-cat-content-gen-google')], 400);
    }

    // Refinement instructions are mandatory *only* if the type is 'refine'.
    if ($refinement_type === 'refine' && empty($refinement_instructions)) {
        wp_send_json_error(['message' => __('Refinement instructions are missing for "Refine" type.', 'ai-cat-content-gen-google')], 400);
    }

    if (!in_array($refinement_type, ['refine', 'reresearch_refresh'])) {
        wp_send_json_error(['message' => __('Invalid refinement type.', 'ai-cat-content-gen-google')], 400);
    }

    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $google_api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
    $model = isset($options['model']) && !empty(trim($options['model'])) ? $options['model'] : 'gemini-2.5-flash'; // Hardcoded default model

    if (empty($google_api_key)) {
        wp_send_json_error(['message' => __('Google AI API Key is missing in plugin settings.', 'ai-cat-content-gen-google')], 400);
    }

    $final_api_prompt = '';
    if ($refinement_type === 'reresearch_refresh') {

        $final_api_prompt = aiccgen_google_build_reresearch_refresh_prompt($original_content, $refinement_instructions);
    } else { // 'refine'
        $final_api_prompt = aiccgen_google_build_refinement_prompt($original_content, $refinement_instructions);
    }
    
    $response_data = aiccgen_google_call_gemini_api($google_api_key, $model, $final_api_prompt);

    if ($response_data['success']) {

        // Remove "html" at the very start (with or without whitespace/newlines)
        $refined_content = $response_data['content'];
        $refined_content = preg_replace('/^\s*```html\s*/i', '', $refined_content);
        $refined_content = preg_replace('/\s*```\s*$/i', '', $refined_content);
        $refined_content = trim($refined_content, "\xEF\xBB\xBF \t\n\r\0\x0B");
        wp_send_json_success(['refined_content' => $refined_content]);

    } else {
        $error_detail = $response_data['error'] ?? __('Unknown API error during content refinement.', 'ai-cat-content-gen-google');
        if (stripos($error_detail, "models/gemini-2.5-flash") !== false || stripos($error_detail, "not found") !== false) {
            $error_detail .= ' ' . __('Please check if the AI model name in plugin settings is current and valid.', 'ai-cat-content-gen-google');
        }
        wp_send_json_error(['message' => $error_detail], $response_data['code'] ?? 500);
    }
}
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_REFINE_POST_EDITOR_ACTION, 'aiccgen_google_ajax_refine_post_editor_content');
// Post Refine Meta box

// Refine Meta box ajax featured images
function aiccgen_google_ajax_post_generate_image_options() {
    check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');
    if (!current_user_can('edit_post', isset($_POST['post_id']) ? absint($_POST['post_id']) : 0)) {
        wp_send_json_error(['message' => __('Permission denied.', 'ai-cat-content-gen-google')], 403);
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $image_prompt = isset($_POST['image_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['image_prompt'])) : '';

    if ($post_id <= 0 || !get_post($post_id)) {
        wp_send_json_error(['message' => __('Invalid post specified.', 'ai-cat-content-gen-google')], 400);
    }
    if (empty($image_prompt)) {
        wp_send_json_error(['message' => __('Image prompt cannot be empty.', 'ai-cat-content-gen-google')], 400);
    }

    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $venice_api_key = isset($options['venice_api_key']) ? trim($options['venice_api_key']) : '';

    if (empty($venice_api_key)) {
        wp_send_json_error(['message' => __('Venice AI API Key missing in plugin settings.', 'ai-cat-content-gen-google')], 400);
    }

    $generated_images = [];
    $generation_attempts = 3; // Generate 3 options

    for ($i = 0; $i < $generation_attempts; $i++) {
        $image_result = aiccgen_google_generate_venice_image($venice_api_key, $image_prompt);
        if ($image_result['success'] && isset($image_result['attachment_id'])) {
            $image_url = wp_get_attachment_image_url($image_result['attachment_id'], 'medium');
            if ($image_url) {
                $generated_images[] = [
                    'attachment_id' => $image_result['attachment_id'],
                    'image_url'     => $image_url,
                ];
            } else {
                my_plugin_log("[AI Cat Gen Post Refine] Failed to get URL for attachment ID: " . $image_result['attachment_id']);
            }
        } else {
            my_plugin_log("[AI Cat Gen Post Refine] Image generation attempt " . ($i + 1) . " failed: " . ($image_result['error'] ?? 'Unknown error'));
        }
    }

    if (empty($generated_images)) {
        wp_send_json_error(['message' => __('Failed to generate any image options. Check API key or prompt. See server logs for details.', 'ai-cat-content-gen-google')], 500);
    }

    wp_send_json_success([
        'generated_images' => $generated_images,
        'message' => sprintf(_n('%d image option generated.', '%d image options generated.', count($generated_images), 'ai-cat-content-gen-google'), count($generated_images))
    ]);
}
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_POST_REFINE_IMAGE_ACTION, 'aiccgen_google_ajax_post_generate_image_options');

// Refine Meta box featured images select any image 
function aiccgen_google_ajax_post_apply_selected_image() {
    check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Permission denied.', 'ai-cat-content-gen-google')], 403);
    }

    $selected_image_id = isset($_POST['selected_image_id']) ? absint($_POST['selected_image_id']) : 0;
    $all_new_image_ids = isset($_POST['all_new_image_ids']) && is_array($_POST['all_new_image_ids'])
                        ? array_map('absint', $_POST['all_new_image_ids'])
                        : [];

    if ($post_id <= 0 || !get_post($post_id)) {
        wp_send_json_error(['message' => __('Invalid post specified.', 'ai-cat-content-gen-google')], 400);
    }
    if ($selected_image_id <= 0 || !wp_get_attachment_url($selected_image_id)) {
        wp_send_json_error(['message' => __('Invalid image selected.', 'ai-cat-content-gen-google')], 400);
    }
    if (empty($all_new_image_ids) || !in_array($selected_image_id, $all_new_image_ids)) {}

    // Set the new featured image
    $set_thumb_result = set_post_thumbnail($post_id, $selected_image_id);

    // If it was already the thumbnail, it returns true (WP 5.5+).
    if (!$set_thumb_result && !has_post_thumbnail($post_id)) {
        $current_thumb_id = get_post_thumbnail_id($post_id);
        if ($current_thumb_id != $selected_image_id) {
             wp_send_json_error(['message' => __('Failed to set the new featured image.', 'ai-cat-content-gen-google')], 500);
        }
    }

    // Delete unselected newly generated images
    $deleted_count = 0;
    if (!empty($all_new_image_ids)) {
        foreach ($all_new_image_ids as $img_id) {
            if ($img_id !== $selected_image_id) {
                if (get_post_type($img_id) === 'attachment') {
                    if (wp_delete_attachment($img_id, true)) {
                        $deleted_count++;
                    } else {
                        my_plugin_log("[AI Cat Gen Post Apply Image] Failed to delete unselected image ID: " . $img_id . " for post " . $post_id);
                    }
                }
            }
        }
    }
    wp_send_json_success([
        'new_thumbnail_id' => $selected_image_id,
        'new_thumbnail_html' => _wp_post_thumbnail_html($selected_image_id, $post_id) // new HTML for the meta box
    ]);
}
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_POST_APPLY_IMAGE_ACTION, 'aiccgen_google_ajax_post_apply_selected_image');

// function to convert ai_suggestion to standard post this function will be triggered when the ai_suggestion post is published.
function aiccgen_google_convert_ai_suggestion_to_post_on_publish($post_ID, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the post type.
    if ('ai_suggestion' !== $post->post_type) {
        return;
    }

    // Check if this post has already been converted to prevent recursion or reprocessing.
    if (get_post_meta($post_ID, '_aiccgen_converted_to_post', true)) {
        return;
    }

    // Gather data from the ai_suggestion post.
    $ai_suggestion_title    = $post->post_title;
    $ai_suggestion_content  = $post->post_content;
    $ai_suggestion_author   = $post->post_author;
    $ai_suggestion_excerpt  = $post->post_excerpt;

    // Get categories (term IDs).
    $categories = wp_get_post_terms($post_ID, 'category', array('fields' => 'ids'));

    // Get featured image ID.
    $featured_image_id = get_post_thumbnail_id($post_ID);

    // Prepare data for the new standard post.
    $new_post_data = array(
        'post_title'    => $ai_suggestion_title,
        'post_content'  => $ai_suggestion_content,
        'post_status'   => 'publish',       // Publish the new post directly.
        'post_type'     => 'post',          // Standard post type.
        'post_author'   => $ai_suggestion_author,
        'post_category' => $categories,    // Assign categories.
        'post_excerpt'  => $ai_suggestion_excerpt,
    );

    // Insert the new standard post.
    $new_post_id = wp_insert_post($new_post_data, true); // true for WP_Error on failure.

    if (is_wp_error($new_post_id)) {
        // Log error or handle it.
        my_plugin_log('[AI Cat Content Gen] Failed to create standard post from AI Suggestion ID ' . $post_ID . ': ' . $new_post_id->get_error_message());
        return; // Stop processing if new post creation failed.
    }

    // Set featured image for the new post if one existed.
    if ($featured_image_id && $new_post_id) {
        set_post_thumbnail($new_post_id, $featured_image_id);
    }

    if ($new_post_id) {
        update_post_meta($new_post_id, '_aiccgen_is_ai_generated_post', true); // Our new marker
        update_post_meta($new_post_id, '_aiccgen_original_suggestion_author', $ai_suggestion_author); // Optional: store original author if needed
    }

    // Mark the original ai_suggestion as converted.
    update_post_meta($post_ID, '_aiccgen_converted_to_post', true);

    // Delete the original ai_suggestion post (force delete, bypass trash).    
    wp_delete_post($post_ID, true);

    // Store the new post ID in a transient to signal a redirect.
    if ($new_post_id) {
        set_transient('aiccgen_redirect_new_post_' . $post_ID, $new_post_id, 60); // Expire in 60 seconds.
    }
}
// Hook with priority 10, accepts 2 arguments.
add_action('publish_ai_suggestion', 'aiccgen_google_convert_ai_suggestion_to_post_on_publish', 10, 2);

// Add a meta box to the post editor for re-editing AI-generated posts
function aiccgen_google_add_reedit_button_meta_box() {
    add_meta_box(
        'aiccgen_reedit_ai_box',
        __('Re-Edit Post', 'ai-cat-content-gen-google'),
        'aiccgen_google_render_reedit_button_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'aiccgen_google_add_reedit_button_meta_box');

// Render the "Re-edit Post" button meta box
function aiccgen_google_render_reedit_button_meta_box($post) {
    // Only show for 'post' type
    if ($post->post_type !== 'post') {
        return;
    }

    // Check if this post originated from an AI Suggestion
    $is_ai_generated = get_post_meta($post->ID, '_aiccgen_is_ai_generated_post', true);
    if (!$is_ai_generated) {
        echo '<p style="color:#888;">' . esc_html__('This post was not created by AI Suggestions.', 'ai-cat-content-gen-google') . '</p>';
        return;
    }

    // Ensure the user can edit this post and create ai_suggestions
    if (!current_user_can('edit_post', $post->ID) || !current_user_can('publish_posts')) {
        echo '<p style="color:#888;">' . esc_html__('You do not have permission to re-edit this post.', 'ai-cat-content-gen-google') . '</p>';
        return;
    }

    // Create a nonce for security
    $nonce = wp_create_nonce('aiccgen_reedit_post_nonce_' . $post->ID); ?>
    <p class="description">
        <?php esc_html_e('Moves this post back to "Needs your Review" for further editing and refinement.', 'ai-cat-content-gen-google'); ?>
    </p>
    <div style="display: flex; align-items: center; margin-top: 10px;">
        <button type="button" id="aiccgen-reedit-button" class="button button-postmetabx"
                data-postid="<?php echo esc_attr($post->ID); ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>">
            <?php esc_html_e('Re-edit Now', 'ai-cat-content-gen-google'); ?>
        </button>
        <span id="aiccgen-reedit-spinner" style="float:none; margin-left: 10px; display:none;">
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'img/loading.gif'); ?>" alt="Loading..." style="width:16px;height:16px;">
        </span>
    </div>
    <div id="aiccgen-reedit-message" style="text-align:center; margin-top:5px;"></div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aiccgen-reedit-button').on('click', function() {
                if (!confirm('<?php echo esc_js(__('Are you sure you want to move this post back to Needs your Review for re-editing?', 'ai-cat-content-gen-google')); ?>')) {
                    return;
                }

                var postId = $(this).data('postid');
                var nonce = $(this).data('nonce');
                var $button = $(this);
                var $spinner = $('#aiccgen-reedit-spinner');
                var $message = $('#aiccgen-reedit-message');

                $button.prop('disabled', true);
                $spinner.show();
                $message.empty().removeClass('notice-success notice-error');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aiccgen_google_move_to_ai_suggestion',
                        post_id: postId,
                        _ajax_nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.addClass('notice-success').html('<div class="notice inline notice-success">' + response.data.message + '</div>');
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            $message.addClass('notice-error').html('<div class="description notice notice-error inline">' + response.data.message + '</div>');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        $message.addClass('notice-error').html('<div class="notice notice-error inline"><?php echo esc_js(__('An AJAX error occurred: ', 'ai-cat-content-gen-google')); ?>' + error + '</div>');
                        $button.prop('disabled', false);
                    },
                    complete: function() {
                        $spinner.hide();
                    }
                });
            });
        });
    </script>
    <?php
}

// Redirect to the new post after conversion
function aiccgen_google_redirect_after_conversion($location, $post_id_original_ai_suggestion) {
    $new_post_id = get_transient('aiccgen_redirect_new_post_' . $post_id_original_ai_suggestion);
    if ($new_post_id) {
        delete_transient('aiccgen_redirect_new_post_' . $post_id_original_ai_suggestion);
        $new_post_edit_link = get_edit_post_link($new_post_id, 'raw');
        if ($new_post_edit_link) {
            return $new_post_edit_link;
        }
    }
    return $location;
}
add_filter('redirect_post_location', 'aiccgen_google_redirect_after_conversion', 99, 2);

// Remove specific separator from WordAI API response
function aiccgen_wordai_strip_original_from_output( $api_response_string, $original_input_text ) {
    if ( empty( $api_response_string ) || empty( $original_input_text ) ) {
        return $api_response_string;
    }

    $stripped_text = $api_response_string;
    $plugin_version_for_log = 'AICCG-WordAI-Integration';

    // Common separators that might follow the original text
    $separators = [
        '. ', PHP_EOL, '.', ' / ', '/', ' - ', '-', ': ', ':', ' '
    ];

    $found_and_stripped = false;

    foreach ( $separators as $sep ) {
        // Normalize original input for comparison (e.g. remove trailing period if separator also has it)
        $normalized_original_input = rtrim($original_input_text, '. '); 
        $prefix_to_check = $normalized_original_input . $sep;
        
        if ( stripos( $stripped_text, $prefix_to_check ) === 0 ) { // Case-insensitive check for prefix
            $stripped_text = trim( substr( $stripped_text, strlen( $prefix_to_check ) ) );
            if (function_exists('error_log')) {
                my_plugin_log(sprintf('[%s] Stripped original input using separator: "%s" (in aiccgen_wordai_strip_original_from_output)', $plugin_version_for_log, $sep));
            }
            $found_and_stripped = true;
            break;
        }
    }

    if ( ! $found_and_stripped && stripos( $stripped_text, $original_input_text ) === 0 ) { // Case-insensitive
        $char_after_original = '';
        if (strlen($stripped_text) > strlen($original_input_text)) {
            $char_after_original = substr($stripped_text, strlen($original_input_text), 1);
        }

        if ($char_after_original === '' || !ctype_alnum($char_after_original)) {
            $potential_stripped = trim( substr( $stripped_text, strlen( $original_input_text ) ) );
            if (!empty($potential_stripped)) {
                $stripped_text = $potential_stripped;
                if (function_exists('error_log')) {
                    my_plugin_log(sprintf('[%s] Stripped original input directly (no specific separator found, or char after was non-alphanumeric) (in aiccgen_wordai_strip_original_from_output).', $plugin_version_for_log));
                }
            } else {
                 if (function_exists('error_log')) {
                    my_plugin_log(sprintf('[%s] Attempted direct strip, but result was empty or original was identical to output. Kept as is (in aiccgen_wordai_strip_original_from_output).', $plugin_version_for_log));
                }
            }
        } else {
             if (function_exists('error_log')) {
                my_plugin_log(sprintf('[%s] Original input is a prefix, but followed by alphanumeric char. Not stripping directly (in aiccgen_wordai_strip_original_from_output).', $plugin_version_for_log));
            }
        }
    }
    return $stripped_text;
}

// Helper function to call WordAI API
function aiccgen_call_wordai_api($text_to_rewrite, $email, $api_key) {
    if (empty($text_to_rewrite) || empty($email) || empty($api_key)) {
        return ['success' => false, 'rewritten_text' => '', 'error' => __('Missing text, email, or API key for WordAI.', 'ai-cat-content-gen-google')];
    }

   


    $plugin_version_for_log = 'AICCG-WordAI-Integration-v2'; // Increment version for logs

    // Clean the input for WordAI: remove ```html and ```
    // This is important because WordAI might interpret these as literal text to be rewritten.
    $clean_text_for_wordai = $text_to_rewrite;
    $clean_text_for_wordai = preg_replace('/^\s*```html\s*/i', '', $clean_text_for_wordai);
    $clean_text_for_wordai = preg_replace('/\s*```\s*$/i', '', $clean_text_for_wordai);
    $clean_text_for_wordai = trim($clean_text_for_wordai);

    // If cleaning makes the input effectively empty, no need to call WordAI.
    if (empty(trim(strip_tags($clean_text_for_wordai)))) { // Check if empty after stripping tags too
        if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI input was effectively empty after cleaning and stripping tags. Original: %s', $plugin_version_for_log, substr($text_to_rewrite,0,200))); }
        // Return the original, cleaned text as WordAI didn't process it.
        return ['success' => true, 'rewritten_text' => $clean_text_for_wordai, 'error' => null, 'skipped_wordai' => true]; 
    }

    $api_params = [
        'email'           => $email,
        'key'             => $api_key,
        'input'           => $clean_text_for_wordai,
        'rewrite_num'     => 1,
        'uniqueness'      => 3, 
        'return_rewrites' => true, 
        // 'html' => true, // WordAI has/had an 'html' parameter. Check current API docs.
                          // If it exists, setting it to true might improve HTML handling.
                          // However, often it just means WordAI won't try to "fix" your HTML.
    ];

    //if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI API Request Params: ', $plugin_version_for_log) . print_r( $api_params, true ) ); }

    $response = wp_remote_post( 'https://wai.wordai.com/api/rewrite', [ 'method' => 'POST', 'timeout' => 8 * 60, 'body' => $api_params ]);

    if ( is_wp_error( $response ) ) {
        // ... (error handling as before) ...
        $error_message = $response->get_error_message();
        if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI WP_Error: ', $plugin_version_for_log) . $error_message ); }
        return ['success' => false, 'rewritten_text' => '', 'error' => $error_message];
    }

    $body = wp_remote_retrieve_body( $response );
    $response_code = wp_remote_retrieve_response_code( $response );
    //if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] Raw WordAI API Response (Code: %s): %s', $plugin_version_for_log, $response_code, substr($body, 0, 1000) . (strlen($body) > 1000 ? '...' : '')) ); }

    $data = json_decode( $body );
    $rewritten_text = ''; // Initialize

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        // ... (JSON error handling as before) ...
        $json_error_msg = json_last_error_msg();
        if (strpos(strtolower($body), 'error') !== false || $response_code >= 400) {
             if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI API returned non-JSON error string or error code. Body: %s', $plugin_version_for_log, $body)); }
             return ['success' => false, 'rewritten_text' => '', 'error' => wp_strip_all_tags($body)];
        }
        if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI Failed to parse API response (JSON Error): %s. Raw response was: %s', $plugin_version_for_log, $json_error_msg, substr( $body, 0, 500 ) . '...')); }
        return ['success' => false, 'rewritten_text' => '', 'error' => __( 'Failed to parse WordAI API response (JSON Error): ', 'ai-cat-content-gen-google' ) . $json_error_msg];
    }

    if ( isset( $data->status ) && $data->status === 'Success' ) {
        $candidate_text = '';
        // Since uniqueness >= 3 and return_rewrites = true, 'text' field should have spintax.
        // 'rewrites' array is typically for uniqueness < 3.
        if ( isset( $data->text ) && !empty(trim($data->text)) ) {
            //if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI using data->text as candidate for spintax.', $plugin_version_for_log)); }
            $candidate_text = $data->text;
        } elseif (isset($data->rewrites) && is_array($data->rewrites) && !empty($data->rewrites[0])) {
             // Fallback if 'text' is empty but 'rewrites' has something (less likely for uniqueness 3)
            if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI data->text was empty/missing. Using data->rewrites[0] as candidate (less likely for uniqueness 3).', $plugin_version_for_log)); }
            $candidate_text = $data->rewrites[0]; // This would be non-spintax
        } else {
            if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI API success, but no "text" or "rewrites[0]" field found or both empty. Body: %s', $plugin_version_for_log, $body )); }
            return ['success' => false, 'rewritten_text' => '', 'error' => __( 'WordAI API success, but no usable text field found.', 'ai-cat-content-gen-google' )];
        }

        if ( ! empty( $candidate_text ) ) {
            //if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] Candidate text from WordAI API (before parsing): %s', $plugin_version_for_log, substr($candidate_text, 0, 500) . (strlen($candidate_text) > 500 ? '...' : ''))); }
            
            $rewritten_text = $candidate_text; // Start with the full candidate text
            $parsed_successfully = false;

            // Check for spintax presence: { | }
            $has_curly_open = strpos($rewritten_text, '{');
            $has_pipe = strpos($rewritten_text, '|');
            $has_curly_close = strpos($rewritten_text, '}');

            if ($has_curly_open !== false && $has_pipe !== false && $has_curly_close !== false && $has_curly_open < $has_pipe && $has_pipe < $has_curly_close) {
                //if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI detected spintax format. Attempting to replace spintax blocks.', $plugin_version_for_log)); }
                
                // Iteratively replace spintax blocks: {original|rewritten} -> rewritten
                // This regex captures the content after the pipe within the outermost matching braces.
                // It's a common approach for simple, non-nested spintax.
                // (?R) would be for recursive/nested, but let's keep it simpler first.
                $temp_text = $rewritten_text;
                while (preg_match('~\{([^|]*)\|([^}]*)\}~s', $temp_text, $match)) {
                    $original_block = $match[0]; // Full {original|rewritten}
                    $rewritten_part = trim($match[2]); // Content after pipe
                    
                    // Replace only the first occurrence in this iteration to handle sequential blocks
                    $pos = strpos($temp_text, $original_block);
                    if ($pos !== false) {
                        $temp_text = substr_replace($temp_text, $rewritten_part, $pos, strlen($original_block));
                        $parsed_successfully = true; // Mark as parsed if at least one replacement happens
                    } else {
                        // Should not happen if preg_match found something
                        break; 
                    }
                }
                if ($parsed_successfully) {
                    $rewritten_text = $temp_text;
                    //  if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI successfully processed spintax by replacing blocks. Result preview: %s', $plugin_version_for_log, substr($rewritten_text,0,500).'...')); }
                } else {
                     if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI spintax markers detected, but replacements did not occur or regex failed to find matches iteratively.', $plugin_version_for_log)); }
                     // If parsing failed, $rewritten_text remains $candidate_text which might still have spintax.
                     // The fallback stripping might then be applied to this spintaxed string, which is not ideal.
                     // For now, if spintax is detected but parsing seems to fail, we might want to NOT strip.
                }
            } else {
                 if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI spintax markers not detected or not in expected order. Output is likely non-spintax or already a single rewrite.', $plugin_version_for_log)); }
                 // If no spintax, $rewritten_text is already the $candidate_text.
                 // We might still want to strip the original if it's a non-spintax response where WordAI prepends.
                 $parsed_successfully = false; // Ensure stripping logic runs if no spintax
            }

            // Fallback: If spintax parsing was NOT successful OR if there was no spintax to begin with,
            // try stripping the original input if WordAI prepended it (common for non-spintax single rewrites).
            // Only do this if $rewritten_text is not empty after potential spintax processing.
            if (!$parsed_successfully && !empty(trim($rewritten_text))) {
                if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] Applying original input stripping logic to: %s', $plugin_version_for_log, substr($rewritten_text,0,200))); }
                if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] Original input for stripping: %s', $plugin_version_for_log, substr($clean_text_for_wordai,0,200))); }
                
                $text_after_stripping = aiccgen_wordai_strip_original_from_output( $rewritten_text, $clean_text_for_wordai );
                
                // Only update if stripping actually changed something and didn't make it empty
                if ($text_after_stripping !== $rewritten_text && !empty(trim($text_after_stripping))) {
                    $rewritten_text = $text_after_stripping;
                    if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] Original input stripping applied. Result preview: %s', $plugin_version_for_log, substr($rewritten_text,0,200).'...')); }
                } else {
                    if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] Original input stripping did not change the text or resulted in empty. Kept as is.', $plugin_version_for_log)); }
                }
            }
            
            // Final cleanup: Remove any ```html or ``` that WordAI might have added or that remained.
            $rewritten_text = preg_replace('/^\s*```html\s*/i', '', $rewritten_text);
            $rewritten_text = preg_replace('/\s*```\s*$/i', '', $rewritten_text);
            $rewritten_text = trim($rewritten_text);

        } else { // candidate_text was empty from API
            if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI API success, but candidate text was empty.', $plugin_version_for_log)); }
             return ['success' => false, 'rewritten_text' => '', 'error' => __( 'WordAI API returned success but candidate text for rewrite was empty.', 'ai-cat-content-gen-google' )];
        }
        
        if (empty(trim(strip_tags($rewritten_text))) && !empty(trim(strip_tags($clean_text_for_wordai)))) {
             if (function_exists('error_log')) { my_plugin_log(sprintf('[%s] WordAI processing resulted in effectively empty text. Original input had content. Returning original cleaned input. Rewritten: %s', $plugin_version_for_log, substr($rewritten_text,0,200))); }
             return ['success' => true, 'rewritten_text' => $clean_text_for_wordai, 'error' => null, 'wordai_result_empty' => true];
        }

        //if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] Final WordAI $rewritten_text length: %d. Preview: %s', $plugin_version_for_log, strlen($rewritten_text), substr($rewritten_text, 0, 500) . (strlen($rewritten_text) > 500 ? '...' : '') )); }
        return ['success' => true, 'rewritten_text' => $rewritten_text, 'error' => null];

    } elseif ( isset( $data->error ) ) {
        // ... (error handling) ...
        if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI API Error: %s', $plugin_version_for_log, $data->error )); }
        return ['success' => false, 'rewritten_text' => '', 'error' => sprintf( __( 'WordAI API Error: %s', 'ai-cat-content-gen-google' ), esc_html( $data->error ) )];
    } else {
        // ... (fallback error handling) ...
        if (is_string($body) && (stripos($body, 'error') !== false || stripos($body, 'fail') !== false || $response_code >= 400 )) {
            if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] WordAI API returned non-JSON error or unexpected structure. Response: %s', $plugin_version_for_log, $body)); }
            return ['success' => false, 'rewritten_text' => '', 'error' => wp_strip_all_tags($body)];
        }
        if (function_exists('error_log')) { my_plugin_log( sprintf('[%s] Unknown WordAI API response format or error. Body: %s', $plugin_version_for_log, $body )); }
        return ['success' => false, 'rewritten_text' => '', 'error' => __( 'Unknown WordAI API response format or error.', 'ai-cat-content-gen-google' )];
    }
}


// Manual Post Generation
function aiccgen_google_ajax_manual_generate() {
    check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Permission denied.', 'ai-cat-content-gen-google')], 403);
    }

    $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
    // Get the manual image prompt
    $manual_image_prompt = isset($_POST['image_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['image_prompt'])) : '';

    if (!$category_id) {
        wp_send_json_error(['message' => __('No category selected.', 'ai-cat-content-gen-google')], 400);
    }

    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $google_api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
    $venice_api_key = isset($options['venice_api_key']) ? trim($options['venice_api_key']) : ''; // Get Venice key
    $model = isset($options['model']) ? $options['model'] : 'gemini-2.5-flash';
    $prompts = isset($options['prompts']) ? $options['prompts'] : [];

    // Get global and category-specific formatting instructions for manual generation
    $global_formatting_instructions = isset($options['global_formatting_instructions']) ? trim($options['global_formatting_instructions']) : '';
    $category_specific_formatting_instructions_map = isset($options['formatting_instructions']) ? $options['formatting_instructions'] : [];

    if (empty($google_api_key)) {
        wp_send_json_error(['message' => __('Google AI API Key missing in settings.', 'ai-cat-content-gen-google')], 400);
    }

    $user_entered_prompt = isset($prompts[$category_id]) ? trim($prompts[$category_id]) : '';
    if (empty($user_entered_prompt)) {
         wp_send_json_error(['message' => __('No content prompt saved for this category in settings. Cannot manual generation.', 'ai-cat-content-gen-google')], 400);
    }

    $category = get_category($category_id);
    if (!$category) {
        wp_send_json_error(['message' => __('Category not found.', 'ai-cat-content-gen-google')], 404);
    }

    // Determine effective formatting instructions for manual generation
    $category_formatting_text = isset($category_specific_formatting_instructions_map[$category_id]) ? trim($category_specific_formatting_instructions_map[$category_id]) : '';
    $effective_formatting_instructions = !empty($category_formatting_text) ? $category_formatting_text : $global_formatting_instructions;

    // --- Generate Content ---
    // Pass the $effective_formatting_instructions to the prompt builder
    $final_prompt = aiccgen_google_build_api_prompt($category->name, $user_entered_prompt, $effective_formatting_instructions); // <<< FIXED
    $content_response = aiccgen_google_call_gemini_api($google_api_key, $model, $final_prompt);

    // Initialize response data structure
    $response_data = [
        'category_name' => $category->name,
        'category_id'   => $category_id,
        'content'       => null,
        'content_error' => null,
        'image_attachment_id' => null,
        'image_url'     => null,
        'image_error'   => null,
    ];

    if (!$content_response['success']) {
        // If content fails, we don't proceed to image, send error immediately
        wp_send_json_error(['message' => $content_response['error']], $content_response['code']);
        return; // Exit
    }

    $response_data['content'] = $content_response['content'];

    // --- Generate Image (Optional) ---
    $image_prompt_trimmed = trim($manual_image_prompt);
    if (!empty($image_prompt_trimmed)) {
        if (!empty($venice_api_key)) {
            $image_result = aiccgen_google_generate_venice_image($venice_api_key, $image_prompt_trimmed);
            if ($image_result['success'] && isset($image_result['attachment_id'])) {
                $response_data['image_attachment_id'] = $image_result['attachment_id'];
                $response_data['image_url'] = wp_get_attachment_url($image_result['attachment_id']); // Get URL for preview
                 if (!$response_data['image_url']) { // Fallback if URL fetch fails
                     $response_data['image_error'] = __('Image generated but failed to retrieve URL.', 'ai-cat-content-gen-google');
                     wp_delete_attachment($response_data['image_attachment_id'], true); // Clean up if URL fails
                     $response_data['image_attachment_id'] = null;
                 }
            } else {
                $response_data['image_error'] = $image_result['error'] ?? __('Unknown image generation error.', 'ai-cat-content-gen-google');
            }
        } else {
            // Venice key is missing, set specific error message
            $response_data['image_error'] = __('Venice AI API key missing in settings.', 'ai-cat-content-gen-google');
        }
    } // No 'else' needed, if prompt is empty, image fields remain null

    // Send combined success response
    wp_send_json_success($response_data);

}

remove_action('wp_ajax_' . AICCG_GOOGLE_AJAX_ACTION, 'aiccgen_google_ajax_generate_content');
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_ACTION, 'aiccgen_google_ajax_manual_generate'); 

// Manual Test - Content Only AJAX Refine Content
function aiccgen_google_ajax_refine_content() {
     check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');
     if (!current_user_can('edit_posts')) {
         wp_send_json_error(['message' => __('Permission denied.', 'ai-cat-content-gen-google')], 403);
     }

     $original_content = isset($_POST['original_content']) ? wp_kses_post(wp_unslash($_POST['original_content'])) : '';
     $refinement_prompt_text = isset($_POST['refinement_prompt']) ? sanitize_textarea_field($_POST['refinement_prompt']) : '';
     // Retrieve category ID from POST, maybe needed if create post uses it? Let's pass it back.
     $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;

     if (empty($original_content) || empty($refinement_prompt_text)) {
         wp_send_json_error(['message' => __('Missing original content or refinement instruction.', 'ai-cat-content-gen-google')], 400);
     }

     $options = get_option(AICCG_GOOGLE_OPTION_NAME);
     $google_api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
     $model = isset($options['model']) ? $options['model'] : 'gemini-2.5-flash';

     if (empty($google_api_key)) {
         wp_send_json_error(['message' => __('Google AI API Key missing.', 'ai-cat-content-gen-google')], 400);
     }

     $final_refinement_prompt = aiccgen_google_build_refinement_prompt($original_content, $refinement_prompt_text);
     $response_data = aiccgen_google_call_gemini_api($google_api_key, $model, $final_refinement_prompt);

     if ($response_data['success']) {
          wp_send_json_success([
              'content' => $response_data['content'],
              'category_id' => $category_id // Pass category ID back for consistency
              ]);
     } else {
         wp_send_json_error(['message' => $response_data['error']], $response_data['code']);
     }
}
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_REFINE_ACTION, 'aiccgen_google_ajax_refine_content');

// AJAX Create Post (Manual Test - NO IMAGE) Manual Process
// AJAX Create Post (Manual Test - WITH Optional Image) Manual Process
function aiccgen_google_ajax_create_post() {
    check_ajax_referer(AICCG_GOOGLE_NONCE_ACTION, '_ajax_nonce');
    if (!current_user_can('publish_posts')) {
        wp_send_json_error(['message' => __('You do not have permission to create posts.', 'ai-cat-content-gen-google')], 403);
    }

    $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
    $post_title = isset($_POST['post_title']) ? sanitize_text_field(wp_unslash($_POST['post_title'])) : '';
    $post_content = isset($_POST['post_content']) ? wp_kses_post(wp_unslash($_POST['post_content'])) : '';
    // Get the image attachment ID (sanitize as int or null)
    $image_attachment_id = isset($_POST['image_attachment_id']) ? absint($_POST['image_attachment_id']) : null;
    if ($image_attachment_id === 0) { $image_attachment_id = null; } // Ensure 0 becomes null


    if (empty($post_title) || empty($post_content) || $category_id <= 0 || !term_exists($category_id, 'category')) {
         wp_send_json_error(['message' => __('Invalid input for post creation.', 'ai-cat-content-gen-google')], 400);
         return;
    }

     $plugin_options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $wordai_api_key = isset($plugin_options['wordai_api_key']) ? trim($plugin_options['wordai_api_key']) : '';
    $wordai_email = isset($plugin_options['wordai_email']) ? trim($plugin_options['wordai_email']) : '';
    //$wordai_enable_manual = isset($plugin_options['wordai_enable_manual']) ? (bool) $plugin_options['wordai_enable_manual'] : false;
    
    $original_content_before_wordai = $post_content; // Keep a copy for message comparison
    $wordai_message_suffix = '.'; // Default suffix
    $wordai_meta_status = AICCG_WORDAI_STATUS_NOT_ATTEMPTED; // Default

    if (!empty($wordai_api_key) && !empty($wordai_email)) {
        if (!empty(trim($post_content))) {
            my_plugin_log("[AI Cat Gen Manual Create Post/Info] Attempting WordAI rewrite.");
            $wordai_response = aiccgen_call_wordai_api($post_content, $wordai_email, $wordai_api_key);

            if ($wordai_response['success']) {
                 if (!empty(trim($wordai_response['rewritten_text']))) {
                    if (isset($wordai_response['wordai_result_empty']) && $wordai_response['wordai_result_empty'] === true) {
                        $wordai_message_suffix = ' ' . __('(WordAI processing resulted in empty text, using original input.)', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                    } elseif (isset($wordai_response['skipped_wordai']) && $wordai_response['skipped_wordai'] === true) {
                        $wordai_message_suffix = ' ' . __('(WordAI skipped: input effectively empty after cleaning, using original input.)', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
                    }
                    else {
                        $post_content = $wordai_response['rewritten_text'];
                        $wordai_message_suffix = ' ' . __('(Content rewritten by WordAI).', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_SUCCESS;
                        my_plugin_log("[AI Cat Gen Manual Create Post/Info] WordAI rewrite successful.");
                    }
                } else { // Success but empty text
                    $wordai_message_suffix = ' ' . __('(WordAI returned empty rewrite, using original input.)', 'ai-cat-content-gen-google');
                    $wordai_meta_status = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                }
            } else { // API call failed
                $wordai_error_msg = isset($wordai_response['error']) ? $wordai_response['error'] : __('Unknown WordAI error.', 'ai-cat-content-gen-google');
                $wordai_message_suffix = ' ' . sprintf(__('(WordAI rewrite failed: %s. Using original content.)', 'ai-cat-content-gen-google'), esc_html($wordai_error_msg));
                $wordai_meta_status = AICCG_WORDAI_STATUS_API_ERROR;
                my_plugin_log("[AI Cat Gen Manual Create Post/Error] WordAI rewrite FAILED - " . $wordai_error_msg . ". Proceeding with content from textarea.");
            }
        } else { // Content from textarea was empty
            $wordai_message_suffix = ' ' . __('(WordAI rewrite skipped: Content from textarea was empty.)', 'ai-cat-content-gen-google');
            $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
            my_plugin_log("[AI Cat Gen Manual Create Post/Warning] WordAI rewrite skipped - Content from textarea was empty.");
        }
    } elseif (empty(trim($post_content))) {
         $wordai_message_suffix = ' ' . __('(WordAI rewrite skipped: Content from textarea was empty.)', 'ai-cat-content-gen-google');
         $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
    }
    else { // API key or email missing
        $wordai_message_suffix = ' ' . __('(WordAI rewrite skipped: API Key or Email missing in settings.)', 'ai-cat-content-gen-google');
        $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_CONFIG;
        my_plugin_log("[AI Cat Gen Manual Create Post/Warning] WordAI rewrite skipped - API Key or Email missing.");
    }

    // Create post WITH optional featured image ID
    $result = aiccgen_google_create_draft_post($category_id, $post_title, $post_content, $image_attachment_id); // Pass the image ID

    if ($result['success']) {
        if ($result['post_id']) {
            update_post_meta($result['post_id'], AICCG_GOOGLE_WORDAI_STATUS_META_KEY, $wordai_meta_status);
        }
         $edit_link = get_edit_post_link($result['post_id'], 'raw');
         $edit_link_html = sprintf(
             '<a href="%s" target="_blank">%s</a>',
             esc_url($edit_link),
             __('Edit Draft Post', 'ai-cat-content-gen-google')
         );
         $message = $image_attachment_id
            ? __('Manual Draft post with featured image created successfully.', 'ai-cat-content-gen-google')
            : __('Manual Draft post created successfully (Content Only).', 'ai-cat-content-gen-google');

         wp_send_json_success([
             'message' => $message,
             'post_id' => $result['post_id'],
             'edit_link_html' => $edit_link_html
         ]);
    } else {
        wp_send_json_error(['message' => $result['error']], 500);
    }
}
add_action('wp_ajax_' . AICCG_GOOGLE_AJAX_CREATE_POST_ACTION, 'aiccgen_google_ajax_create_post');

// Add custom column to AI Suggestion post type Listing
function aiccgen_google_manage_ai_suggestion_columns($columns) {
    $new_columns_to_add = [
        'aiccgen_ai_status'     => __('AI Status', 'ai-cat-content-gen-google'),
    ];

    $reference_keys = ['title', 'author'];
    $inserted_columns = []; // To avoid duplicate keys if logic errors
    $final_columns = [];
    $columns_inserted_flag = false;

    foreach ($columns as $key => $title) {
        $final_columns[$key] = $title;
        if (in_array($key, $reference_keys) && !$columns_inserted_flag) {
            foreach ($new_columns_to_add as $new_col_key => $new_col_title) {
                if (!isset($final_columns[$new_col_key])) { // Ensure not to overwrite if somehow already present
                    $final_columns[$new_col_key] = $new_col_title;
                }
            }
            $columns_inserted_flag = true;
        }
    }
    
    // Fallback: if no reference key was found, try to insert before 'date', or append.
    if (!$columns_inserted_flag) {
        if (isset($columns['date'])) {
            $date_offset = array_search('date', array_keys($columns));
            $final_columns = array_slice($columns, 0, $date_offset, true) +
                             $new_columns_to_add +
                             array_slice($columns, $date_offset, null, true);
        } else {
            // Append to the very end if 'date' also not found.
            $final_columns = array_merge($columns, $new_columns_to_add);
        }
    }

    return $final_columns;
}
add_filter('manage_ai_suggestion_posts_columns', 'aiccgen_google_manage_ai_suggestion_columns');

// Render the WordAI status column for AI Suggestion post type
function aiccgen_google_render_ai_suggestion_custom_columns($column_name, $post_id) {
    if ($column_name === 'aiccgen_ai_status') {
        $is_reedit = get_post_meta($post_id, AICCG_GOOGLE_IS_REEDIT_META_KEY, true);
        $display_text = '';
        $style = '';

        if ($is_reedit) {
            $display_text = __('Not Ready', 'ai-cat-content-gen-google');
            $style = 'color: #c10c0c; font-weight: bold;';
        } else {
            $display_text = __('AI Complete - Needs Review', 'ai-cat-content-gen-google');
            $style = 'color: #036503; font-weight: bold;';
        }
        echo '<span style="' . esc_attr($style) . '">' . esc_html($display_text) . '</span>';
    }
}
add_action('manage_ai_suggestion_posts_custom_column', 'aiccgen_google_render_ai_suggestion_custom_columns', 10, 2);

// --- Scheduling and Settings Update Handling ---
function aiccgen_google_handle_settings_update($old_value, $new_value) {

    // Increase execution time limit for potentially long operations (like scheduling many tasks)
    @set_time_limit(300); // 5 minutes

    $results = [
        'schedule_updates' => 0,
        'schedule_cleared' => 0,
        'details' => []
        // Removed success/fail counts as manual generation on save is removed
    ];

    $old_prompts = isset($old_value['prompts']) && is_array($old_value['prompts']) ? $old_value['prompts'] : [];
    $old_freqs = isset($old_value['frequency']) && is_array($old_value['frequency']) ? $old_value['frequency'] : [];

    $new_prompts = isset($new_value['prompts']) && is_array($new_value['prompts']) ? $new_value['prompts'] : [];
    $new_freqs = isset($new_value['frequency']) && is_array($new_value['frequency']) ? $new_value['frequency'] : [];
    // No need for refinements/image prompts here, only scheduling matters

    // Get all categories that might have changed state
    $all_category_ids = get_terms(['taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false]);
     if (is_wp_error($all_category_ids) || !is_array($all_category_ids)) {
         $all_category_ids = [];
     }
     // Include keys from old/new options just in case a category was deleted since last save
     $process_cat_ids = array_unique(array_merge(
         array_keys($old_prompts),
         array_keys($new_prompts),
         $all_category_ids
     ));


    foreach ($process_cat_ids as $cat_id) {
        $cat_id = absint($cat_id);
        if ($cat_id === 0) continue;

        $category = get_category($cat_id);
        // If category doesn't exist anymore, try to clear any lingering schedule
        if (!$category) {
            $args_for_cron = ['category_id' => $cat_id];
             $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args_for_cron);
            if ($timestamp) {
                wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK, $args_for_cron);
                $results['schedule_cleared']++;
                 $results['details'][] = ['type' => 'info', 'message' => sprintf(__('Cleared schedule for deleted/invalid category ID %d.', 'ai-cat-content-gen-google'), $cat_id)];
            }
            continue; // Skip further processing for this ID
        }
        $category_name = $category->name;

        // Check prompt existence in NEW value (determines active state)
        $new_prompt_exists = isset($new_prompts[$cat_id]) && !empty(trim($new_prompts[$cat_id]));

        // Get frequencies, default to 'none' if not set or no prompt
        $old_freq = isset($old_prompts[$cat_id]) && isset($old_freqs[$cat_id]) && !empty(trim($old_prompts[$cat_id])) ? $old_freqs[$cat_id] : 'none';
        $new_freq = $new_prompt_exists && isset($new_freqs[$cat_id]) ? $new_freqs[$cat_id] : 'none';

        // Treat 'manual' from old settings as 'none' for scheduling comparison
        if ($old_freq === 'manual') $old_freq = 'none';

        $args_for_cron = ['category_id' => $cat_id];

        // --- Schedule Management ---
        $is_currently_scheduled = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args_for_cron);
        $needs_scheduling = $new_prompt_exists && $new_freq !== 'none';
        // Needs clearing if:
        // 1. It was scheduled before (old_freq !== 'none') AND (it's no longer active OR frequency changed)
        // 2. Or if it's currently scheduled but shouldn't be (e.g., prompt removed)
        $needs_clearing = ($is_currently_scheduled && !$needs_scheduling) || ($is_currently_scheduled && $needs_scheduling && wp_get_schedule(AICCG_GOOGLE_CRON_HOOK, $args_for_cron) !== $new_freq);


        // 1. Clear schedule if needed
        if ($needs_clearing) {
            $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args_for_cron); // Get timestamp again just in case
            if ($timestamp) {
                $cleared = wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK, $args_for_cron);
                if ($cleared !== false) {
                    $results['schedule_cleared']++;
                    $results['details'][] = ['type' => 'info', 'message' => sprintf(__('Cleared previous schedule for category "%s".', 'ai-cat-content-gen-google'), esc_html($category_name))];
                    $is_currently_scheduled = false; // Update state after clearing
                } else {
                     $results['details'][] = ['type' => 'warning', 'message' => sprintf(__('Could not clear previous schedule for "%s". Check WP Cron.', 'ai-cat-content-gen-google'), esc_html($category_name))];
                }
            }
        }

        // 2. Schedule if needed AND not already scheduled with the correct frequency
        if ($needs_scheduling && !$is_currently_scheduled) {
            $first_run_time = time() + 60; // Schedule slightly in the future
            wp_schedule_event($first_run_time, $new_freq, AICCG_GOOGLE_CRON_HOOK, $args_for_cron);
            $verify_schedule_time = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args_for_cron); // Verify schedule
            if ($verify_schedule_time) {
                 $results['schedule_updates']++;
                 $results['details'][] = ['type' => 'success', 'message' => sprintf(__('Scheduled new task (%s) for category "%s".', 'ai-cat-content-gen-google'), esc_html($new_freq), esc_html($category_name))];
            } else {
                 $results['details'][] = ['type' => 'error', 'message' => sprintf(__('CRITICAL FAILURE: Could not schedule task (%s) for category "%s". Check WP Cron system/logs.', 'ai-cat-content-gen-google'), esc_html($new_freq), esc_html($category_name))];
            }
        } elseif ($needs_scheduling && $is_currently_scheduled) {
            // Already scheduled correctly, log for info? Maybe not necessary unless debugging.
             // $results['details'][] = ['type' => 'info', 'message' => sprintf(__('Schedule (%s) for category "%s" remains active.', 'ai-cat-content-gen-google'), esc_html($new_freq), esc_html($category_name))];
        }

    } // End foreach category loop

    // Save the results for display on the settings page
    set_transient(AICCG_GOOGLE_SETTINGS_NOTICE_TRANSIENT, $results, 60);
}
add_action('update_option_' . AICCG_GOOGLE_OPTION_NAME, 'aiccgen_google_handle_settings_update', 10, 2);


// --- Cron Setup Callback for posts---
function aiccgen_google_run_scheduled_generation($category_id) {
    //@ini_set('memory_limit', '512M'); // Also try increasing memory
    @set_time_limit(25 * 60); // 20 minutes for testing
    $category_id = absint($category_id);
    if ($category_id === 0) {
        my_plugin_log("[AI Cat Gen Cron] Error: Received invalid category ID 0 for scheduled generation.");
        return;
    }

    // --- Step 1: Initial Setup and Validation ---
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    if (!$options) {
        my_plugin_log("[AI Cat Gen Cron] Error: Plugin options (AICCG_GOOGLE_OPTION_NAME) not found for Cat ID {$category_id}.");
        wp_clear_scheduled_hook(AICCG_GOOGLE_CRON_HOOK, ['category_id' => $category_id]);
        return;
    }

    $google_api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
    $venice_api_key = isset($options['venice_api_key']) ? trim($options['venice_api_key']) : '';
    $model = isset($options['model']) && !empty(trim($options['model'])) ? $options['model'] : 'gemini-2.5-flash';
    $prompts = isset($options['prompts']) && is_array($options['prompts']) ? $options['prompts'] : [];
    $image_prompts = isset($options['image_prompts']) && is_array($options['image_prompts']) ? $options['image_prompts'] : [];
    $formatting_instructions_map = isset($options['formatting_instructions']) && is_array($options['formatting_instructions']) ? $options['formatting_instructions'] : [];
    $global_formatting_instructions = isset($options['global_formatting_instructions']) ? trim($options['global_formatting_instructions']) : '';
    $frequencies = isset($options['frequency']) && is_array($options['frequency']) ? $options['frequency'] : [];


    // Validate essential data for this category
    $category = get_category($category_id);
    $prompt_text_for_cat = isset($prompts[$category_id]) ? trim($prompts[$category_id]) : '';
    $frequency_for_cat = isset($frequencies[$category_id]) ? $frequencies[$category_id] : 'none';

    $is_valid_for_run = true;
    $validation_error_reason = '';

    if (empty($google_api_key)) { $is_valid_for_run = false; $validation_error_reason = 'Missing Google API Key in settings.'; }
    if (!$category) { $is_valid_for_run = false; $validation_error_reason = 'Category not found.'; }
    if (empty($prompt_text_for_cat)) { $is_valid_for_run = false; $validation_error_reason = 'Missing Content Prompt for this category.'; }
    if ($frequency_for_cat === 'none') { $is_valid_for_run = false; $validation_error_reason = 'Frequency set to None (should not be triggered by cron).';}


    if (!$is_valid_for_run) {
        my_plugin_log("[AI Cat Gen Cron] Error for Cat ID {$category_id}: {$validation_error_reason} - Unscheduling this task.");
        wp_clear_scheduled_hook(AICCG_GOOGLE_CRON_HOOK, ['category_id' => $category_id]);
        return;
    }

    $category_name = $category->name;
    $image_prompt_text_for_cat = isset($image_prompts[$category_id]) ? trim($image_prompts[$category_id]) : '';
    $category_specific_formatting = isset($formatting_instructions_map[$category_id]) ? trim($formatting_instructions_map[$category_id]) : '';
    $effective_formatting_instructions = !empty($category_specific_formatting) ? $category_specific_formatting : $global_formatting_instructions;

    // --- Step 2: Generate Google Content ---
    my_plugin_log("[AI Cat Gen Cron] Starting Google content generation for Cat ID {$category_id} ('{$category_name}').");
    $final_google_prompt = aiccgen_google_build_api_prompt($category_name, $prompt_text_for_cat, $effective_formatting_instructions);
    $generation_response = aiccgen_google_call_gemini_api($google_api_key, $model, $final_google_prompt);

    if (!$generation_response['success']) {
        $google_error = isset($generation_response['error']) ? esc_html($generation_response['error']) : 'Unknown Google API error.';
        my_plugin_log("[AI Cat Gen Cron] Google Content generation FAILED for Cat ID {$category_id}: {$google_error}");
        return;
    }
    $generated_content_from_google = $generation_response['content'];
    if (empty(trim($generated_content_from_google))) {
        my_plugin_log("[AI Cat Gen Cron] Google Content generation for Cat ID {$category_id} resulted in empty content. Aborting post creation.");
        return;
    }
    my_plugin_log("[AI Cat Gen Cron] Google content generated successfully for Cat ID {$category_id}. Length: " . strlen($generated_content_from_google));


    // --- Step 3: WordAI Processing with Locking ---
    $content_to_post = $generated_content_from_google; // Start with Google content
    $wordai_api_key = isset($options['wordai_api_key']) ? trim($options['wordai_api_key']) : '';
    $wordai_email = isset($options['wordai_email']) ? trim($options['wordai_email']) : '';
    $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_NOT_ATTEMPTED;
    $wordai_status_message_for_log = '';

    $should_attempt_wordai = !empty($wordai_api_key) && !empty($wordai_email) && !empty(trim($generated_content_from_google));
    
    if ($should_attempt_wordai) {
        my_plugin_log("[AI Cat Gen Cron] Attempting to acquire WordAI lock for Cat ID {$category_id}.");
        if (get_transient(AICCG_WORDAI_CRON_LOCK_NAME)) {
            // Lock is busy
            my_plugin_log("[AI Cat Gen Cron] WordAI lock for Cat ID {$category_id} is BUSY. Rescheduling this task.");
            $current_cron_args = ['category_id' => $category_id];
            $timestamp_of_current = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $current_cron_args);
            if ($timestamp_of_current) {
                wp_unschedule_event($timestamp_of_current, AICCG_GOOGLE_CRON_HOOK, $current_cron_args);
            }
            // ADD A SMALL RANDOM DELAY TO THE RESCHEDULE TIME
            $random_additional_delay = rand(0, 60); // Random 0 to 60 seconds
            wp_schedule_single_event(time() + AICCG_WORDAI_CRON_RETRY_DELAY + $random_additional_delay, AICCG_GOOGLE_CRON_HOOK, $current_cron_args);
            my_plugin_log("[AI Cat Gen Cron] Cat ID {$category_id} rescheduled to run in approx " . (AICCG_WORDAI_CRON_RETRY_DELAY + $random_additional_delay)/60 . " minutes due to busy lock.");
            return; // Exit current execution, will retry later
        }  else {
            // Acquire the lock
            set_transient(AICCG_WORDAI_CRON_LOCK_NAME, $category_id, AICCG_WORDAI_CRON_LOCK_DURATION);
            my_plugin_log("[AI Cat Gen Cron] WordAI lock ACQUIRED by Cat ID {$category_id}. Processing WordAI.");

            try {
                $wordai_response = aiccgen_call_wordai_api($generated_content_from_google, $wordai_email, $wordai_api_key);

                if ($wordai_response['success']) {
                    if (!empty(trim($wordai_response['rewritten_text']))) {
                        if (isset($wordai_response['wordai_result_empty']) && $wordai_response['wordai_result_empty'] === true) {
                            $wordai_status_message_for_log = 'WordAI processing resulted in empty text; using original Google content.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                        } elseif (isset($wordai_response['skipped_wordai']) && $wordai_response['skipped_wordai'] === true) {
                            $wordai_status_message_for_log = 'WordAI skipped as input was effectively empty after cleaning; using original Google content.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
                        } else {
                            $content_to_post = $wordai_response['rewritten_text']; // Update content
                            $wordai_status_message_for_log = 'Content successfully rewritten by WordAI.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SUCCESS;
                        }
                    } else { // WordAI success, but rewritten_text is empty
                        $wordai_status_message_for_log = 'WordAI returned an empty rewrite; using original Google content.';
                        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                    }
                } else { // WordAI call failed
                    $wordai_api_error = isset($wordai_response['error']) ? esc_html($wordai_response['error']) : 'Unknown WordAI API error.';
                    $wordai_status_message_for_log = "WordAI rewrite failed: {$wordai_api_error}. Using original Google content.";
                    $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_API_ERROR;
                    my_plugin_log("[AI Cat Gen Cron/Error] WordAI rewrite FAILED for Cat ID {$category_id} - {$wordai_api_error}");
                }
            } catch (Exception $e) {
                my_plugin_log("[AI Cat Gen Cron/Exception] Exception during WordAI processing for Cat ID {$category_id}: " . $e->getMessage());
                $wordai_status_message_for_log = 'Exception during WordAI processing. Using original Google content.';
                $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_API_ERROR;
            } finally {
                my_plugin_log("[AI Cat Gen Cron] ENTERING finally block for WordAI lock release - Cat ID {$category_id}.");
                delete_transient(AICCG_WORDAI_CRON_LOCK_NAME);
                my_plugin_log("[AI Cat Gen Cron] WordAI lock RELEASED by Cat ID {$category_id} (from finally block). WordAI Status: {$wordai_status_message_for_log}");
            }
        }
    } elseif (empty(trim($generated_content_from_google))) {
        // This case should ideally be caught earlier, but as a fallback:
        $wordai_status_message_for_log = 'WordAI rewrite skipped: Original Google content was empty.';
        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
    } else { // WordAI API key/email missing
        $wordai_status_message_for_log = 'WordAI rewrite skipped: API Key or Email missing in settings.';
        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_CONFIG;
    }
    // Log the final WordAI status for this run if it was attempted or skipped for a reason
    if ($wordai_meta_status_for_post !== AICCG_WORDAI_STATUS_NOT_ATTEMPTED) {
         my_plugin_log("[AI Cat Gen Cron] WordAI processing for Cat ID {$category_id} - Result: {$wordai_status_message_for_log}");
    }

    // --- Step 4: Image Generation (Optional) ---
    $generated_image_id_for_post = null;
    $image_generation_status_log_msg = 'Not attempted or no prompt.';

    if (!empty($image_prompt_text_for_cat)) {
        if (!empty($venice_api_key)) {
            my_plugin_log("[AI Cat Gen Cron] Starting Venice image generation for Cat ID {$category_id}.");
            $image_result = aiccgen_google_generate_venice_image($venice_api_key, $image_prompt_text_for_cat);
            if ($image_result['success'] && isset($image_result['attachment_id'])) {
                $generated_image_id_for_post = $image_result['attachment_id'];
                $image_generation_status_log_msg = 'Image generated successfully (ID: ' . $generated_image_id_for_post . ').';
                my_plugin_log("[AI Cat Gen Cron] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
            } else {
                $venice_error = isset($image_result['error']) ? esc_html($image_result['error']) : 'Unknown Venice API error.';
                $image_generation_status_log_msg = "Image generation failed: {$venice_error}.";
                my_plugin_log("[AI Cat Gen Cron/Error] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
            }
        } else {
            $image_generation_status_log_msg = 'Image generation skipped: Venice AI API key missing in settings.';
            my_plugin_log("[AI Cat Gen Cron] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
        }
    }

    // --- Step 5: Create Draft Post ---
    $post_title_prefix = __('Scheduled AI Draft:', 'ai-cat-content-gen-google');
    $post_title_for_new_post = sprintf('%s %s - %s', $post_title_prefix, $category_name, date_i18n(get_option('date_format') . ' H:i'));

    my_plugin_log("[AI Cat Gen Cron] Attempting to create draft post for Cat ID {$category_id} with title '{$post_title_for_new_post}'.");
    $create_post_result = aiccgen_google_create_draft_post(
        $category_id,
        $post_title_for_new_post,
        $content_to_post, // This is either original Google content or WordAI rewritten content
        $generated_image_id_for_post
    );

    if ($create_post_result['success'] && isset($create_post_result['post_id'])) {
        $new_post_id = $create_post_result['post_id'];
        // Save WordAI status as post meta
        update_post_meta($new_post_id, AICCG_GOOGLE_WORDAI_STATUS_META_KEY, $wordai_meta_status_for_post);

        $final_log_message = sprintf(
            'Draft post created for Cat ID %d. Post ID: %d. WordAI Status: %s. Image Status: %s',
            $category_id,
            $new_post_id,
            $wordai_meta_status_for_post, // Using the meta status string
            $image_generation_status_log_msg
        );
        my_plugin_log("[AI Cat Gen Cron/Success] " . $final_log_message);
    } else {
        $post_creation_error = isset($create_post_result['error']) ? esc_html($create_post_result['error']) : 'Unknown post creation error.';
        my_plugin_log("[AI Cat Gen Cron/Error] Post creation FAILED for Cat ID {$category_id}: {$post_creation_error}");
        // If post creation failed, and an image was generated, delete the orphaned image
        if ($generated_image_id_for_post) {
            wp_delete_attachment($generated_image_id_for_post, true);
            my_plugin_log("[AI Cat Gen Cron] Deleted orphaned image (ID: {$generated_image_id_for_post}) for Cat ID {$category_id} due to post creation failure.");
        }
    }
    my_plugin_log("[AI Cat Gen Cron] Finished processing for Cat ID {$category_id}.");
}
//add_action(AICCG_GOOGLE_CRON_HOOK, 'aiccgen_google_run_scheduled_generation', 10, 1);


// --- Generate_and_create_post (Handles Content, Refine, Image, Post Create) ---
function aiccgen_google_generate_and_create_post($cat_id, $category_name, $prompt_text, $image_prompt_text, $formatting_instructions_text, $google_api_key, $venice_api_key, $model, $context = 'Process') {
    $notice_prefix = sprintf(__('%s generation for "%s": ', 'ai-cat-content-gen-google'), $context, esc_html($category_name));
    $generated_image_id = null; // Initialize image ID
    $image_generation_status_msg = ''; // For logging/notices
    $wordai_status_msg = '';
    $wordai_meta_status = AICCG_WORDAI_STATUS_NOT_ATTEMPTED; // Default meta status

    // Step 1: Generation (Google AI Content)
    $final_prompt = aiccgen_google_build_api_prompt($category_name, $prompt_text);
    $final_prompt = aiccgen_google_build_api_prompt($category_name, $prompt_text, $formatting_instructions_text);
    $generation_response = aiccgen_google_call_gemini_api($google_api_key, $model, $final_prompt);

    

    if (!$generation_response['success']) {
        $error_msg = esc_html($generation_response['error']);
        return [
            'success' => false,
            'notice' => ['type' => 'error', 'message' => $notice_prefix . sprintf(__('Content generation FAILED - %s', 'ai-cat-content-gen-google'), $error_msg)]
        ];
    }
    $content = $generation_response['content'];

    $plugin_options = get_option(AICCG_GOOGLE_OPTION_NAME); // Fetch options once
    $wordai_api_key = isset($plugin_options['wordai_api_key']) ? trim($plugin_options['wordai_api_key']) : '';
    $wordai_email = isset($plugin_options['wordai_email']) ? trim($plugin_options['wordai_email']) : '';
    
    if (!empty($wordai_api_key) && !empty($wordai_email)) {
        if (!empty(trim($content))) {
            //my_plugin_log("[AI Cat Gen Cron/Info] Cat ID {$cat_id}: Attempting WordAI rewrite for automated post.");
            $wordai_response = aiccgen_call_wordai_api($content, $wordai_email, $wordai_api_key);
            if ($wordai_response['success']) {
                if (!empty(trim($wordai_response['rewritten_text']))) {
                    if (isset($wordai_response['wordai_result_empty']) && $wordai_response['wordai_result_empty'] === true) {
                        // WordAI processing led to empty, so original Google content is used.
                        $wordai_status_msg = ' ' . __('(WordAI processing resulted in empty text, using Google AI content.)', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                        my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI result empty, using Google AI content.");
                    } elseif (isset($wordai_response['skipped_wordai']) && $wordai_response['skipped_wordai'] === true) {
                        // WordAI was skipped because input was effectively empty after cleaning
                        $wordai_status_msg = ' ' . __('(WordAI skipped: input effectively empty after cleaning, using Google AI content.)', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT; // Or a more specific status
                        my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI skipped (cleaned input empty), using Google AI content.");
                    }
                    else {
                        $content = $wordai_response['rewritten_text']; // Update content with WordAI version
                        $wordai_status_msg = ' ' . __('Content rewritten by WordAI.', 'ai-cat-content-gen-google');
                        $wordai_meta_status = AICCG_WORDAI_STATUS_SUCCESS;
                        //my_plugin_log("[AI Cat Gen Cron/Info] Cat ID {$cat_id}: WordAI rewrite successful for automated post.");
                    }
                } else { // WordAI success but rewritten_text is empty
                    $wordai_status_msg = ' ' . __('(WordAI returned empty rewrite, using Google AI content.)', 'ai-cat-content-gen-google');
                    $wordai_meta_status = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                    my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI returned empty text, using Google AI content.");
                }
            } else { // WordAI call failed
                $wordai_error_msg = isset($wordai_response['error']) ? $wordai_response['error'] : __('Unknown WordAI error.', 'ai-cat-content-gen-google');
                $wordai_status_msg = ' ' . sprintf(__('(WordAI rewrite failed: %s. Using Google AI content.)', 'ai-cat-content-gen-google'), esc_html($wordai_error_msg));
                $wordai_meta_status = AICCG_WORDAI_STATUS_API_ERROR;
                my_plugin_log("[AI Cat Gen Cron/Error] Cat ID {$cat_id}: WordAI rewrite FAILED for automated post - " . $wordai_error_msg);
            }
        } else { // Original Google content was empty
            $wordai_status_msg = ' ' . __('(WordAI rewrite skipped: Original Google AI content was empty.)', 'ai-cat-content-gen-google');
            $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
            my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI rewrite skipped for automated post - Original Google AI content was empty.");
        }
    } elseif (empty(trim($content))) { // Content was empty to begin with (even before WordAI check)
        $wordai_status_msg = ' ' . __('(WordAI rewrite skipped: Google AI content was empty.)', 'ai-cat-content-gen-google');
        $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
         my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI not attempted as Google content was empty.");
    }
    else { // API key or email missing
        $wordai_status_msg = ' ' . __('(WordAI rewrite skipped: API Key or Email missing in settings.)', 'ai-cat-content-gen-google');
        $wordai_meta_status = AICCG_WORDAI_STATUS_SKIPPED_CONFIG;
        my_plugin_log("[AI Cat Gen Cron/Warning] Cat ID {$cat_id}: WordAI rewrite skipped for automated post - API Key or Email missing.");
    }

    // Step 3: Image Generation (Venice AI - Optional)
    $image_prompt_text_trimmed = trim($image_prompt_text);
    if (!empty($image_prompt_text_trimmed) && !empty($venice_api_key)) {
        $image_result = aiccgen_google_generate_venice_image($venice_api_key, $image_prompt_text_trimmed);
        if ($image_result['success'] && isset($image_result['attachment_id'])) {
            $generated_image_id = $image_result['attachment_id'];
            $image_generation_status_msg .= ' ' . __('Image generated successfully (Landscape 3:2).', 'ai-cat-content-gen-google');
        } else {
            // Log image generation failure but proceed without image
            $image_error = esc_html($image_result['error'] ?? __('Unknown error', 'ai-cat-content-gen-google'));
            $image_generation_status_msg .= ' ' . sprintf(__('(Image generation failed: %s)', 'ai-cat-content-gen-google'), $image_error);
            my_plugin_log("[AI Cat Gen Helper/$context] Cat ID {$cat_id}: Image generation failed - {$image_error}");
        }
    } elseif (!empty($image_prompt_text_trimmed) && empty($venice_api_key)) {
        $image_generation_status_msg .= ' ' . __('(Image generation skipped: Venice AI API key missing)', 'ai-cat-content-gen-google');
         my_plugin_log("[AI Cat Gen Helper/$context] Cat ID {$cat_id}: Image generation skipped - Venice API key missing.");
    }

    // Step 4: Create Draft Post (with optional Featured Image)
    $post_title_prefix = ($context === 'Scheduled') ? __('Scheduled Draft:', 'ai-cat-content-gen-google') : __('Draft:', 'ai-cat-content-gen-google');
    $post_title = sprintf('%s %s - %s', $post_title_prefix, $category_name, date_i18n(get_option('date_format')));
    // Pass the generated image ID (null if failed or not requested)
    $create_result = aiccgen_google_create_draft_post($cat_id, $post_title, $content, $generated_image_id);

     if ($create_result['success']) {
        if ($create_result['post_id']) {
            update_post_meta($create_result['post_id'], AICCG_GOOGLE_WORDAI_STATUS_META_KEY, $wordai_meta_status);
        }
        $edit_link = get_edit_post_link($create_result['post_id'], 'raw');
        $edit_link_html = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($edit_link), __('Edit Draft', 'ai-cat-content-gen-google'));
        // Append image status message to the main success message
        $success_message = $notice_prefix . sprintf(__('Draft created successfully. %s', 'ai-cat-content-gen-google'), $edit_link_html) . esc_html($image_generation_status_msg);

        return [
            'success' => true,
            'notice' => ['type' => 'success', 'message' => $success_message]
        ];
    } else {
        $error_msg = esc_html($create_result['error']);
        // If post creation failed, we should maybe delete the generated image?
        if ($generated_image_id) {
            wp_delete_attachment($generated_image_id, true); // Force delete image if post creation fails
            my_plugin_log("[AI Cat Gen Helper/$context] Cat ID {$cat_id}: Deleted generated image (ID: {$generated_image_id}) because post creation failed.");
        }
        return [
            'success' => false,
            'notice' => ['type' => 'error', 'message' => $notice_prefix . sprintf(__('Post creation FAILED - %s', 'ai-cat-content-gen-google'), $error_msg)]
        ];
    }
}

// --- AJAX Handler to move a standard post back to AI Suggestions ---
function aiccgen_google_ajax_move_to_ai_suggestion() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    // Verify nonce
    check_ajax_referer('aiccgen_reedit_post_nonce_' . $post_id, '_ajax_nonce');

    // Capability checks
    if (!current_user_can('edit_post', $post_id) || !current_user_can('publish_posts')) { // Assuming 'publish_posts' for CPT creation
        wp_send_json_error(['message' => __('You do not have permission for this action.', 'ai-cat-content-gen-google')], 403);
    }

    $original_post = get_post($post_id);
    if (!$original_post || $original_post->post_type !== 'post') {
        wp_send_json_error(['message' => __('Invalid post specified.', 'ai-cat-content-gen-google')], 400);
    }

    // Check if it's actually an AI generated post
    if (!get_post_meta($post_id, '_aiccgen_is_ai_generated_post', true)) {
        wp_send_json_error(['message' => __('This post did not originate from AI Suggestions and cannot be moved back.', 'ai-cat-content-gen-google')], 400);
    }

    // --- Gather data from the original standard post ---
    $title = $original_post->post_title;
    // Prepend "Re-editing:" to the title to make it clear in the AI Suggestions list
    // if (strpos($title, __('', 'ai-cat-content-gen-google')) === false) { // This check seems incomplete.
    // Let's make it simpler: if "Re-editing: " is not already a prefix.
    $re_edit_prefix = __('', 'ai-cat-content-gen-google') . ' ';
    if (strpos($title, $re_edit_prefix) !== 0) {
        $title = $re_edit_prefix . $title;
    }


    $content = $original_post->post_content;
    $author = $original_post->post_author; // Or use get_post_meta($post_id, '_aiccgen_original_suggestion_author', true); if you stored it
    $categories = wp_get_post_categories($post_id, ['fields' => 'ids']);
    $featured_image_id = get_post_thumbnail_id($post_id);

    // --- Create the new AI Suggestion post ---
    $ai_suggestion_data = [
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'draft',       // New AI suggestions are drafts
        'post_type'     => 'ai_suggestion',
        'post_author'   => $author,
        'post_category' => $categories,
    ];

    
    $kses_filters_removed = false;
    if (has_filter('content_save_pre', 'wp_filter_post_kses')) {
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $kses_filters_removed = true;
    }

    $new_ai_suggestion_id = wp_insert_post($ai_suggestion_data, true);

    if ($kses_filters_removed) {
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    if (is_wp_error($new_ai_suggestion_id)) {
        wp_send_json_error(['message' => __('Failed to create new AI Suggestion: ', 'ai-cat-content-gen-google') . $new_ai_suggestion_id->get_error_message()], 500);
    }

    // Set featured image for the new AI Suggestion
    if ($featured_image_id) {
        set_post_thumbnail($new_ai_suggestion_id, $featured_image_id);
    }

    // *** Mark this AI Suggestion as a re-edit from a post ***
    if ($new_ai_suggestion_id && !is_wp_error($new_ai_suggestion_id)) {
        update_post_meta($new_ai_suggestion_id, AICCG_GOOGLE_IS_REEDIT_META_KEY, true);
    }


    // PERMANENTLY DELETE the original standard post
    $deleted = wp_delete_post($post_id, true); // true = force delete, bypass trash

    if (!$deleted) {
        my_plugin_log("[AI Cat Gen Re-edit] FAILED to delete original post ID {$post_id} after creating AI Suggestion {$new_ai_suggestion_id}.");       
    } else {
        my_plugin_log("[AI Cat Gen Re-edit] Successfully deleted original post ID {$post_id} after creating AI Suggestion {$new_ai_suggestion_id}.");
    }


    // --- Prepare response ---
    $redirect_url = get_edit_post_link($new_ai_suggestion_id, 'raw');

    wp_send_json_success([
        'message'      => __('Post successfully moved for re-editing.', 'ai-cat-content-gen-google'),
        'redirect_url' => $redirect_url,
        'new_suggestion_id' => $new_ai_suggestion_id
    ]);
}
add_action('wp_ajax_aiccgen_google_move_to_ai_suggestion', 'aiccgen_google_ajax_move_to_ai_suggestion');

// --- Reschedule all tasks based on current settings ---
function aiccgen_google_reschedule_all_tasks() {
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $prompts = isset($options['prompts']) ? $options['prompts'] : [];
    $frequencies = isset($options['frequency']) ? $options['frequency'] : [];

    // Clear ALL existing hooks first more reliably
    $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK);
    while($timestamp) {
        $hook_args = get_scheduled_event(AICCG_GOOGLE_CRON_HOOK, [], $timestamp);
        if ($hook_args) {
            wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK, $hook_args->args);
        } else {
             // Fallback, try unscheduling without args if getting the event failed
            wp_unschedule_event($timestamp, AICCG_GOOGLE_CRON_HOOK);
        }
        // Crucially, check for the *next* scheduled event after potentially unscheduling one
        $timestamp = wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK);
    }

    if (empty($prompts) || !is_array($prompts)) {
        return; // No prompts configured, nothing to schedule
    }

    foreach ($prompts as $cat_id => $prompt_text) {
        if (empty(trim($prompt_text))) continue; // Skip if content prompt is empty

        $cat_id = absint($cat_id);
        $frequency = isset($frequencies[$cat_id]) ? $frequencies[$cat_id] : 'none';
        $args = ['category_id' => $cat_id];

        // Only schedule if frequency is valid for scheduling (not 'none')
        if ($frequency !== 'none') {
             if (!wp_next_scheduled(AICCG_GOOGLE_CRON_HOOK, $args)) {
                 $first_run_time = time() + 60; // Add a small delay
                 wp_schedule_event($first_run_time, $frequency, AICCG_GOOGLE_CRON_HOOK, $args);
                 // Optional: Log successful scheduling
                 // my_plugin_log("[AI Cat Gen Activation/Reschedule] Scheduled task ({$frequency}) for Cat ID {$cat_id}.");
             }
        }
    }
}


// --- Instruction for the AI generation---
function aiccgen_google_build_api_prompt($category_name, $user_prompt, $formatting_instructions = '') {
    $current_date = date_i18n(get_option('date_format'));

    // Prepare the formatting instructions part conditionally
    $instructions_part = '';
    $formatting_instructions_trimmed = trim($formatting_instructions);

    if (!empty($formatting_instructions_trimmed)) {
        $instructions_part = sprintf(
            "**CRITICAL: User-Defined Formatting & Content Rules:**\n" .
            "You are tasked with generating HTML content. The following rules, provided by the user, are ABSOLUTELY MANDATORY and dictate the precise HTML structure and formatting of the output. You MUST follow these rules literally and meticulously. Any content generated must conform to these rules by using the specified HTML tags or structures. These rules override any of your default formatting preferences or tendencies.\n\n" .
            "---BEGIN USER-DEFINED HTML RULES---\n" .
            "%s\n" . // User's raw formatting instructions will be inserted here
            "---END USER-DEFINED HTML RULES---\n\n" .
            "**Mandatory Interpretation and Application of User-Defined Rules:**\n" .
            "Your primary task after generating the initial content is to go back through it and ensure that EVERY SINGLE RULE listed above under 'USER-DEFINED HTML RULES' has been applied correctly and to the letter. For each rule provided by the user:\n" .
            "1.  Understand the user's intent for HTML structure and styling.\n" .
            "2.  If the rule specifies using specific HTML tags (e.g., `<p>`, `<h2>`, `<strong>`, `<em>`, `<ul>`, `<li>`, `<hr>`, etc.), you MUST use those exact HTML tags in your output. Do not substitute with Markdown or other formats.\n" .
            "3.  If the rule describes a structural change to the content (e.g., inserting an element, wrapping certain text, changing order), you MUST implement that structural change in the final HTML.\n" .
            "4.  If the rule requires identifying specific types of content (e.g., 'all dates', 'company names', 'locations') and applying formatting, you must diligently identify such content within your generated text and apply the user's specified HTML formatting to it.\n" .
            "5.  These user-defined rules take absolute precedence. If a user rule seems to conflict with general web best practices, you MUST still follow the user's rule.\n" .
            "6.  The final output MUST be pure, valid HTML. Do NOT include any Markdown syntax (like `## Heading`, `**bold**`, `*italic*`, `- list item`) unless a User-Defined Rule explicitly instructs you to output Markdown as *literal text within the HTML* (which is highly unusual and should be double-checked if the rule is ambiguous).\n\n" .
            "After generating the initial content based on the User Request, your next critical step is to meticulously review that content and apply ALL the User-Defined HTML Rules listed above. Ensure the final output strictly adheres to every rule.\n\n",
            $formatting_instructions_trimmed // The user's actual rules are injected here
        );
    } else {
        // If no user-defined rules, provide a basic instruction about HTML.
        $instructions_part =
            "**HTML Output Requirements:**\n" .
            "The entire output MUST be valid HTML. All text content should be appropriately wrapped in HTML tags (e.g., `<p>` for paragraphs, heading tags like `<h2>`, `<h3>` for sections, `<ul>/<li>` for lists, `<strong>/<em>` for emphasis, etc.).\n" .
            "Avoid Markdown syntax (like `## Heading`, `**bold**`, `*italic*`, `- list item`) in the final HTML output.\n\n";
    }

    // Construct the final prompt
    return sprintf(
        "You are an expert HTML content generator. Your task is to generate a well-structured news or content summary for a WordPress blog category named '%s'.\n" .
        "First, understand the User Request provided below. Then, generate content based on this request, enhancing it with relevant, factual information from recent web searches (ensure information is current up to %s).\n\n" .
        // --- START: Insert the User-Defined Formatting Rules (or default HTML instructions) ---
        "%s" . // This is where $instructions_part will go.
        // --- END: Insert the User-Defined Formatting Rules ---
        "**General Output Structure (Unless Overridden by User-Defined Rules):**\n" .
        "1.  **Paragraphs:** Default to wrapping standard text paragraphs in `<p>` and `</p>` tags.\n" .
        "2.  **Headings:** Default to using `<h2>` for main section titles and `<h3>` for sub-section titles.\n" .
        "3.  **Lists:** If a list format is appropriate for the content, default to using standard `<ul>/<li>` for unordered lists or `<ol>/<li>` for ordered lists.\n" .
        "4.  **General Recommendations Section:** After the main content, include a 'General Recommendations' section. This section should also be valid HTML (e.g., `<h3>General Recommendations</h3><p>For more information, visit...</p>`). Recommend 1-3 reputable websites. This section is also subject to any applicable User-Defined HTML Rules.\n\n" .
        "**User Request (for content topic):**\n---\n%s\n---",

        esc_html($category_name),
        esc_html($current_date),
        $instructions_part, // Contains the user's rules (if any) and how to interpret them, or default HTML guidance.
        esc_html($user_prompt)
    );
}

// --- Instruction for the AI generation Refine---
function aiccgen_google_build_refinement_prompt($original_content, $refinement_instructions) {
    // This prompt is for taking existing text and applying instructions to it.
    return sprintf(
        "You are assisting a user in refining text content for a blog.\n" .
        "Below is the original text. Please refine it based *only* on the user's refinement request provided below.\n" .
        "Try to maintain the original structure (headings, lists, HTML elements etc.) unless the user's request specifically asks to change it or implies a structural change (e.g., 'rewrite as a list').\n" .
        "Ensure the output is valid HTML, with paragraphs in `<p>` tags, headings in `<h2>`/`<h3>` etc. Avoid Markdown.\n\n" .
        "**Original Text:**\n---\n%s\n---\n\n" .
        "**User's Refinement Request:**\n---\n%s\n---",
        $original_content, 
        $refinement_instructions
    );
}

// Gemini API Function
function aiccgen_google_call_gemini_api($api_key, $model, $prompt) {
    // Same as before
    if (empty($api_key) || empty($model) || empty($prompt)) {
        return ['success' => false, 'content' => null, 'error' => __('Missing Google API key, model, or prompt.', 'ai-cat-content-gen-google'), 'code' => 400];
    }

    $api_url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=%s', esc_attr($api_key));

    $request_body_array = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.6,
            // 'maxOutputTokens' => 2048, // Consider if needed
        ],
         'safetySettings' => [
             ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
             ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
             ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
             ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
         ],
        //  'tools' => [ 
        //      [
        //          // Use googleSearchRetrieval for grounding with Google Search
        //          'googleSearchRetrieval' => new \stdClass()
        //      ]
        //  ]
    ];

    $request_body = wp_json_encode($request_body_array); // Use wp_json_encode
    if ($request_body === false) {
         return ['success' => false, 'content' => null, 'error' => __('Internal error preparing API request (JSON).', 'ai-cat-content-gen-google') . ' Error: ' . json_last_error_msg(), 'code' => 500];
    }

    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $request_body,
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 180, // increase if needed
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return ['success' => false, 'content' => null, 'error' => __('Error communicating with Google AI service: ', 'ai-cat-content-gen-google') . $error_message, 'code' => 500];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    // Check for API-level errors first
    if (isset($data['error'])) {
        $api_error_message = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown Google API error structure.', 'ai-cat-content-gen-google');
        return ['success' => false, 'content' => null, 'error' => 'Google API Error: ' . $api_error_message, 'code' => $response_code];
    }

    // Check for specific content blocking reasons before assuming success
     if (isset($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] !== 'STOP') {
         $finish_reason = $data['candidates'][0]['finishReason'];
         $error_msg = __('AI response did not complete successfully.', 'ai-cat-content-gen-google');
         if ($finish_reason === 'SAFETY') {
             $error_msg = __('Google AI response blocked due to safety settings.', 'ai-cat-content-gen-google');
         } elseif ($finish_reason === 'RECITATION') {
             $error_msg = __('Google AI response blocked due to potential recitation issues.', 'ai-cat-content-gen-google');
         } elseif ($finish_reason === 'MAX_TOKENS') {
              $error_msg = __('Google AI response incomplete: Maximum output length reached.', 'ai-cat-content-gen-google');
         } else {
             $error_msg = sprintf(__('Google AI response ended unexpectedly (Reason: %s).', 'ai-cat-content-gen-google'), $finish_reason);
         }
        return ['success' => false, 'content' => null, 'error' => $error_msg, 'code' => ($finish_reason === 'SAFETY' || $finish_reason === 'RECITATION') ? 400 : 500];
    }

    // Check if the expected text part exists
    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
         $error_msg = __('Google AI response structure invalid or missing content.', 'ai-cat-content-gen-google');
         // Log the raw response body for debugging if this happens
         my_plugin_log("[AI Cat Gen API Error] Invalid Gemini response structure: " . $response_body);
         return ['success' => false, 'content' => null, 'error' => $error_msg, 'code' => 500];
    }

    // Check response code *after* checking for specific error structures
    if ($response_code < 200 || $response_code >= 300) {
        return ['success' => false, 'content' => null, 'error' => __('Unexpected response status from Google AI service.', 'ai-cat-content-gen-google') . ' (Code: ' . $response_code . ')', 'code' => $response_code];
    }


    $generated_content = $data['candidates'][0]['content']['parts'][0]['text'];
    return ['success' => true, 'content' => $generated_content, 'error' => null, 'code' => $response_code];
}

// --- function for Venice AI Image Generation ---
function aiccgen_google_generate_venice_image($api_key, $prompt) {
    if (empty($api_key) || empty($prompt)) {
        return ['success' => false, 'attachment_id' => null, 'error' => __('Missing Venice AI API key or image prompt.', 'ai-cat-content-gen-google')];
    }

    $api_url = 'https://api.venice.ai/api/v1/image/generate';
    // Consider making model, size, etc., configurable in the future
    $data = [
        'model' => 'flux-dev', // VENICE MODEL Check if this is still the desired model to generate Landscape images
        'prompt' => $prompt,
        'height' => 848,
        'width' => 1264,
        'steps' => 30,
        'cfg_scale' => 7.5,
        'return_binary' => false, // Request base64 encoded string
        'hide_watermark' => true,
        'format' => 'png'
    ];

    $request_body = wp_json_encode($data);
    if ($request_body === false) {
        return ['success' => false, 'attachment_id' => null, 'error' => __('Internal error preparing Venice API request (JSON).', 'ai-cat-content-gen-google') . ' Error: ' . json_last_error_msg()];
    }

    // Use wp_remote_post for consistency and better error handling
    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => $request_body,
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 5 * 60, // Image generation can take time
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return ['success' => false, 'attachment_id' => null, 'error' => __('Error communicating with Venice AI service: ', 'ai-cat-content-gen-google') . $error_message];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);

    // Check for Venice API specific errors (structure may vary)
    if ($response_code != 200 || !$result || isset($result['error']) || !isset($result['images'][0])) {
        $api_error_message = __('Unknown Venice API error.', 'ai-cat-content-gen-google');
        if (isset($result['error']['message'])) {
             $api_error_message = $result['error']['message'];
        } elseif (isset($result['detail'])) { // Some APIs use 'detail' for errors
            $api_error_message = is_string($result['detail']) ? $result['detail'] : wp_json_encode($result['detail']);
        } elseif ($response_code != 200) {
            $api_error_message = sprintf(__('API returned status %d.', 'ai-cat-content-gen-google'), $response_code);
        }
         // Log the full response body for debugging Venice errors
         my_plugin_log("[AI Cat Gen Venice Error] Response Code: {$response_code}, Body: " . $response_body);
        return ['success' => false, 'attachment_id' => null, 'error' => 'Venice AI Error: ' . $api_error_message];
    }

    $base64_image = $result['images'][0];
    $image_data = base64_decode($base64_image);

    if ($image_data === false) {
         return ['success' => false, 'attachment_id' => null, 'error' => __('Failed to decode base64 image data from Venice AI.', 'ai-cat-content-gen-google')];
    }

    // Save image to WordPress uploads
    $upload_dir = wp_upload_dir();
    $safe_prompt_prefix = substr(sanitize_title(substr($prompt, 0, 50)), 0, 40); // Limit length
    $unique_filename = 'aiccgen-' . $safe_prompt_prefix . '-' . time() . '.png';
    $image_path = $upload_dir['path'] . '/' . $unique_filename;

    // Ensure the uploads directory is writable
    if (!wp_is_writable($upload_dir['path'])) {
        return ['success' => false, 'attachment_id' => null, 'error' => sprintf(__('Uploads directory is not writable: %s', 'ai-cat-content-gen-google'), $upload_dir['path'])];
    }

    $file_saved = file_put_contents($image_path, $image_data);
    if ($file_saved === false) {
         return ['success' => false, 'attachment_id' => null, 'error' => __('Failed to save generated image to disk.', 'ai-cat-content-gen-google')];
    }

    // Create WordPress attachment
    $filetype = wp_check_filetype(basename($image_path), null);
    $attachment = [
        'guid'           => $upload_dir['url'] . '/' . basename($image_path),
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name($unique_filename),
        'post_content'   => '', // Can add prompt here if desired: 'Generated from prompt: ' . esc_html($prompt),
        'post_status'    => 'inherit'
    ];

    $attachment_id = wp_insert_attachment($attachment, $image_path);

    if (is_wp_error($attachment_id)) {
        aiccgen_google_delete_file($image_path); // Clean up saved file if attachment fails // <-- CORRECTED CALL
        return ['success' => false, 'attachment_id' => null, 'error' => __('Failed to create WordPress attachment: ', 'ai-cat-content-gen-google') . $attachment_id->get_error_message()];
    }

    // Generate attachment metadata (thumbnails etc.)
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $image_path);
    if (is_wp_error($attachment_metadata) || empty($attachment_metadata)) {
        // Attachment exists, but metadata failed. Not fatal, but log it.
         my_plugin_log("[AI Cat Gen Venice Warning] Failed to generate attachment metadata for ID {$attachment_id}. Error: " . ($is_wp_error($attachment_metadata) ? $attachment_metadata->get_error_message() : 'Empty metadata'));
    } else {
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
    }
    // Return success with the attachment ID
    return ['success' => true, 'attachment_id' => $attachment_id, 'error' => null];
}

// --- Create draft post (accepts featured image ID) ---
function aiccgen_google_create_draft_post($category_id, $post_title, $post_content, $featured_image_id = null) {
    $admin_users = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
    $post_author_id = (!empty($admin_users)) ? $admin_users[0]->ID : get_current_user_id(); // Fallback to current user if no admin found
    if (!$post_author_id) $post_author_id = 1; // Absolute fallback

    if (empty($post_title) || empty($post_content) || $category_id <= 0) {
        return ['success' => false, 'post_id' => null, 'error' => __('Missing title, content, or category ID for post creation.', 'ai-cat-content-gen-google')];
    }
    if (!term_exists($category_id, 'category')) {
        return ['success' => false, 'post_id' => null, 'error' => sprintf(__('Category ID %d does not exist.', 'ai-cat-content-gen-google'), $category_id)];
    }

    // --- Remove any unwanted static "html" or special characters "```" at the top/bottom of content ---
    $clean_content = $post_content;

    // Remove "html" at the very start (with or without whitespace/newlines)
    $clean_content = preg_replace('/^\s*```html\s*/i', '', $clean_content);

    // Remove "html" at the very end (with or without whitespace/newlines)
    $clean_content = preg_replace('/\s*```\s*$/i', '', $clean_content);

    // Remove any leading/trailing special characters (optional, if needed)
    $clean_content = trim($clean_content, "\xEF\xBB\xBF \t\n\r\0\x0B");

    $post_data = [
        'post_title'    => wp_strip_all_tags($post_title),
        'post_content'  => wp_kses_post($clean_content), // Use KSES for safety
        'post_status'   => 'draft',
        'post_author'   => $post_author_id,
        'post_category' => [$category_id],
        'post_type'     => 'ai_suggestion',
    ];

    // Temporarily remove kses filters for insertion if they interfere
    $kses_filters_removed = false;
    if (has_filter('content_save_pre', 'wp_filter_post_kses')) {
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $kses_filters_removed = true;
    }

    $post_id = wp_insert_post($post_data, true); // Pass true to enable WP_Error return

    // Add kses filters back if they were removed
    if ($kses_filters_removed) {
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }


    if (is_wp_error($post_id)) {
        $error_message = $post_id->get_error_message();
        return ['success' => false, 'post_id' => null, 'error' => __('WordPress error creating post: ', 'ai-cat-content-gen-google') . $error_message];
    } elseif ($post_id === 0) {
        return ['success' => false, 'post_id' => null, 'error' => __('Failed to create post draft (wp_insert_post returned 0).', 'ai-cat-content-gen-google')];
    } else {
        // Post created successfully, now try setting the featured image
        if ($featured_image_id && is_numeric($featured_image_id) && $featured_image_id > 0) {
            // Check if the attachment ID is valid
             if (wp_get_attachment_url($featured_image_id)) {
                set_post_thumbnail($post_id, absint($featured_image_id));
            } else {
                 
                 my_plugin_log("[AI Cat Gen Post Create] Warning: Attempted to set invalid featured image ID ({$featured_image_id}) for Post ID {$post_id}.");
            }
        }
        return ['success' => true, 'post_id' => $post_id, 'error' => null];
    }
}

//  Display Status after save the plugin settings
function aiccgen_google_show_save_notices() {
    $screen = get_current_screen();
    $results = get_transient(AICCG_GOOGLE_SETTINGS_NOTICE_TRANSIENT);
    if ($results && is_array($results)) {
        $notice_html = '';
        $overall_type = 'info'; // Default type

        // Determine overall status based on schedule actions
        $has_schedule_updates = isset($results['schedule_updates']) && $results['schedule_updates'] > 0;
        $has_schedule_cleared = isset($results['schedule_cleared']) && $results['schedule_cleared'] > 0;
        $has_errors = false;
        if (!empty($results['details'])) {
             foreach ($results['details'] as $detail) {
                if(isset($detail['type']) && ($detail['type'] === 'error' || $detail['type'] === 'warning')) {
                    $has_errors = true;
                    break;
                }
             }
        }

        if ($has_errors) {
            $overall_type = 'warning'; // Use warning if there were schedule errors/warnings
        } elseif ($has_schedule_updates || $has_schedule_cleared) {
            $overall_type = 'success'; // Success if actions occurred without errors
        } else {
             $overall_type = 'info'; // Info if just saved with no changes triggering actions
        }

        // Build Summary Message
        $summary_parts = [];
        if ($has_schedule_updates || $has_schedule_cleared) {
             $schedule_summary = sprintf(__('%d schedule(s) updated/set, %d schedule(s) cleared.', 'ai-cat-content-gen-google'),
                isset($results['schedule_updates']) ? $results['schedule_updates'] : 0,
                isset($results['schedule_cleared']) ? $results['schedule_cleared'] : 0
            );
             $summary_parts[] = $schedule_summary;
         }

        if (!empty($summary_parts)) {
            $notice_html .= '<p><strong>' . implode(' ', $summary_parts) . '</strong></p>';
        } else {
             $notice_html .= '<p><strong>' . __('Settings saved. No schedule changes detected.', 'ai-cat-content-gen-google') . '</strong></p>'; // More informative default
        }

        // Add Details List (Scrollable)
        if (!empty($results['details']) && is_array($results['details'])) {
             $notice_html .= '<ul style="margin-top: 5px; list-style: disc; margin-bottom: 0px; max-height: 150px; overflow-y: auto; border: 1px solid #eee; padding: 5px;">';
             foreach ($results['details'] as $detail) {
                 if (!is_array($detail) || !isset($detail['message'])) continue;

                 $detail_type = isset($detail['type']) ? $detail['type'] : 'info';
                 $color = '#333'; // Default color
                 $icon = ' інформация;'; // Info icon

                 if ($detail_type === 'success') {$color = '#28a745'; $icon = '✔'; }
                 if ($detail_type === 'warning') {$color = '#ffc107'; $icon = '⚠'; }
                 if ($detail_type === 'error')   {$color = '#dc3545'; $icon = '✘'; }
                 if ($detail_type === 'info')    {$color = '#17a2b8'; $icon = 'ℹ'; }

                 // Use wp_kses_post carefully, ensure messages don't have harmful HTML
                 $notice_html .= '<li style="margin-bottom: 3px; color: ' . esc_attr($color) . ';"><span style="margin-right: 5px;">' . $icon . '</span>' . wp_kses_post($detail['message']) . '</li>';
             }
             $notice_html .= '</ul>';
        }

        // Output the notice div
        printf(
            '<div id="setting-error-settings_updated" class="notice notice-%s is-dismissible settings-error"><div style="padding: 10px 0;">%s</div></div>',
            esc_attr($overall_type), // Use determined overall type
            $notice_html // Contains KSESed content and escaped attributes
        );

        // Delete the transient so it doesn't show again
        delete_transient(AICCG_GOOGLE_SETTINGS_NOTICE_TRANSIENT);
    }
}
add_action('admin_notices', 'aiccgen_google_show_save_notices');

// Delete 2 other images after selected one refine image
function aiccgen_google_delete_file( $file ) {
    $upload_dir = wp_upload_dir();
    if ( strpos( realpath( $file ), realpath( $upload_dir['basedir'] ) ) !== 0 ) {
        // File is not inside the uploads directory, bail out for safety.
        my_plugin_log( "[AI Cat Gen Security] Attempted to delete file outside uploads directory: " . $file );
        return false;
    }

    // Use WP Filesystem API if possible for better compatibility/safety
    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if ( $wp_filesystem ) {
        return $wp_filesystem->delete( $file );
    } else {
        // Fallback to PHP unlink if WP_Filesystem fails to initialize
        if ( file_exists( $file ) ) {
            return unlink( $file );
        }
    }
    return false;
}

// --- Action Scheduler Hook for Scheduling Category Processing ---
function aiccgen_google_schedule_category_action_for_as($category_id) {
    $category_id = absint($category_id);
    if ($category_id === 0) {
        my_plugin_log("[AI Cat Gen AS Scheduler] Invalid category ID 0 passed to WP Cron hook for AS scheduling.");
        return;
    }

    if (function_exists('as_schedule_single_action')) {
        $args = array('category_id' => $category_id);
        // Check if a PENDING action for this specific category and hook already exists
        if (function_exists('as_has_scheduled_action') && as_has_scheduled_action('aiccgen_do_actual_category_processing', $args, 'aiccgen_content_generation_tasks')) {
            my_plugin_log("[AI Cat Gen AS Scheduler] Action Scheduler task for Cat ID {$category_id} (hook 'aiccgen_do_actual_category_processing') is already PENDING. Skipping new schedule.");
            return;
        }
        as_schedule_single_action(
            time() + 10,
            'aiccgen_do_actual_category_processing',
            $args,
            'aiccgen_content_generation_tasks'
        );
        my_plugin_log("[AI Cat Gen AS Scheduler] Successfully scheduled Action Scheduler task 'aiccgen_do_actual_category_processing' for Cat ID {$category_id}.");
    } else {
        my_plugin_log("[AI Cat Gen AS Scheduler] CRITICAL ERROR: Action Scheduler function 'as_schedule_single_action' NOT FOUND. Cat ID {$category_id} processing cannot be scheduled via AS.");
    }
}
add_action(AICCG_GOOGLE_CRON_HOOK, 'aiccgen_google_schedule_category_action_for_as', 10, 1);

// --- Load Action Scheduler Library if not already loaded ---
function aiccgen_maybe_load_action_scheduler() {
    if ( ! class_exists( 'ActionScheduler_Versions' ) && ! class_exists( 'ActionScheduler' ) ) {
        $as_library_path = plugin_dir_path( __FILE__ ) . 'lib/action-scheduler/action-scheduler.php'; 
        if ( file_exists( $as_library_path ) ) {
            require_once $as_library_path;
        } else {
            
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__( 'AI Category Content Generator requires the Action Scheduler library, but it was not found. Please install it or ensure it is active.', 'ai-cat-content-gen-google' );
                echo '</p></div>';
            });
            my_plugin_log('AI Cat Content Generator: Action Scheduler library not found at ' . $as_library_path);
        }
    }
}
add_action( 'plugins_loaded', 'aiccgen_maybe_load_action_scheduler', 5 ); // Load early

// --- Action Scheduler Worker for Actual Category Processing ---
function aiccgen_handle_actual_category_processing_action($category_id) {
    $category_id = absint($category_id); // Passed as first element of $args array by AS typically
    my_plugin_log("[AI Cat Gen AS Worker] Starting actual processing for Cat ID {$category_id} via Action Scheduler.");
    @ini_set('memory_limit', '512M');
    @set_time_limit(25 * 60); // Request 25 minutes execution time

    // --- Step 1: Initial Setup and Validation ---
    if ($category_id === 0) { // Ensure this check is early
        my_plugin_log("[AI Cat Gen AS Worker] Error: Received invalid category ID 0 for processing.");
        throw new Exception("Invalid category ID 0 for AS Worker."); // Fail the action
    }
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    if (!$options) {
        my_plugin_log("[AI Cat Gen AS Worker] Error: Plugin options (AICCG_GOOGLE_OPTION_NAME) not found for Cat ID {$category_id}.");
        throw new Exception("Plugin options not found for AS Worker Cat ID {$category_id}.");
    }

    // ... (all your option fetching: google_api_key, venice_api_key, model, prompts, etc.)
    $google_api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
    $venice_api_key = isset($options['venice_api_key']) ? trim($options['venice_api_key']) : '';
    $model = isset($options['model']) && !empty(trim($options['model'])) ? $options['model'] : 'gemini-2.5-flash';
    $prompts = isset($options['prompts']) && is_array($options['prompts']) ? $options['prompts'] : [];
    $image_prompts = isset($options['image_prompts']) && is_array($options['image_prompts']) ? $options['image_prompts'] : [];
    $formatting_instructions_map = isset($options['formatting_instructions']) && is_array($options['formatting_instructions']) ? $options['formatting_instructions'] : [];
    $global_formatting_instructions = isset($options['global_formatting_instructions']) ? trim($options['global_formatting_instructions']) : '';
    $category = get_category($category_id);
    $prompt_text_for_cat = isset($prompts[$category_id]) ? trim($prompts[$category_id]) : '';

    $is_valid_for_run = true;
    $validation_error_reason = '';
    if (empty($google_api_key)) { $is_valid_for_run = false; $validation_error_reason = 'Missing Google API Key in settings.'; }
    if (!$category) { $is_valid_for_run = false; $validation_error_reason = 'Category not found.'; }
    if (empty($prompt_text_for_cat)) { $is_valid_for_run = false; $validation_error_reason = 'Missing Content Prompt for this category.'; }

    if (!$is_valid_for_run) {
        $error_msg = "[AI Cat Gen AS Worker] Validation failed for Cat ID {$category_id}: {$validation_error_reason}.";
        my_plugin_log($error_msg);
        throw new Exception($error_msg); // Fail the AS action
    }

    $category_name = $category->name;
    $image_prompt_text_for_cat = isset($image_prompts[$category_id]) ? trim($image_prompts[$category_id]) : '';
    $category_specific_formatting = isset($formatting_instructions_map[$category_id]) ? trim($formatting_instructions_map[$category_id]) : '';
    $effective_formatting_instructions = !empty($category_specific_formatting) ? $category_specific_formatting : $global_formatting_instructions;


    // --- Step 2: Generate Google Content ---
    my_plugin_log("[AI Cat Gen AS Worker] Starting Google content generation for Cat ID {$category_id} ('{$category_name}').");
    $final_google_prompt = aiccgen_google_build_api_prompt($category_name, $prompt_text_for_cat, $effective_formatting_instructions);
    $generation_response = aiccgen_google_call_gemini_api($google_api_key, $model, $final_google_prompt);

    if (!$generation_response['success']) {
        $google_error = isset($generation_response['error']) ? esc_html($generation_response['error']) : 'Unknown Google API error.';
        $error_msg = "[AI Cat Gen AS Worker] Google Content generation FAILED for Cat ID {$category_id}: {$google_error}";
        my_plugin_log($error_msg);
        throw new Exception($error_msg);
    }
    $generated_content_from_google = $generation_response['content'];
    if (empty(trim($generated_content_from_google))) {
        $error_msg = "[AI Cat Gen AS Worker] Google Content generation for Cat ID {$category_id} resulted in empty content.";
        my_plugin_log($error_msg);
        throw new Exception($error_msg);
    }
    my_plugin_log("[AI Cat Gen AS Worker] Google content generated successfully for Cat ID {$category_id}. Length: " . strlen($generated_content_from_google));

    // --- Step 3: WordAI Processing with Locking ---
    $content_to_post = $generated_content_from_google;
    $wordai_api_key = isset($options['wordai_api_key']) ? trim($options['wordai_api_key']) : '';
    $wordai_email = isset($options['wordai_email']) ? trim($options['wordai_email']) : '';
    $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_NOT_ATTEMPTED;
    $wordai_status_message_for_log = '';
    $should_attempt_wordai = !empty($wordai_api_key) && !empty($wordai_email) && !empty(trim($generated_content_from_google));

    if ($should_attempt_wordai) {
        my_plugin_log("[AI Cat Gen AS Worker] Attempting to acquire WordAI lock for Cat ID {$category_id}.");
        if (get_transient(AICCG_WORDAI_CRON_LOCK_NAME)) {
            $error_msg = "[AI Cat Gen AS Worker] WordAI lock for Cat ID {$category_id} is BUSY. Action will be retried by Action Scheduler.";
            my_plugin_log($error_msg);
            throw new Exception($error_msg); // Cause AS to retry this action later
        } else {
            set_transient(AICCG_WORDAI_CRON_LOCK_NAME, $category_id, AICCG_WORDAI_CRON_LOCK_DURATION);
            my_plugin_log("[AI Cat Gen AS Worker] WordAI lock ACQUIRED by Cat ID {$category_id}. Processing WordAI.");
            try {
                $wordai_response = aiccgen_call_wordai_api($generated_content_from_google, $wordai_email, $wordai_api_key);
                
                if ($wordai_response['success']) {
                    if (!empty(trim($wordai_response['rewritten_text']))) {
                        if (isset($wordai_response['wordai_result_empty']) && $wordai_response['wordai_result_empty'] === true) {
                            $wordai_status_message_for_log = 'WordAI processing resulted in empty text; using original Google content.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                        } elseif (isset($wordai_response['skipped_wordai']) && $wordai_response['skipped_wordai'] === true) {
                            $wordai_status_message_for_log = 'WordAI skipped as input was effectively empty after cleaning; using original Google content.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
                        } else {
                            $content_to_post = $wordai_response['rewritten_text'];
                            $wordai_status_message_for_log = 'Content successfully rewritten by WordAI.';
                            $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SUCCESS;
                        }
                    } else {
                        $wordai_status_message_for_log = 'WordAI returned an empty rewrite; using original Google content.';
                        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_RESULT_EMPTY;
                    }
                } else {
                    $wordai_api_error = isset($wordai_response['error']) ? esc_html($wordai_response['error']) : 'Unknown WordAI API error.';
                    $wordai_status_message_for_log = "WordAI rewrite failed: {$wordai_api_error}. Using original Google content.";
                    $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_API_ERROR;
                    my_plugin_log("[AI Cat Gen AS Worker/Error] WordAI rewrite FAILED for Cat ID {$category_id} - {$wordai_api_error}");
                    // For now, proceed with Google content if WordAI fails, but log it. If WordAI is critical, throw new Exception here.
                }
            } catch (Exception $e) {
                my_plugin_log("[AI Cat Gen AS Worker/Exception] Exception during WordAI processing for Cat ID {$category_id}: " . $e->getMessage());
                $wordai_status_message_for_log = 'Exception during WordAI processing. Using original Google content.';
                $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_API_ERROR; // Or a new status like 'exception'
            } finally {
                my_plugin_log("[AI Cat Gen AS Worker] ENTERING finally block for WordAI lock release - Cat ID {$category_id}.");
                delete_transient(AICCG_WORDAI_CRON_LOCK_NAME);
                my_plugin_log("[AI Cat Gen AS Worker] WordAI lock RELEASED by Cat ID {$category_id} (from finally block). WordAI Status for log: {$wordai_status_message_for_log}");
            }
        }
    } elseif (empty(trim($generated_content_from_google))) {
        $wordai_status_message_for_log = 'WordAI rewrite skipped: Original Google content was empty.';
        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT;
    } else {
        $wordai_status_message_for_log = 'WordAI rewrite skipped: API Key or Email missing in settings.';
        $wordai_meta_status_for_post = AICCG_WORDAI_STATUS_SKIPPED_CONFIG;
    }
    if ($wordai_meta_status_for_post !== AICCG_WORDAI_STATUS_NOT_ATTEMPTED) {
         my_plugin_log("[AI Cat Gen AS Worker] WordAI processing for Cat ID {$category_id} - Result: {$wordai_status_message_for_log}");
    }

    // --- Step 4: Image Generation (Optional) ---
    $generated_image_id_for_post = null;
    $image_generation_status_log_msg = 'Image: Not attempted or no prompt.';
    if (!empty($image_prompt_text_for_cat)) {
        if (!empty($venice_api_key)) {
            my_plugin_log("[AI Cat Gen AS Worker] Starting Venice image generation for Cat ID {$category_id}.");
            $image_result = aiccgen_google_generate_venice_image($venice_api_key, $image_prompt_text_for_cat);
            if ($image_result['success'] && isset($image_result['attachment_id'])) {
                $generated_image_id_for_post = $image_result['attachment_id'];
                $image_generation_status_log_msg = 'Image: Generated successfully (ID: ' . $generated_image_id_for_post . ').';
                my_plugin_log("[AI Cat Gen AS Worker] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
            } else {
                $venice_error = isset($image_result['error']) ? esc_html($image_result['error']) : 'Unknown Venice API error.';
                $image_generation_status_log_msg = "Image: Generation failed: {$venice_error}.";
                my_plugin_log("[AI Cat Gen AS Worker/Error] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
                // Optional: throw new Exception($image_generation_status_log_msg);
            }
        } else {
            $image_generation_status_log_msg = 'Image: Skipped, Venice AI API key missing.';
            my_plugin_log("[AI Cat Gen AS Worker] " . $image_generation_status_log_msg . " for Cat ID {$category_id}");
        }
    }

    // --- Step 5: Create Draft Post ---
    $post_title_prefix = __('Scheduled Draft:', 'ai-cat-content-gen-google');
    $post_title_for_new_post = sprintf('%s %s - %s', $post_title_prefix, $category_name, date_i18n(get_option('date_format') . ' H:i'));

    my_plugin_log("[AI Cat Gen AS Worker] Attempting to create draft post for Cat ID {$category_id} with title '{$post_title_for_new_post}'.");
    $create_post_result = aiccgen_google_create_draft_post(
        $category_id,
        $post_title_for_new_post,
        $content_to_post,
        $generated_image_id_for_post
    );

    if ($create_post_result['success'] && isset($create_post_result['post_id'])) {
        $new_post_id = $create_post_result['post_id'];
        update_post_meta($new_post_id, AICCG_GOOGLE_WORDAI_STATUS_META_KEY, $wordai_meta_status_for_post);
        
        $date_slug_part = date_i18n('Y-m-d');
        update_post_meta($new_post_id, '_aiccgen_generation_date_marker', $date_slug_part);


        $final_log_message = sprintf(
            'Draft post created via AS for Cat ID %d. Post ID: %d. WordAI: %s. %s',
            $category_id,
            $new_post_id,
            $wordai_meta_status_for_post,
            $image_generation_status_log_msg
        );
        my_plugin_log("[AI Cat Gen AS Worker/Success] " . $final_log_message);
    } else {
        $post_creation_error = isset($create_post_result['error']) ? esc_html($create_post_result['error']) : 'Unknown post creation error.';
        $error_msg = "[AI Cat Gen AS Worker/Error] Post creation FAILED for Cat ID {$category_id}: {$post_creation_error}";
        my_plugin_log($error_msg);
        if ($generated_image_id_for_post) {
            wp_delete_attachment($generated_image_id_for_post, true);
        }
        throw new Exception($error_msg);
    }
    my_plugin_log("[AI Cat Gen AS Worker] Finished actual processing for Cat ID {$category_id}.");
}
add_action('aiccgen_do_actual_category_processing', 'aiccgen_handle_actual_category_processing_action', 10, 1);

// Check if Google API key exceeded or expired
add_action('wp_ajax_aiccgen_google_check_api_key', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'ai-cat-content-gen-google')], 403);
    }
    $options = get_option(AICCG_GOOGLE_OPTION_NAME);
    $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
    if (empty($api_key)) {
        wp_send_json_error(['message' => __('API key is missing in settings.', 'ai-cat-content-gen-google')], 400);
    }
    $test_prompt = 'Say "Hello World" in HTML.';
    $model = 'gemini-2.5-flash';
    $result = aiccgen_google_call_gemini_api($api_key, $model, $test_prompt);

    if (!$result['success']) {
        // Check for quota or expired key
        if (
            stripos($result['error'], 'quota') !== false ||
            stripos($result['error'], 'exceeded') !== false ||
            stripos($result['error'], 'API key not valid') !== false ||
            stripos($result['error'], 'API key expired') !== false
        ) {
            wp_send_json_error(['message' => $result['error']], 200);
        }
        wp_send_json_error(['message' => $result['error']], 200);
    }
    wp_send_json_success(['message' => __('API key is valid.', 'ai-cat-content-gen-google')]);
});

?>
