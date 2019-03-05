$(function(){
	//==================== チェックボックス変更時 ====================//
	$('input[type=checkbox]').change(function(){
		var ele_checkbox = this;
		var ele_number = $(this).prev();
		var asn = $(this).data('asn');
		var conf_asn = $(this).data('conf_asn');
		// debug
		// console.log($(this).data('asn')+'///'+$(this).data('asn_conf'));
		
		// true: ホワイトリストに追加，false: ホワイトリストから削除			
		var whitelist = $(ele_checkbox).prop('checked')? $(ele_number).val(): 0;

		//------------ ajaxここから ------------//
		// ajax成功時の処理
		var json_success = function(data){
			if(data['error']){
				json_error();
			}else{
				if(whitelist)	$(ele_number).attr('disabled',true).parents('tr').addClass('whitelist');
				else			$(ele_number).attr('disabled',false).parents('tr').removeClass('whitelist');
			}
		}
		// ajax失敗時の処理
		var json_error = function(){
			alert('処理失敗，リトライしてください');
			$(ele_checkbox).prop('checked', !whitelist);
		};
		// ajax実行
		$.ajax({
			url: 'edit_whitelist.php',
			type: 'POST',
			dataType: 'json',
			data: {asn: asn, conf_asn: conf_asn, whitelist: whitelist, two_way: true},
			timeout: 1000,
		}).then(json_success, json_error);
		
	});
});
