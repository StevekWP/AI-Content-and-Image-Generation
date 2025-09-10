jQuery(document).ready(function ($) {
    var vars = window.aiccgen_post_edit_vars || {};
    var $wrapper = $('#aiccgen-post-refine-image-wrapper');

    if (!$wrapper.length) {
        return;
    }

    var $button = $wrapper.find('#aiccgen-post-refine-image-button');
    var $promptTextarea = $wrapper.find('#aiccgen-post-image-prompt');
    var $loader = $wrapper.find('#aiccgen-post-refine-loader');
    var $status = $wrapper.find('#aiccgen-post-refine-status');
    var $optionsArea = $wrapper.find('#aiccgen-post-image-options-area');

    var currentPostId = $('input#post_ID').val();
    var allGeneratedImageIds = [];

    //$button.after($status);


    if (!vars.venice_api_key_exists) {
        $promptTextarea.prop('disabled', true);
        $button.prop('disabled', true);
        return;
    }

    $button.on('click', function () {
        
        $status.html('').removeClass('notice-success notice-error notice-warning');
        $optionsArea.html('').hide();
        allGeneratedImageIds = [];

        var imagePrompt = $promptTextarea.val().trim();
        if (!imagePrompt) {
            $status.text(vars.text_no_prompt).addClass('notice notice-error inline');
            $promptTextarea.focus();
            return;
        }

        $button.prop('disabled', true);
        $promptTextarea.prop('disabled', true);
        $loader.show();
        $status.text(vars.text_generating_options).addClass('notice notice-info inline');

        $.ajax({
            url: vars.ajax_url,
            type: 'POST',
            data: {
                action: vars.ajax_post_refine_image_action,
                _ajax_nonce: vars.nonce,
                post_id: currentPostId,
                image_prompt: imagePrompt
            },
            success: function (response) {
                if (response.success) {
                    allGeneratedImageIds = response.data.generated_images.map(img => img.attachment_id);
                    displayImageOptions(response.data.generated_images);
                    $status.text(vars.text_options_generated.replace('%d', response.data.generated_images.length))
                           .removeClass('notice-info notice-error')
                           .addClass('notice-success inline');
                } else {
                    $status.text(response.data.message || vars.text_api_error).addClass('notice notice-error inline');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error (Generate Image Options):", textStatus, errorThrown, jqXHR.responseText);
                $status.text(vars.text_ajax_error).addClass('notice notice-error inline');
            },
            complete: function () {
                $loader.hide();
                $button.prop('disabled', false);
                $promptTextarea.prop('disabled', false);
                
            }
        });
    });

    

    function displayImageOptions(images) {
        if (!images || images.length === 0) {
            $status.text(vars.text_all_failed).addClass('notice notice-error inline');
            $optionsArea.hide();
            return;
        }

        var html = '<div class="aiccgen-image-options-container">';
        images.forEach(function (img, index) {
            html += '<div class="aiccgen-image-option">';
            html += '<input type="radio" name="aiccgen_selected_image" value="' + img.attachment_id + '" id="aiccgen_img_' + img.attachment_id + '">';
            html += '<label for="aiccgen_img_' + img.attachment_id + '"><img src="' + escapeHtml(img.image_url) + '" alt="Option ' + (index + 1) + '"></label>';
            html += '</div>';
        });
        html += '</div>';
        html += '<p><button type="button" id="aiccgen-apply-selected-image-button" class="button button-primary">' + vars.text_apply_selected + '</button></p>';
        
        $optionsArea.html(html).slideDown();
    }

    $optionsArea.on('click', '.aiccgen-image-option', function() {
        $(this).closest('.aiccgen-image-options-container').find('.aiccgen-image-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });

    $optionsArea.on('click', '#aiccgen-apply-selected-image-button', function () {
        var $applyButton = $(this);
        var selectedImageId = $optionsArea.find('input[name="aiccgen_selected_image"]:checked').val();

        if (!selectedImageId) {
            alert(vars.text_select_an_image);
            return;
        }

        if (!confirm(vars.text_confirm_apply)) {
            return;
        }

        $applyButton.prop('disabled', true);
        $status.text('Applying...').removeClass('notice-error notice-success').addClass('notice-info inline');

        $.ajax({
            url: vars.ajax_url,
            type: 'POST',
            data: {
                action: vars.ajax_post_apply_image_action,
                _ajax_nonce: vars.nonce,
                post_id: currentPostId,
                selected_image_id: selectedImageId,
                all_new_image_ids: allGeneratedImageIds
            },
            success: function (response) {
                if (response.success) {
                    $status.html(response.data.message || vars.text_applied_success).addClass('notice-success inline');
                    $optionsArea.slideUp(function() { $(this).empty(); });
                    if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
                         wp.media.featuredImage.set(response.data.new_thumbnail_id);
                    } else {
                        if (response.data.new_thumbnail_html) {
                            $('#postimagediv .inside').html(response.data.new_thumbnail_html);
                        } else {
                             // If HTML isn't provided, just reload. Crude but might work.
                             // window.location.reload();
                        }
                    }
                    
                    // Clear the prompt after successful application
                    // $promptTextarea.val('');
                } else {
                    $status.text(response.data.message || vars.text_apply_failed).addClass('notice notice-error inline');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                 console.error("AJAX Error (Apply Image):", textStatus, errorThrown, jqXHR.responseText);
                $status.text(vars.text_ajax_error).addClass('notice notice-error inline');
            },
            complete: function () {
                $applyButton.prop('disabled', false);
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