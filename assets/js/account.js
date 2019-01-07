/* 
 * Account module
 * ©SWD
 */

$(document).on('click', '.account .menu a[href="?act=account-delete"]', function(e){
	e.preventDefault();
	
	pp('Warning', 'Are you sure you want to delete your account?', [
		{label: 'Delete', cls: ' btn-info', callback: function(){	window.location.href = '?act=account-delete'; } },
		{label: 'Cancel', cls: '', callback: function(){} },
	]);
});

$(".account").validate({
	rules: {
		'data[fname]': { required: true },
		'data[lname]': { required: true },
		'data[username]': { required: true },
		'data[city]': { required: true },
		'data[dob]': { required: true },
		'data[pob]': { required: true },
		'data[gender]': { required: true },
		'data[email]': { required: true, email: true },
		'data[password]': { required: true }
	},
	errorPlacement: function(error, element){
		$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		$(element).tooltip('show');
	},
	success: function (label, element) { $(element).tooltip('hide'); }
});