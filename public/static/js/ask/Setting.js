/**
 * AskRole JS
 *
 * @author Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-12
 */

var Setting = {
	config : {} ,
	init : function () {
		//重新设置菜单
		if ( ! empty(Param.uri.menu) ) {
			Layout.setSidebarMenuActiveLink('set' , 'a[data-uri="' + Param.uri.menu + '"]');
		}

		//初始化ajax 提示框
		loading.initAjax();

		//初始化页面按钮
		this.initBtn();

		//初始化数据表
		this.initGrid();


	} ,

	//显示 modal
	setPortletShow : function (type) {
		var $addEditModal = $('#addEditModal');

		$addEditModal.modal('show');
		if ( type == 'add' ) {
			$addEditModal.find('.caption-subject').html('新增 ' + Param.pageTitle);
		} else if ( type == 'edit' ) {
			$addEditModal.find('.caption-subject').html('编辑 ' + Param.pageTitle);
		}
	} ,

	//关闭 modal
	setPortletHide : function () {
		$('#addEditModal').modal('hide');
	} ,

	//初始化各种按钮
	initBtn : function () {
		var self = this;

		//打开添加框
		$('#addNewBtn').on('click' , function (e) {
			e.preventDefault();
			self.setPortletShow('add');

			var $form = $('#addEditForm');

			$form.reloadForm(Param.defaultRow);


			$form.attr('action' , Param.uri.insert);
		});

		$('#type_tabs li:eq(0) a').on('click' , function(e){
			e.preventDefault();
			data = {};
			url = Param.uri.website;
			$.get(url , data , function (res) {

				$('#base').html(res.html);


			});

		});
		/*$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			e.preventDefault();
			$("[name='mail_open']").bootstrapSwitch({
				         onText:"开启",
				         offText:"关闭",
				         onColor:"success",
				         offColor:"info",
				         size:"small",
			 });
		});*/

		$('#type_tabs li:eq(1) a').on('click' , function(e){
			e.preventDefault();
			data = {};
			url = Param.uri.seo;
			$.get(url , data , function (res) {

				$('#seo').html(res.html);


			});
		});
		$('#type_tabs li:eq(2) a').on('click' , function(e){
			e.preventDefault();
			data = {};
			url = Param.uri.register;
			$.get(url , data , function (res) {

				$('#register').html(res.html);


			});
		});
		$('#type_tabs li:eq(3) a').on('click' , function(e){
			e.preventDefault();
			data = {};
			url = Param.uri.email;
			$.get(url , data , function (res) {

				$('#mail').html(res.html);
				//初始化开关
				$("[name='mail_switch']").bootstrapSwitch({
					onText:"开启",
					offText:"关闭",
					onColor:"success",
					offColor:"info",
				});
				$("[name='mail_ssl']").bootstrapSwitch({
					onText:"开启",
					offText:"关闭",
					onColor:"success",
					offColor:"info",

				});

			});

		});








	} ,

	delData : function (ids) {
		var self = this;
		var data = {
			ids : ids
		};

		sure.init('是否删除?' , function () {

			$.post(Param.uri.destroy , data)
			 .fail(function (res) {
				 tips.error(res.responseText);
			 })
			 .done(function (res) {
				 if ( res.code == 1001 ) {
					 //需要登录
					 tips.error('请先登录');
				 } else if ( res.code != 0 ) {
					 tips.error(res.msg);
				 } else {
					 tips.success(res.msg);
					 $('#dataGrid').TableGrid('reload');
				 }
			 });
		});
	} ,

	//初始化grid
	initGrid : function () {
		data = {};
		url = Param.uri.website;
		$.post(url , data , function (res) {

			$('#base').html(res.html);


		});

		/*var self = this;
		var uri = Param.uri.this + '?' + $.param(Param.query);
		history.replaceState(Param.query , '' , uri);

		$('#model-content').TableGrid({
			uri : Param.uri.read ,
			selectAll : true ,
			param : Param.query ,
			rowStyle : function (row) {
				if ( row.status == 0 ) {
					return 'warning';
				}
			} ,
			loadSuccess : function (rows , settings) {
				var oldUri = window.location.href;
				var uri = Param.uri.this + '?' + $.param(settings.param);
				if ( oldUri == uri ) {
					return false;
				}

				var params = $.getUrlParams(window.location.href);
				history.pushState(params , '' , oldUri);
				history.replaceState(settings.param , '' , uri);
			}
		});*/
	} ,

	//将下级权限置为可查看
	setSubOptCheck : function (this_node) {
		var self_tree = this_node.parent('.func-tree');
		var _sub_func_node = self_tree.find('.sub-permission .func-node');
		_sub_func_node.each(function () {
			var self_sub_func_node = $(this);
			$(this).find('.func-opt').each(function () {
				if ( $(this).html().indexOf('查看') > 0 ) {
					self_sub_func_node.find('.func').removeClass('active notall disabled').addClass('notall');
					$(this).removeClass('active notall disabled').addClass('active');
					var self_sub_func_opt_i = $(this).find('i');
					self_sub_func_opt_i.removeClass('fa-square-o').addClass('fa-check-square-o');
				}
			});
		});
	} ,

	//将下级权限置为全不选
	setSubOptDisabled : function (this_node) {
		var self_tree = this_node.parent('.func-tree');
		var _sub_func_node = self_tree.find('.sub-permission .func-node');
		_sub_func_node.each(function () {
			$(this).find('.func').removeClass('active notall disabled').addClass('disabled');
			$(this).find('.func-opt').removeClass('active notall disabled').addClass('disabled');
			$(this).find('.func-opt > i').removeClass('fa-check-square-o').addClass('fa-square-o');
		});
	} ,

	//初始化
	initPermission : function (privilegeData) {
		$('.func').addClass('disabled');
		$('.func-opt').addClass('disabled');

		for ( var i = 0 ; i < privilegeData.length ; i ++ ) {
			$obj = $('.func-opt[data-id="' + privilegeData[i].privilege_id + '"]');
			$obj.removeClass('disabled').addClass('active');
			$obj.find('i').removeClass('fa-square-o').addClass('fa-check-square-o');
		}

		$('.func-node').each(function (index) {
			var func_id = $(this).data('id');

			var total_len = $(this).find('.func-opt').length;
			var active_len = $(this).find('.active').length;
			if ( total_len == active_len ) {
				$(this).find('.func').removeClass('disabled').addClass('active');
			} else if ( total_len > active_len && active_len > 0 ) {
				$(this).find('.func').removeClass('disabled').addClass('notall');
			}
		});
	} ,

};

