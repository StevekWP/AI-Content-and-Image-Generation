jQuery(document).ready(function($) {
    $('#aiccgen-redraft-post-button').on('click', function() {
        alert('asdasdasdasd');
        if (!confirm(aiccgen_post_edit_redraft_vars.confirm_redraft)) {
            return false;
        }

        var $button = $(this);
        var postId = $button.data('postid');
        var nonce = $button.data('nonce');
        var $loader = $('#aiccgen-redraft-loader');
        var $statusMessage = $('#aiccgen-redraft-status-message');

        $button.prop('disabled', true);
        $loader.show();
        $statusMessage.empty().removeClass('notice notice-error notice-success');

        $.ajax({
            url: aiccgen_post_edit_redraft_vars.ajax_url,
            type: 'POST',
            data: {
                action: aiccgen_post_edit_redraft_vars.redraft_action,
                original_post_id: postId,
                _ajax_nonce: nonce
            },
            success: function(response) {
                $loader.hide();
                if (response.success) {
                    $statusMessage.text(aiccgen_post_edit_redraft_vars.success_redirect_message).addClass('notice notice-success').show();
                    if (response.data.redirect_url) {
                        // Disable further interaction and redirect
                        $('body').css('pointer-events', 'none'); // Prevent clicks during redirect
                        window.location.href = response.data.redirect_url;
                    } else {
                        // Fallback: should ideally not happen if redirect_url is always sent
                        alert(response.data.message || 'Redrafted successfully! Please refresh.');
                        $button.prop('disabled', false); // Re-enable if no redirect
                    }
                } else {
                    $statusMessage.text(aiccgen_post_edit_redraft_vars.error_message + (response.data.message ? ': ' + response.data.message : '')).addClass('notice notice-error').show();
                    $button.prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $loader.hide();
                $statusMessage.text(aiccgen_post_edit_redraft_vars.error_message + ': ' + textStatus + ' - ' + errorThrown).addClass('notice notice-error').show();
                console.error("Redraft AJAX error:", jqXHR.responseText);
                $button.prop('disabled', false);
            }
        });
    });
});