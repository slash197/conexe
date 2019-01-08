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

$(document).on('click', '.ico-photo-camera', function(){
	$('#file-uploader input[type="file"]').click();
});

var uploader = new qq.FileUploader({
	element: document.getElementById('file-uploader'),
	multiple: false,
	action: 'callback.php?act=general-upload',
	debug: true,
	uploadButtonText: 'Upload',
	disableDefaultDropzone: true,
	onComplete: function(id, filename, response){
		if (response.success === true)
		{
			$('input[name="image"]').val(response.path);
			request({
				data: {
					act: 'account-saveProfileImage',
					image: response.path
				},
				success: function(r){
					if (r.status) $('img.profile').attr('src', r.url);
				}
			});
		}
	}
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