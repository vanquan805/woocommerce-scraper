$ = jQuery;

$(function () {
    // event click tab menu
    $(document).on('click', '#ws-scraper-info .nav-tab-wrapper .nav-tab', function (e) {
        if (!$(this).hasClass('active')) {
            let target = $(this).data('target');
            $(`.nav-tab-wrapper .nav-tab.nav-tab-active`).removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $(`.tab-content .tab-item.active`).removeClass('active');
            $(`.tab-content ${target}`).addClass('active');
        }
    });

    $(document).on('click', '.ws-add-attribute', function (e){
            let parent = $(this).closest('#ws-scraper-metabox-attributes');
            let listElement = $(parent).find('.wrap-attribute-list');
            let lastForm = $(listElement).find('.form-table:last-child');
            let index = $(lastForm).data('index');
            let attribute_form = $(woocommerce_scraper.attribute_form_template.replace(/\{index\}/gi, index+1)).clone();
            $(listElement).append('<hr>');
            $(listElement).append(attribute_form);
    });
});