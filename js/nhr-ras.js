// using the `$` as jQuery
$ = jQuery.noConflict();

// show the order form popup when click the order now button
$('#order-now').click((evt)=>{

    $('#nhr-ras-order-form-container').addClass('showOffer');
    $('#nhr-ras-banner').addClass('hideBanner');
    document.querySelector('#nhr-ras-order-form #fullName').dispatchEvent(new Event('focus'));


    // form vaidation
    $('#nhr-ras-order-form .inline-btns button').click((evt)=>{

        // disabling the buttons
        $('#nhr-ras-order-form .inline-btns button').prop('disabled', true);

        let fullName = $('#nhr-ras-order-form #fullName').val();
        let email = $('#nhr-ras-order-form #email').val();
        let siteURL = $('#nhr-ras-order-form #siteURL').val();


        // parsing the url
        if( !siteURL.match("^(http://|https://)") ){ siteURL = "http://" + siteURL; }


        // to clear all the fields
        function clearFields(){
            $('#nhr-ras-order-form #siteURL').val('');
            $('#nhr-ras-order-form #fullName').val('');
            $('#nhr-ras-order-form #email').val('');
        }


        // validation
        if(fullName == '' || email == '' || siteURL == ''){

            // disabling the buttons
            $('#nhr-ras-order-form .inline-btns button').prop('disabled', false);
            $('#nhr-ras-order-form #error-msg').css('opacity', '1').text('Please fill the form correctly!');

        }else if( !email.match("^([a-zA-Z0-9\._-]+)@([a-zA-Z0-9]+)[\.]{1}([a-zA-Z0-9]+)") ){

            // disabling the buttons
            $('#nhr-ras-order-form .inline-btns button').prop('disabled', false);
            $('#nhr-ras-order-form #error-msg').css('opacity', '1').text('Invalid email!');
            
        }else{

            $('#nhr-ras-order-form #error-msg').css('opacity', '0');
            let id = evt.target.id;
            
            // IF CLICKED ON THE 5 MINUTES TRIAL BUTTON
            if(id == "five-minutes-trial-btn"){

                // saving the info in database
                jQuery.ajax({
                    type: "POST",
                    url: nhrRAS.url,
                    data: {
                        action: 'nhrRAS_AjaxHandler',
                        trial: 'trial',
                        fullName,
                        email,
                        siteURL
                    },
                    success: function (resp) {
                        
                        // disabling the buttons
                        $('#nhr-ras-order-form .inline-btns button').prop('disabled', false);

                        if(resp == 'success'){

                            clearFields();

                            // hiding all the banners and popups
                            $('#nhr-ras-order-form-container').removeClass('showOffer');
                            $('#nhr-ras-banner').addClass('hideBanner');

                            // overlaying the iframe
                            $('#nhr-ras-iframe').attr('src', siteURL).addClass('overlay');
                            $('body').css('position', 'fixed');

                            // setting a timeout for the trial period
                            let trialTimout = setTimeout(() => {
                                
                                //  the iframe
                                $('#nhr-ras-iframe').removeClass('overlay').attr('src', '');

                                // displaying back the banner
                                $('#nhr-ras-banner').removeClass('hideBanner');

                                $('body').css('position', 'static');

                            }, 1000 * 60 * 5);

                        }else if(resp != '' || resp != null){
                            
                            alert('Something went wrong, please try again!');

                        }

                    }
                });

            }
            
            // IF CLICKED ON THE RENT NOW BUTTON
            else if(id == "rent-now-btn"){

                // clearFields();

                // hiding the error message
                $('#nhr-ras-order-form #error-msg').text('Processing...');

                // enabling the buttons
                $('#nhr-ras-order-form .inline-btns button').prop('disabled', false);

                $.ajax({
                    type: "POST",
                    url: nhrRAS.url,
                    data: {
                        action: 'nhrRAS_AjaxHandler',
                        preRental: true,
                        fullName,
                        email,
                        siteURL
                    },
                    success: function (response) {
                        
                        if(response == 'success'){

                            $('#nhr-ras-order-form-container').removeClass('showOffer');
                            $('#nhr-ras-banner').addClass('hideBanner');
                            
                            // clicking the stripe's payment button
                            alert('Use the same Email ('+email+') for payment!');
                            $("#nhr-ras-payment-form button").click();

                        }else{

                            $('#nhr-ras-order-form #error-msg').text('Something went wrong, please try again!').css('opacity', '1');

                        }

                    }
                });

            }

        }

    });


});


// when click the cancel button, the popup will be closed
$('#nhr-ras-order-form-container #close-popup').click((evt)=>{

    $('#nhr-ras-order-form-container').removeClass('showOffer');
    $('#nhr-ras-banner').removeClass('hideBanner');

});





