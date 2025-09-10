<?php
//  Constants 
define('AICCG_GOOGLE_OPTION_GROUP', 'aiccgen_google_settings_group');
define('AICCG_GOOGLE_OPTION_NAME', 'aiccgen_google_options');
define('AICCG_GOOGLE_SETTINGS_SLUG', 'ai-content-generator');
// For Manual Post Creation
define('AICCG_GOOGLE_AJAX_ACTION', 'aiccgen_google_generate_content_ajax');
define('AICCG_GOOGLE_AJAX_REFINE_ACTION', 'aiccgen_google_refine_content_ajax');
define('AICCG_GOOGLE_NONCE_ACTION', 'aiccgen_google_generate_nonce');
define('AICCG_GOOGLE_AJAX_CREATE_POST_ACTION', 'aiccgen_google_create_post_ajax');
define('AICCG_GOOGLE_SETTINGS_NOTICE_TRANSIENT', 'aiccgen_google_save_notice');
define('AICCG_GOOGLE_AJAX_REFINE_DRAFT_ACTION', 'aiccgen_google_refine_latest_draft_ajax');
define('AICCG_GOOGLE_AJAX_REFINE_IMAGE_ACTION', 'aiccgen_google_refine_featured_image');
define('AICCG_GOOGLE_AJAX_APPLY_REFINED_IMAGE_ACTION', 'aiccgen_google_apply_refined_image');

// Refine Image Constants
define('AICCG_GOOGLE_AJAX_POST_REFINE_IMAGE_ACTION', 'aiccgen_google_post_refine_image');
define('AICCG_GOOGLE_AJAX_POST_APPLY_IMAGE_ACTION', 'aiccgen_google_post_apply_image');
define('AICCG_RECENTLY_CREATED_POSTS_TRANSIENT', 'aiccgen_recently_created_posts');
// Refine content Constants
define('AICCG_GOOGLE_AJAX_REFINE_POST_EDITOR_ACTION', 'aiccgen_google_refine_post_editor_content');
define('AICCG_GOOGLE_CRON_HOOK', 'aiccgen_google_scheduled_generation_event');
// AI Suggestion Constants for column
define('AICCG_GOOGLE_IS_REEDIT_META_KEY', '_aiccgen_is_reedit_from_post');

// WordAI Constants
define('AICCG_GOOGLE_WORDAI_STATUS_META_KEY', '_aiccgen_wordai_status');
define('AICCG_WORDAI_STATUS_SUCCESS', 'success');
define('AICCG_WORDAI_STATUS_SKIPPED_CONFIG', 'skipped_config'); // API key/email missing
define('AICCG_WORDAI_STATUS_SKIPPED_EMPTY_INPUT', 'skipped_empty_input'); // Google content was empty
define('AICCG_WORDAI_STATUS_API_ERROR', 'api_error');
define('AICCG_WORDAI_STATUS_RESULT_EMPTY', 'result_empty'); // API success, but WordAI returned nothing usable
define('AICCG_WORDAI_STATUS_NOT_ATTEMPTED', 'not_attempted'); // WordAI not enabled for this flow or content empty

define('AICCG_WORDAI_CRON_LOCK_NAME', 'aiccgen_wordai_cron_lock');
define('AICCG_WORDAI_CRON_LOCK_DURATION', 2 * 60); // Lock for 2 minutes WordAI cron duration
define('AICCG_WORDAI_CRON_RETRY_DELAY', 5 * 60);   // Reschedule for 5 minutes later

//  Load Text Domain 