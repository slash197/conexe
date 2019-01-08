/* 
 * Account module
 * ©SWD
 */

$(document).on('click', '.account .menu a[href="?act=account-delete"]', function(e){
	e.preventDefault();
	
	pp('Warning', 'Are you sure you want to delete your account?', [
		{label: 'Delete', cls: ' btn-info', callback: function(){	window.location.href = '?act=account-delete'; } },
		{label: 'Cancel', cls: '', callback: function(){} }
	]);
});

$(document).on('change', 'select[name="data[country]"]', function(){
	lg('change');
	request({
		data: {
			act: 'general-getStates',
			country_id: $(this).val()
		},
		success: function(r){
			if (r.status) $('select[name="data[region]"]').html(r.states);
		}
	});
});

$(".account form").validate({
	rules: {
		'data[fname]': { required: true },
		'data[lname]': { required: true },
		'data[city]': { required: true },
		'data[email]': { required: true, email: true },
		'data[password]': { required: true }
	},
	errorPlacement: function(error, element){
		$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		$(element).tooltip('show');
	},
	success: function (label, element) { $(element).tooltip('hide'); }
});