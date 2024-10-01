(function ($) {

    var $form = $('form.checkout'),
        $body = $('body'),
        imoje_active = 'imoje-active',
        imoje_c_active = 'imoje-c-active',
        imoje_ms_speed_animation = 250,
        imoje_is_rendered_installments = false,
        imoje_is_passed_installments = false,
        imoje_installments_payment_method = 'imoje_installments';

    // catch an installment message
    window.addEventListener('message', function (data) {
        if (data.data?.channel && data.data.period) {
            imoje_is_passed_installments = true;
            $('#imoje-selected-channel').val(imoje_installments_payment_method + '-' + data.data.channel)
            $('#imoje-installments-period').val(data.data.period)
        }
    }, false);

    $body.on('click', '.imoje-channels li.imoje-channel label', function () {

        var $that = $(this);
        $('.imoje-channels label').removeClass(imoje_active);

        if ($that.parent().attr('class').includes('imoje_twisto')) {
            $('.imoje-twisto-regulations').show();
        }
        if ($that.hasClass(imoje_c_active)) {
            $that.addClass(imoje_active);
        }
    });

    $body.on('click', '.imoje-channels .imoje-channel', function () {

        if (!$(this).attr('class').includes('imoje_twisto') && !$(this).attr('class').includes('imoje-c-no-active')) {
            $('.imoje-twisto-regulations').hide();
        }

        $('.pbl-error').slideUp(imoje_ms_speed_animation);
    });

    $('form#order_review').on('submit', function (e) {
        var payment_method = $(this).find('input[name="payment_method"]:checked').val(),
            validate_result = true;

        if (['imoje_pbl', 'imoje_blik', 'imoje_paylater'].includes(payment_method)) {
            validate_result = validate_channels();
        }

        if (!validate_result) {
            setTimeout(function () {
                $(e.target).unblock();
            }, 500);
        }

        return validate_result;
    });

    $form.on('checkout_place_order_imoje_pbl', function () {
        return validate_channels($('.payment_method_imoje_pbl'));
    });
    $form.on('checkout_place_order_imoje_paylater', function () {
        return validate_channels($('.payment_method_imoje_paylater'));
    });
    $form.on('checkout_place_order_imoje_blik', function () {
        return validate_blik();
    });
    $form.on('checkout_place_order_imoje_installments', function () {
        return imoje_is_rendered_installments && imoje_is_passed_installments;
    });

    function validate_channels($payment_method) {

        if ($payment_method.find('.imoje-channel .imoje-active').length) {
            $('.imoje-pbl-error').slideUp(imoje_ms_speed_animation);

            return true;
        } else {

            $('html, body').animate({
                scrollTop: $payment_method.offset().top
            }, 500);
            $('.imoje-pbl-error').slideDown(imoje_ms_speed_animation);

            return false;
        }
    }

    function validate_blik() {

        if ($('.imoje-blik-code-container').length < 1) {
            return validate_channels($('.payment_method_imoje_blik'));
        }

        var $input_imoje_blik_code = $('input[name="imoje-blik-code"]');

        if ($input_imoje_blik_code.length < 1) {
            return false;
        }

        if ($input_imoje_blik_code.val().length !== 6) {
            alert(imoje_js_object.imoje_blik_tooltip)
            return false;
        }

        return true;

    }

    function show_installments_widget() {

        if (imoje_is_rendered_installments) {
            return;
        }

        var script = document.getElementById('imoje-installments__script'),
            $wraper = $('#imoje-installments__wrapper');

        if (script == null) {
            script = document.createElement('script');
            script.id = 'imoje-installments__script';
            script.src = $wraper.data('installmentsUrl');
            script.onload = () => {
                show_installments_widget();
            };
            document.body.append(script);

            return;
        }

        var installmentsData = $wraper.data();

        document.getElementById('imoje-installments__wrapper').imojeInstallments({
                amount: installmentsData.installmentsAmount,
                currency: installmentsData.installmentsCurrency,
                serviceId: installmentsData.installmentsServiceId,
                merchantId: installmentsData.installmentsMerchantId,
                signature: installmentsData.installmentsSignature
            }
        )

        imoje_is_rendered_installments = true;

    }

    $(document.body).on('updated_checkout', function () {

        if (document.getElementById('payment_method_imoje_installments') !== null) {
            show_installments_widget();
        }

    });

})(jQuery);
