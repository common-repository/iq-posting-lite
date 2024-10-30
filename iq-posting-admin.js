function isURL(str) {
    var pattern = new RegExp(/^(?:(?:https?|ftp):\/\/)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/\S*)?$/);
    return pattern.test(str);
}

jQuery(document).ready(function ($) {

    $("#iq-posting-link").on('click', function (e) {
        e.preventDefault();

        var featured = $('#iq_posting_image:checked').val(),
            text = $('#iq_posting_text:checked').val(),
            link = $('input[name="iq_posting_link"]:checked').val(),
            url = $('#iq_posting_url').val(),
            nonce = $('#postingiq_nonce').val(),
            is_https = isURL(url);

        if ($('.iq_posting_error').length) $('.iq_posting_error').remove();
        $(this).prop('disabled', true).text('Posting...');

        if (!featured && !text) {
            $('#iq-posting-meta-box .inside').append('<span class="iq_posting_error">You need to check at least one option</span>');
            $('#iq-posting-link').prop('disabled', false).text('Post Link');
            return false
        }

        if (is_https === false) {
            $('#iq-posting-meta-box .inside').append('<span class="iq_posting_error">You need to enter a valid link</span>');
            $('#iq-posting-link').prop('disabled', false).text('Post Link');
            return false;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "iq_posting_action",
                nonce: nonce,
                post_id: $("#post_ID").val(),
                url: url,
                link: link
            },
            success: function (response) {
                console.log(response);
                if (response.success == true) {

                    var attachment_id = response.data.output.attachment_id,
                        attachment_src = response.data.output.attachment_src,
                        title = response.data.output.title,
                        description = response.data.output.description,
                        content = '',
                        content_output = '',
                        url = response.data.output.url;

                    if (!description || description == 'undefined') {
                        content += title
                    } else {
                        content += title + ' &mdash; ' + description
                    }

                    if (text === 'yes' && (title || description)) {

                        if (link === 'none') {
                            content_output += content;
                        } else {

                            var a = document.createElement('a');
                            a.href = url;

                            content_output +=
                                '<div class="iq-posting" data-url="' + url + '" data-link="' + link + '">' +
                                '<p>' + content + ' <span class="iq-posting-source">' + a.hostname + '</span></p>' +
                                '<p><a class="iq-posting-link" href="' + url + '">Read More</a></p>' +
                                '</div>&nbsp;';
                        }

                        if (!tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
                            jQuery('textarea#content').val(jQuery('textarea#content').val() + content_output);
                        } else {
                            tinymce.get('content').execCommand('mceInsertContent', false, content_output);
                        }
                    }

                    if (!title && !description)
                        $('#iq-posting-meta-box .inside').append('<span class="iq_posting_error">Could not get title and description</span>');

                    if (!attachment_id)
                        $('#iq-posting-meta-box .inside').append('<span class="iq_posting_error">Could not find any image</span>');


                    if (featured === 'yes' && attachment_id) {
                        $("#_thumbnail_id").val(attachment_id);

                        if (attachment_src) {
                            $('#postimagediv .inside').find('p').each(function () {
                                $(this).remove();
                            });

                            $('#postimagediv .inside').prepend('<p><a href="https://intermittentfastingandketo.com/wp-admin/media-upload.php?post_id=' + attachment_id + '&amp;type=image&amp;TB_iframe=1" id="set-post-thumbnail" aria-describedby="set-post-thumbnail-desc" class="thickbox">' +
                                '<img src="' + attachment_src + '" alt="" width="266" height="auto"></a></p><p><a href="#" id="remove-post-thumbnail">Remove featured image' +
                                '</a></p>');
                        }

                    }
                } else {
                    $('#iq-posting-meta-box .inside').append('<span class="iq_posting_error">' + response.data.output.error + '</span>');
                }

                $('#iq-posting-link').prop('disabled', false).text('Post Link');

            }
        })

    });

    $('#iq_posting_text').on('change', function () {
        if (jQuery(this).is(':checked')) {
            jQuery('input[name="iq_posting_link"]').prop('disabled', false);
        } else {
            jQuery('input[name="iq_posting_link"]').prop('disabled', true);
        }
    });

    if (typeof wp.media != "undefined") {

        var wpMediaFramePost = wp.media.view.MediaFrame.Post;
        wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
            {
                mainInsertToolbar: function (view) {
                    "use strict";

                    wpMediaFramePost.prototype.mainInsertToolbar.call(this, view);

                    var controller = this;

                    this.selectionStatusToolbar(view);

                    view.set("insertOpengraph", {
                        style: "secondary",
                        priority: 80,
                        text: "Insert OpenGraph",
                        requires: {
                            selection: true
                        },

                        click: function () {
                            var description = jQuery('.compat-field-iq-posting-description textarea').val(),
                                title = jQuery('.compat-field-iq-posting-title input').val(),
                                url = jQuery('.compat-field-iq-posting-url input').val(),
                                site_name = jQuery('.compat-field-iq-posting-sitename input').val(),
                                content = title + ' &mdash; ' + description;

                            var content_output = '<div class="iq-posting" data-url="' + url + '" data-link="link">' +
                                '<p>' + content + ' <span class="iq-posting-source">' + site_name + '</span></p>' +
                                '<p><a class="iq-posting-link" href="' + url + '">Read More</a></p>' +
                                '</div>';

                            if (title || description) {
                                send_to_editor(content_output);
                            } else {
                                jQuery('.opengraphError').remove();
                                jQuery('.media-button-insertOpengraph').after('<span class="opengraphError" style="display: inline-block;margin-top: 25px;z-index: 1000;position: relative;">No OpenGraph found</span>');
                                jQuery('.opengraphError').fadeOut(3000);
                                setTimeout(function () {
                                    jQuery('.opengraphError').remove()
                                }, 3000);
                            }
                        }
                    });
                }
            });
    }

    var colorPickerOptions = {
        // a callback to fire whenever the color changes to a valid color
        change: function(event, ui){
            if($(this).hasClass('iq-pop-color')) {
                var color = ui.color.toString();
                $('.popup-iq-overlay-after').css('color', color);
            }
            if($(this).hasClass('iq-pop-bg')) {
                var color = ui.color.toString();
                $('.popup-iq-overlay-before').css('background', color);
            }
        }
    };

    $( '.iq-color-picker' ).wpColorPicker(colorPickerOptions);

    $('.iq-alpha').on('change', function(){
        $('.popup-iq-overlay-before').css('opacity', $(this).val())
    });

});