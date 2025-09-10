jQuery(document).ready(function ($) {
    
    var vars = window.aiccgen_post_editor_refine_vars || {};

    var $wrapper = $('#aiccgen-post-editor-refine-wrapper');
    if (!$wrapper.length) {
        return;
    }

    var $button = $wrapper.find('#aiccgen-post-editor-refine-button');
    var $instructionsTextarea = $wrapper.find('#aiccgen-post-editor-refine-instructions');
    var $refinementTypeDropdown = $wrapper.find('#aiccgen-post-editor-refine-type');
    var $loader = $wrapper.find('#aiccgen-post-editor-refine-loader');
    var $status = $wrapper.find('#aiccgen-post-editor-refine-status');

    var currentPostId = $('input#post_ID').val();
    var originalInstructionsPlaceholder = vars.text_refine_instructions_placeholder || 'e.g., "Make it more concise"...';

    if (!vars.google_api_key_exists) {
        $instructionsTextarea.prop('disabled', true);
        $refinementTypeDropdown.prop('disabled', true);
        $button.prop('disabled', true);
        return;
    }

    $button.on('click', function () {
        var refinementInstructions = $instructionsTextarea.val().trim();
        var refinementType = $refinementTypeDropdown.val();
        var originalContent = '';

        if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
            originalContent = tinymce.get('content').getContent();
        } else if ($('#content').length && $('#content').is(':visible')) {
            originalContent = $('#content').val();
        } else { 
            originalContent = wp.data.select("core/editor") ? wp.data.select("core/editor").getEditedPostAttribute('content') : '';
            if (!originalContent && $('#content').length) originalContent = $('#content').val();
        }
        
        $status.html('').removeClass('notice-success notice-error notice-warning notice-info inline');

        if (!refinementType) {
            $status.text(vars.text_error_no_refinement_type).addClass('notice notice-error inline');
            $refinementTypeDropdown.focus();
            return;
        }

        // Instructions are mandatory ONLY for 'refine' type
        if (refinementType === 'refine' && !refinementInstructions) {
            $status.text(vars.text_error_no_instructions_for_refine).addClass('notice notice-error inline');
            $instructionsTextarea.focus();
            return;
        }

        // Original content is always needed, at least for topic context.
        if (!originalContent.trim()) {
            $status.text(vars.text_error_no_content).addClass('notice notice-warning inline');
            return;
        }

        $button.prop('disabled', true);
        $instructionsTextarea.prop('disabled', true);
        $refinementTypeDropdown.prop('disabled', true);
        $loader.show();
        
        var processingMessage = (refinementType === 'reresearch_refresh') ? 
                                vars.text_reresearching_content : 
                                vars.text_refining_content;
        $status.text(processingMessage).addClass('notice notice-info inline');

        $.ajax({
            url: vars.ajax_url,
            type: 'POST',
            data: {
                action: vars.ajax_action,
                _ajax_nonce: vars.nonce,
                post_id: currentPostId,
                original_content: originalContent,
                refinement_instructions: refinementInstructions, // Send even if empty for reresearch
                refinement_type: refinementType
            },
            success: function (response) {
                if (response.success && response.data && typeof response.data.refined_content !== 'undefined') {
                    if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
                        tinymce.get('content').setContent(response.data.refined_content);
                    } else if ($('#content').length && $('#content').is(':visible')) {
                        $('#content').val(response.data.refined_content);
                    } else { 
                         if (wp.data.dispatch && wp.data.dispatch("core/editor")) { // Check if dispatch exists
                            wp.data.dispatch("core/editor").editPost({ content: response.data.refined_content });
                         } else if ($('#content').length) {
                            $('#content').val(response.data.refined_content);
                         }
                    }
                    $status.text(vars.text_refine_success).removeClass('notice-info notice-warning notice-error').addClass('notice-success inline');
                } else {
                    var errorMsg = (response.data && response.data.message) ? response.data.message : vars.text_refine_api_error;
                    $status.text(errorMsg).removeClass('notice-info notice-success').addClass('notice-error inline');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error (Refine Editor Content):", {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                var errorMsg = vars.text_refine_ajax_error;
                 if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    errorMsg = jqXHR.responseJSON.data.message;
                }
                $status.text(errorMsg).removeClass('notice-info notice-success').addClass('notice-error inline');
            },
            complete: function () {
                $loader.hide();
                $button.prop('disabled', false);
                $instructionsTextarea.prop('disabled', false);
                $refinementTypeDropdown.prop('disabled', false);
            }
        });
    });

     function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
   }
});