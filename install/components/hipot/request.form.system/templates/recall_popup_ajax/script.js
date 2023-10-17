$(function(){	
	// корректность телефона
	$('#recall_form .phone').keypress(function(e){		
		return isPhone(e.which);
	});
});