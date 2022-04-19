$ = jQuery.noConflict();

// enable/disable the banner ads, popups
$('#togglePopup').change((evt) => {

    let checked = $('#togglePopup').prop('checked');

    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'nhrRAS_AjaxHandler',
            togglePopup: checked ? 'YES':'NO'
        },
        success: function (response) {
            
            if(checked && response == 'error'){

                $('#togglePopup').prop('checked', false);

            }else if(!checked && response == 'error'){

                $('#togglePopup').prop('checked', true);

            }
    
        }
    });

});



// enable/disable the overlay
$('#toggleOverlay').change((evt) => {

    let checked = $('#toggleOverlay').prop('checked');
    
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'nhrRAS_AjaxHandler',
            toggleOverlay: checked ? 'YES':'NO'
        },
        success: function (response) {
            
            if(checked && response == 'error'){

                $('#toggleOverlay').prop('checked', false);

            }else if(!checked && response == 'error'){

                $('#toggleOverlay').prop('checked', true);

            }
    
        }
    });

});




// nhr-tabs functionalities
$('.nhr-tabs .tab-btns button').click((evt) => {

    let tabID = evt.target.getAttribute('data-tab');
    $('.nhr-tabs .tab-btns button.active').removeClass('active');
    $(evt.target).addClass('active');

    $('.nhr-tabs .tabs .tab.active').removeClass('active');
    $('.nhr-tabs .tabs #'+tabID).addClass('active');

});

