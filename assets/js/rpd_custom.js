				
		$(document).ready(function() {
			$('#example').DataTable( {
				"scrollY": 200,
				"scrollX": true
			} );
		});
		
		$(document).on('click', '.send_mail_btn', function() {
			
			var meta_id = $(this).closest('tr').find('.rpd_meta_id').val();
		    var rpd_send = $(this).find('.rpd_send_it');
		
			$.ajax({
					url: plugin_ajax.ajaxurl,
					type: 'POST',
					data: ({
					action: 'mail_send_to',
					meta_id: meta_id
					}),
					success: function(data) {
						 console.log(data);
						 alert('Email Sent Successfully');
						 rpd_send.html('ReSend');
						
					},
					 error: function(data, errorThrown){
						console.log('Error=>',errorThrown);
					}
				}); 
				
		});
		
		$(document).on('click','.delete_btn', function(){
		    var row = $(this).closest('tr');
			var meta_id_del = row.find('.rpd_meta_id').val();
		
				$.ajax({
					url: plugin_ajax.ajaxurl,
					type: 'POST',
					data: ({
					action: 'delete_record',
					meta_id_del: meta_id_del
					}),
					success: function(data) {
						 //console.log(data);
						 //location.reload(data);
						 row.hide('fast');
					},
					 error: function(data, errorThrown){
						console.log('Error=>',errorThrown);
					}
				}); 
			
		});

	