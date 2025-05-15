jQuery(document).ready(function($) {
    console.log('Local SEO Admin Script Loaded'); // Debugging

    function localSeoToggleBusinessFields() {
        var businessType = $('#business_type').val();
        console.log('Selected Business Type:', businessType); // Debugging

        // Hide all business-specific fields
        $('.business-specific-field').hide();

        // Show fields for the selected business type
        $('.' + businessType + '-field').show();

        // Show WooCommerce fields only if WooCommerce is active
        if (businessType === 'Store' && typeof woocommerce_params !== 'undefined') {
            $('.Store-field').show();
        }
    }

    // Initial toggle on page load
    localSeoToggleBusinessFields();

    // Toggle fields when business type changes
    $('#business_type').change(localSeoToggleBusinessFields);
});