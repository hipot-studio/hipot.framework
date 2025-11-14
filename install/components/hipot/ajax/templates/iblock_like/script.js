$(function () {
	// load interface
	let likeIds = [],
		lang,
		type,
		template;

	$("[data-like-block]").each(function () {
		likeIds[likeIds.length] = $(this).data("id");
		template = $(this).data("template");
		if (typeof lang === "undefined") {
			lang = $(this).data("lang");
		}
		if (typeof type === "undefined") {
			type = $(this).data("type");
		}
	});

	BX.ajax
		.runComponentAction("hipot:ajax", "loadIblockLikeTemplates", {
			mode: "class",
			data: {
				ids: likeIds,
				type: type,
				lang: lang,
				template: template,
			},
			method: "POST",
		})
		.then(function (response) {
			if (response.status === "success") {
				$("[data-like-block]").each(function () {
					let _block = $(this),
						id = $(_block).data("id");
					if (typeof response.data[id] !== "undefined") {
						_block.replaceWith(response.data[id].HTML);

						if (BX.localStorage.get("like-pt-" + id) == 1) {
							$(`.blog-section_inner_grid_item_info_favorite[data-id="${id}"]`).addClass("blog-section_inner_grid_item_info_favorite--added");
						}
					}
				});
			} else {
				console.log(response);
			}
		});

	// actions on interface
	$(document).on("click", ".blog-section_inner_grid_item_info_favorite", function () {
		let _block = $(this),
			id = $(_block).data("id"),
			type = $(_block).data("type"),
			lang = $(_block).data("lang"),
			isLiked = BX.localStorage.get("like-pt-" + id) == 1;

		const likeAction = isLiked ? "-" : "+";

		if (isLiked) {
			_block.removeClass("blog-section_inner_grid_item_info_favorite--added");
			like({id, type, lang, value: likeAction}, _block);
			BX.localStorage.remove("like-pt-" + id, null);

			return;
		}

		_block.addClass("blog-section_inner_grid_item_info_favorite--added");
		like({id, type, lang, value: likeAction}, _block);
		BX.localStorage.set("like-pt-" + id, 1, 3600 * 24 * 356);
	});

	/**
	 * @typedef {Object} LikeActionData
	 * @property {number} id
	 * @property {string} type
	 * @property {string} lang
	 * @property {string} value - property defines which action we're doing. "+" = like, "-" = dislike
	 */

	/**
	 * Like action for iblock_like
	 * @param {LikeActionData} data - for like action
	 */
	function like(data, _block) {
		BX.ajax
			.runComponentAction("hipot:ajax", "saveIblockLike", {
				mode: "class",
				data,
				method: "POST",
			})
			.then(function (response) {
				const {id} = data;

				if (response.status === "success") {
					$(`[data-id-num-likes="${id}"]`, _block).html(response.data.CNT_P);
				} else {
					console.log(response);
				}
			});
	}
});