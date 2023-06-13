/**
 * get Read Models from HL-blocks data
 */
function HiModel()
{
	this.getAjaxData = function (method, dataSend, thenRun)
	{
		BX.ajax.runComponentAction('hipot:ajax', method, {
			mode: 'ajax',
			data: dataSend,
			method: 'POST'
		}).then(function(response){
			let responseData = {};
			if (response.status === 'success') {
				responseData = JSON.parse(response.data);
			}
			thenRun(responseData, response);
		});
	};
	this.getModelStat = function (entityName, entityOrder, filter, initRun)
	{
		this.getAjaxData('getEntityStat',  {
			'entityType'    : entityName,
			'entityOrder'   : entityOrder,
			'filter'        : filter
		}, initRun);
	};
	this.getModel = function (entityName, id, initRun)
	{
		this.getAjaxData('getEntity',  {
			'entityType'    : entityName,
			'entityId'      : id,
		}, initRun);
	};
}

const HiModelLib = new HiModel();

/*
use example:

HiModelLib.getModelStat('HiPortfolio',  {'UF_DATETIME': 'DESC'}, [], function (dataStat, response) {
	// statistic
	console.log(dataStat);
	if (!!dataStat.START_ID) {
		// one last post
		HiModelLib.getModel('HiPortfolio', dataStat.START_ID, function (dataPost, response) {
			console.log(dataPost);
		})
	}
});
*/