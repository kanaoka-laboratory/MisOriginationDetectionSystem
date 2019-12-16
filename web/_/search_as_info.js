$(function(){
	//==================== ホワイトリスト追加 ====================//
	$('input.ignore_web').click(function(){
        var div_ele = $(this).parent();
		
		// ajaxパラメタ
		var param = {url: $(this).data('url')};
		// ajax失敗時の処理
        var json_error = function(){ alert('処理失敗，リトライしてください'); };
        // ajax成功時の処理
        var json_success = function(data){
            if(data.error){ json_error(); }
            else{ div_ele.remove(); }
        };
        // ajax実行
        $.ajax({
            url: 'ignore_url_from_moascleaning.php',
            type: 'post',
            dataType: 'json',
            data: param,
            timeout: 2000,
        }).then(json_success, json_error);
    });

});
