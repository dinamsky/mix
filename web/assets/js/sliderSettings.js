$( document ).ready(function() {
    $(".owl-carousel-all").each(function() {
        var items = $(this).data('items');
        var dots = $(this).data('dots');
        var full = $(this).data('full');
        var fnc = '';
        if (dots === 0) {
            dots = false;
            fnc = 'recount';
        } else {
            dots = true;
            fnc = '';
        }
        var st_padding = 50;
        if (full){
            st_padding = 0;
        }
        var margin = 0;
        if(items>1) margin = 20;
        $(this).owlCarousel({
            'nav': true,
            'margin' : margin,
            'slideBy' : items,
            'navText': ['<i uk-icon="icon:chevron-left; ratio: 2"></i>', '<i uk-icon="icon:chevron-right; ratio: 2"></i>'],
            // 'autoHeight': true,
            'dots': dots,
            onInitialized: recount(fnc),
            responsive:{
                0:{
                    items:1,
                    stagePadding:st_padding,
                    margin:20
                },
                600:{
                    items:items
                },
                1000:{
                    items:items
                },
                2000:{
                    items:items
                }
            }

        });
    });

    function recount(fnc)
    {
        // if (fnc === 'recount') $(".owl-carousel-all").find('div.owl-item').height($(".owl-carousel").height());
    }

});
