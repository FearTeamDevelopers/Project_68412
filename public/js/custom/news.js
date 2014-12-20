jQuery.noConflict();

jQuery(document).ready(function () {
    jQuery('.nosubmit').submit(function(event){
        event.preventDefault();
        return false;
    });
    
    jQuery('#text-to-teaser').click(function (event) {
        event.preventDefault();
        var value = CKEDITOR.instances['ckeditor'].getData();
        var short = value.substr(0, 240);
        CKEDITOR.instances['ckeditor2'].setData(short);
    });

    jQuery('#teaser-to-meta').click(function (event) {
        event.preventDefault();
        var value = CKEDITOR.instances['ckeditor2'].getData();
        var short = value.substr(0, 250);
        jQuery('textarea[name=metadescription]').val(short);
    });

    jQuery('#clear-text').click(function (event) {
        event.preventDefault();
        CKEDITOR.instances['ckeditor'].setData('');
    });

    jQuery('#clear-teaser').click(function (event) {
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].setData('');
    });

    jQuery('#teaser-readmore-link').click(function (event) {
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].insertText('<a href="(!read_more_link!)">(!read_more_title!)</a>');
    });

    jQuery('#text-new-paragraph').click(function (event) {
        event.preventDefault();
        CKEDITOR.instances['ckeditor'].insertText('<br class="clear" />');
    });

    jQuery('#teaser-new-paragraph').click(function (event) {
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].insertText('<br class="clear" />');
    });

    jQuery('#text-link-to-gallery, #teaser-link-to-gallery').click(function (event) {
        event.preventDefault();
        var type = jQuery(this).attr('id');

        jQuery('#insert-dialog p').load('/admin/gallery/inserttocontent/');

        jQuery('#insert-dialog').dialog({
            title: 'Insert',
            width: 600,
            modal: true,
            buttons: {
                'Insert': function () {
                    var src = jQuery('#content').val();
                    var target = jQuery('#link-target').val();
                    var name = jQuery('#link-name').val();
                    var tag = "<a href=\"/galerie/r/" + src + "\" target=" + target + ">" + name + "</a>";

                    if (type.substr(0, 4) == 'text') {
                        CKEDITOR.instances['ckeditor'].insertText(tag);
                    } else {
                        CKEDITOR.instances['ckeditor2'].insertText(tag);
                    }

                    jQuery(this).dialog('close');
                },
                Close: function () {
                    jQuery(this).dialog('close');
                }
            }
        });
        return false;
    });
    
    jQuery('#text-link-to-news, #teaser-link-to-news').click(function (event) {
        event.preventDefault();
        var type = jQuery(this).attr('id');

        jQuery('#insert-dialog p').load('/admin/news/inserttocontent/');

        jQuery('#insert-dialog').dialog({
            title: 'Insert',
            width: 600,
            modal: true,
            buttons: {
                'Insert': function () {
                    var src = jQuery('#content').val();
                    var target = jQuery('#link-target').val();
                    var name = jQuery('#link-name').val();
                    var tag = "<a href=\"/aktuality/r/" + src + "\" target=" + target + ">" + name + "</a>";

                    if (type.substr(0, 4) == 'text') {
                        CKEDITOR.instances['ckeditor'].insertText(tag);
                    } else {
                        CKEDITOR.instances['ckeditor2'].insertText(tag);
                    }

                    jQuery(this).dialog('close');
                },
                Close: function () {
                    jQuery(this).dialog('close');
                }
            }
        });
        return false;
    });
});