$(function (){
	// load interface
	let likeIds = [], lang, type;
	$("[data-like-block]").each(function (){
		likeIds[likeIds.length] = $(this).data('id');
		if (typeof lang === 'undefined') {
			lang = $(this).data('lang')
		}
		if (typeof type === 'undefined') {
			type = $(this).data('type')
		}
	});
	BX.ajax.runComponentAction('hipot:ajax', 'loadIblockLikeTemplates', {
		mode: 'class',
		data: {
			'ids'   : likeIds,
			'type'  : type,
			'lang'  : lang,
		},
		method: 'POST'
	}).then(function(response){
		if (response.status === 'success') {
			$("[data-like-block]").each(function (){
				let _block = $(this),
					id = $(_block).data('id');
				if (typeof response.data[id] !== 'undefined') {
					_block.replaceWith(response.data[id].HTML);

					if (BX.localStorage.get('like-pt-' + id) != 1) {
						$(".ico-like-pt[data-id="+ id +"]").fadeTo(0, 0.5);
					}
				}
			});
		} else {
			console.log(response);
		}
	});

	// actions on interface
	$(document).on('click', '.ico-like-pt', function (){
		let _block = $(this),
			id = $(_block).data('id'),
			type = $(_block).data('type'),
			lang = $(_block).data('lang');

		if (_block.data('clicked') || BX.localStorage.get('like-pt-' + id) == 1) {
			return;
		}
		_block.fadeTo(0, 0.5).data('clicked', true);
		BX.localStorage.set('like-pt-' + id, 1, 3600 * 24 * 356);

		BX.ajax.runComponentAction('mgu:ajax', 'saveIblockLike', {
			mode: 'class',
			data: {
				'id'    : id,
				'type'  : type,
				'lang'  : lang,
			},
			method: 'POST'
		}).then(function(response){
			if (response.status === 'success') {
				$("[data-id-num-likes=" + id + '] b').html(response.data.CNT_P);
			} else {
				console.log(response);
			}
			_block.fadeTo(0, 1);
		});
	});
});