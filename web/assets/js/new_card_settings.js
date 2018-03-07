$( document ).ready(function() {

    $.ajax({
        url: '/ajax/getAllSubFields',
        type: 'POST',
        data: {generalTypeId:2},
        success: function(html){
            $('#subfields').html(html);
        }
    });

    $('#subfields').on('change', '.subFieldSelect', function(){
        var subId = $(this).children('option:selected').val();
        var fieldId = $(this).data('id');
        var element = $(this);
        $.ajax({
            url: '/ajax/getSubField',
            type: 'POST',
            data: {subId:subId, fieldId:fieldId},
            success: function(html){
                //console.log(html);
                if (html) {
                    $(element).attr('name', 'old').attr('class','old_select uk-select');
                    $(element).next('select').remove();
                    $(element).after(html);
                }
                else {
                    //
                }
            },
            error: function (response) {
                //console.log(response);
            }
        });
    });

    $('#show_map').on('click', function(){
        var uluru = {lat: $('#map').data('lat') - 0, lng: $('#map').data('lng') - 0};
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 12,
            center: uluru
        });
        var marker = new google.maps.Marker({
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP,
            position: uluru
        });

        marker.addListener('dragend', function (element) {
            console.log(marker.getPosition());
            var coords = marker.getPosition().toString();
            $('input[name="coords"]').val(coords.replace("(", "").replace(")", ""));
            var latlon = coords.replace("(", "").replace(")", "").split(',');
            $('#map').data('lat',latlon[0]);
            $('#map').data('lng',latlon[1]);
        });
    });

    $('#fill_dop_button').on('click', function(){
        $(this).parent().remove();
        $('#dop_fields').addClass('uk-animation-slide-right').removeAttr('hidden');
        UIkit.update(event = 'update');
    });

    $('.service_selector button').on('click', function(){
        $('input[name="serviceTypeId"]').val($(this).val());
        $('.service_selector button').removeClass('uk-button-primary').addClass('uk-button-default');
        $(this).removeClass('uk-button-default').addClass('uk-button-primary');
    });

    $('.newcard_mailcheck').on('click', function(){
        var check_email = $('input[name="check_email"]').val();
        if (!validateEmail(check_email)) {
            alert('Fill email correctly! Available: a-z 0-9 dot minus @');
            return false;
        } else {
            $(this).hide();
            $.ajax({
                url: '/user_checkmail', // user controller
                type: 'POST',
                data: {email: check_email},
                success: function (html) {
                    if (html === 'ok') {
                        $('input[name="email"]').val(check_email);
                        $('.check_block').hide();
                        $('#signin_block').removeClass('uk-hidden');
                    }
                    if (html === 'new') {
                        $('input[name="r_email"]').val(check_email);
                        $('.check_block').hide();
                        $('#signup_block').removeClass('uk-hidden');
                    }
                }
            });
        }
    });

    $('.continue_with_reg').on('click', function(){
        // var h = $('input[name="r_header"]').val();
        // var t = $('input[name="r_phone"]').val();
        // if(h!=='' && t!=='') {
            $(this).hide();
            $('.first_step').hide();
            $('.unknown').css('display', 'block');
             UIkit.update(event = 'update');
            // $('#signup_block').append('<hr>');
            // $('html, body').animate({
            //     scrollTop: $(".unknown").offset().top - 80
            // }, 1000);
        // } else {
        //     alert('Пожалуйста заполните телефон и имя/наименование!');
        // }
    });

    $('.newcard_continue').on('click', function(){
        var id = $(this).data('id');
        $(this).remove();
        $('#'+id).removeClass('uk-hidden');
        UIkit.update(event = 'update');
    });
});