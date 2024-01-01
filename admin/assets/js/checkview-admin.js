(function( $ ) {
	'use strict';
    $( document ).ready(function() {
        jQuery("#checkview_update_cache").click( function(e){ 

            e.preventDefault();
            var $thisButton = $(this);
            console.log("helloworld");
            $thisButton.removeClass('success error').addClass('loading');
            Swal.fire({
                title: 'Are you sure?',
                text:  'Cache will be updated permanently!',
                icon:  'warning',
                showCancelButton: true,
                confirmButtonText: 'Update cache',
                cancelButtonText: 'Cancel'
              }).then((result) => {
                if ( result.value ) {
    
                    $.ajax({
                        url: checkview_ajax_obj.ajaxurl,
                        type: 'post',
                        data: {
                            'action':'checkview_update_cache',
                            'user_id': checkview_ajax_obj.user_id,
                            _nonce   : $thisButton.data('nonce')
                        },beforeSend: function() {
                            Swal.fire({
                                title: 'Success',
                                text: 'Update in progress it will take some time!',
                                icon: 'success',
                                showCancelButton: false,
                                confirmButtonText: 'Ok',
                                timer: 3000,
                            })
                            $thisButton.removeClass('loading error').addClass('success');
                        },
                        success: function( response ) {
    
                                var tokenObj = JSON.parse( response );
                                 if( !tokenObj.success && tokenObj != '0'){
    
                                    Swal.fire({
                                        title: 'Error',
                                        text: tokenObj.message,
                                        icon: 'warning',
                                        showCancelButton: false,
                                        confirmButtonText: 'Ok',
    
                                    })
                                    $thisButton.removeClass('loading success').addClass('error');
    
                                } else {
                                    Swal.fire({
                                        title: 'Success',
                                        text:  (tokenObj != '0' ? tokenObj.message : 'Updated Successfully.'),
                                        icon: 'success',
                                        showCancelButton: false,
                                        confirmButtonText: 'Ok',
                                    })
                                    $thisButton.removeClass('loading error').addClass('success');
                                }
    
                        },
                    });
                } else {
                    $thisButton.removeClass('loading success error');
                } //endif
            })
        });
    });
})( jQuery );