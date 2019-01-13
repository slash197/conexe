/* 
 * Account module
 * ©SWD
 */

var xhr = null;

$(document).on('click', '.account .btn-delete', function(e){
	pp('Warning', 'Are you sure you want to delete your account?', [
		{label: 'Delete', cls: ' btn-info', callback: function(){	window.location.href = '?act=account-delete'; } },
		{label: 'Cancel', cls: '', callback: function(){} }
	]);
});

$(document).on('click', '.location-dd .holder li', function(){
	if ($(this).data('id'))
	{
		$('input[name="location"]').val($(this).text());
		$('input[name="data[city_id]"]').val($(this).data('id'));
		$('input[name="data[region_id]"]').val($(this).data('region-id'));
		$('input[name="data[country_id]"]').val($(this).data('country-id'));
	}
	
	$('.location-dd .holder').remove();
});

$(document).on('keyup', 'input[name="location"]', function(){
	if ($(this).val().length < 4) return false;
				
	if (!$('.location-dd .holder').length) $('.location-dd').append('<ul class="holder" />');
	
	$('.location-dd .holder').html('<div class="spinner blue s40"></div> searching...');
	
	if (xhr !== null) xhr.abort();
	
	xhr = request({
		data: {
			act: 'general-getLocation',
			input: $(this).val().trim()
		},
		success: function(r){
			if (r.status)
			{
				var 
					loc = null,
					html = '';
				
				for (var i = 0; i < r.results.length; i++)
				{
					loc = r.results[i];
					html += 
						'<li data-id="' + loc.id + '" data-region-id="' + loc.region_id + '" data-country-id="' + loc.country_id + '">' + 
							'<span class="city">' + loc.name + '</span>, ' + 
							'<span class="region">' + loc.region_name + ', ' + loc.country_name + '</span>' +
						'</li>';
				}
				
				if (!r.results.length) html = '<li>Unrecognized location</li>';
				
				$('.location-dd .holder').html(html).show();
			}
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
		'location': { required: true },
		'data[email]': { required: true, email: true },
		'data[password]': { required: true }
	},
	errorPlacement: function(error, element){
		$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		$(element).tooltip('show');
	},
	success: function (label, element) { $(element).tooltip('hide'); }
});