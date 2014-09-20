jQuery.noConflict();

jQuery(document).ready(function() {
    
    jQuery('#news-clear-text').click(function(event){
        event.preventDefault();
        CKEDITOR.instances['ckeditor'].setData('');
    });
    jQuery('#news-clear-text2').click(function(event){
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].setData('');
    });
    
    jQuery('.img-to-text').click(function(event){
        event.preventDefault();
        var id = jQuery(this).attr('value');
        CKEDITOR.instances['ckeditor'].insertText('(!photo_'+id+'!)');
    });
});