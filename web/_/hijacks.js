$(function(){
	//==================== ホワイトリスト追加 ====================//
	$('input.add_whitelist').click(function(){
		var conflict_type = parseInt($(this).prev().val());
		var two_way = conflict_type===11;
		var td_ele = $(this).parent();
		var suspicious_id = td_ele.data('id');
		var asn = td_ele.data('asn');
		var conf_asn_list = td_ele.data('conf_asn').toString().split('/');
		
		// ajax実行
		var param = {suspicious_id: suspicious_id,
					asn: asn,
					conf_asn: conf_asn_list,
					conflict_type: conflict_type,
					two_way: two_way};
		// console.log(param);
		if(conf_asn_list.length===1)
			EditWhitelistAjax(param, td_ele.parent());
		else
			ShowMultipleOriginSelectWindow(param, td_ele);
	});

	//==================== ホワイトリスト追加（Multiple） ====================//
	$(document).on('click', 'input.add_whitelist_multiple', function(){
		var div_ele = $(this).parent();
		var conflict_type = div_ele.data('conflict_type');
		var two_way = div_ele.data('two_way');
		var td_ele = div_ele.parent();
		var suspicious_id = td_ele.data('id');
		var asn = td_ele.data('asn');
		var conf_asn_list = [];
		div_ele.children('input[type=checkbox]:checked').each(function(i,ele){
			conf_asn_list.push(ele.value);
		});
		
		// ajax実行
		var param = {suspicious_id: suspicious_id,
					asn: asn,
					conf_asn: conf_asn_list,
					conflict_type: conflict_type,
					two_way: two_way};
		// console.log(param);
		EditWhitelistAjax(param, td_ele.parent());

		// 選択Windowのdivを削除
		div_ele.remove();
	});
});

// Ajaxでホワイトリストの登録を行う
function EditWhitelistAjax(param, tr_ele){
	// ajax失敗時の処理
	var json_error = function(){ alert('処理失敗，リトライしてください'); };
	// ajax成功時の処理
	var json_success = function(data){
		if(data.error){ json_error(); }
		else{
			if(data.conflict_type>=10)	$(tr_ele).addClass('whitelist');
			else						$(tr_ele).removeClass('whitelist');
			$(tr_ele).children('td[name=conflict_type]').text(data.conflict_type);
		}
	};
	// console.log(param);
	// ajax実行
	$.ajax({
		url: 'edit_whitelist.php',
		type: 'post',
		dataType: 'json',
		data: param,
		timeout: 2000,
	}).then(json_success, json_error);
}

function ShowMultipleOriginSelectWindow(param, td_ele){
	var div = $('<div>', {
			class: 'multipleorigin',
			css: {
				'background-color': 'white',
				position: 'absolute',
				top: 0,
				left: 0,
				width: 'calc(100% - 12px)',
				padding: '5px',
				'text-align': 'left',
				border: 'solid 1px black',
				'z-index': 1
			},
			'data-conf_asn': param.conf_asn_list,
			'data-conflict_type': param.conflict_type,
			'data-two_way': param.two_way
		});
	$.each(param.conf_asn, function(i, conf_asn){
		div.append('<input type="checkbox" value="'+conf_asn+'" checked>'+conf_asn+'<br>');
	});
	div.append('<input class="add_whitelist_multiple" type="button" value="追加">');
	div.append('<input type="button" value="キャンセル" onclick="$(this).parent().remove()">');
	
	// 要素を追加
	$(td_ele).append(div);
}
