(function($) {
    $(document).ready(function() {
        $('.field--name-field-apply-now').hide();
        //  drupalSettings.custom_workflow.ourCondition 
        if (drupalSettings.custom_workflow == 'yes'){
            $('.field--name-field-apply-now').show();
        }
    });
} (jQuery));