$('#rpdnotifiedform').submit(function ( event ){
	var form = $(this)[0];
	var email = $(this).find('#subscriber_email').val();
	var result = $(this).find('.rpd_result');
	var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	
	result.hide();
	if(reg.test(email)== true){
		form.submit();
	}
	else{
		
		result.show();
		event.preventDefault();
		return false;
	}
	
});