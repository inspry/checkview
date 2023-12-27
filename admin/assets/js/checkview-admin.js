(function( $ ) {
	'use strict';

	jQuery("#reset-library").click( function(e){ 

        e.preventDefault();
        var $thisButton = $(this);
        $thisButton.removeClass('success error').addClass('loading');
        Swal.fire({
            title: 'Are you sure?',
            text:  'Library will be updated permanently!',
            icon:  'warning',
            showCancelButton: true,
            confirmButtonText: 'Sync Library',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if ( result.value ) {

                $.ajax({
                    url: templatehero_ajax_obj.ajaxurl,
                    type: 'post',
                    data: {
                        'action':'checkview_sync_library',
                        'user_id': templatehero_ajax_obj.user_id,
                        _nonce   : $thisButton.data('nonce')
                    },beforeSend: function() {
                        Swal.fire({
                            title: 'Success',
                            text: 'Sync in progress it will take some time!',
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
                                    text:  (tokenObj != '0' ? tokenObj.message : 'Synced Successfully.'),
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

})( jQuery );