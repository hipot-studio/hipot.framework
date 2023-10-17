$(function(){	
	// корректность телефона
	$('#order_form .phone').keypress(function(e){		
		return isPhone(e.which);
	});
	
	//скрипт для стилизированного поля файла
	$('.fonTypeFile').click(function(){
		$('.inputFile').click();
	});
	$('.inputFile').change(function(){
		$(".inputFileVal", $(this).parent()).val(this.value).css('display', 'block');
	});	
});