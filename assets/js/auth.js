/*
 * @Author: Slash Web Design
 * Login module
*/

var client = {

	initSignIn: function(r){
		$('.access.signup, .access.forgot, #notify-modal').modal('hide');
		$('.access.signin').modal('show');

		if (r !== undefined) client.redirect = r;
	},

	initSignUp: function(){
		$('.access.signin, .access.forgot, #notify-modal').modal('hide');
		$('.access.signup').modal('show');
	},

	signIn: function(type, email, password){
		$('.access.signin .btn-info').addClass('disabled').html('<span class="spinner s20"></span>');
		request({
			data: {
				act: 'auth-signIn',
				type: type,
				email: email,
				password: password
			},
			success: function(r){
				$('.access.signin .btn-info').removeClass('disabled').html('Sign in');
				if (r.status === false)
				{
					pp('Sign in', r.message);
					return false;
				}

				window.location.reload();
			}
		});
	},

	signUp: function(type, name, email, password){
		$('.access.signup .btn-info').addClass('disabled').html('<span class="spinner s20"></span>');
		request({
			data: {
				act: 'auth-backgroundCheck',
				email: email
			},
			success: function(r){
				$('.access.signup .btn-info').removeClass('disabled').html('Sign in');
				if (r.status)
				{
					request({
						data: {
							act: 'auth-signUp',
							type: type,
							email: email,
							name: name,
							password: password
						},
						success: function(r){
							$('.access.signup .btn-info').removeClass('disabled').html('Sign in');
							if (r.status === false)
							{
								pp('Sign in', r.message);
								return false;
							}

							window.location.reload();
						}
					});
					return false;
				}

				pp('Error', r.message);
			}
		});
	}
};

$(document).on('keyup', '.signin input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signin form').valid())
		{
			client.signIn('form', $('.signin input[name="email"]').val(), $('.signin input[name="password"]').val());
		}
	}
});

$(document).on('keyup', '.signup input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signup form').valid())
		{
			client.signUp('form', $('.signup input[name="name"]').val(), $('.signup input[name="email"]').val(), $('.signup input[name="password"]').val());
		}
	}
});

$(document).on('click', '.signin .btn-info', function(e){
	e.preventDefault();

	if ($(this).hasClass('disabled')) return false;

	if ($('.signin form').valid())
	{
		client.signIn('form', $('.signin input[name="email"]').val(), $('.signin input[name="password"]').val());
	}
});

$(document).on('click', '.signup .btn-info', function(e){
	e.preventDefault();

	if ($(this).hasClass('disabled')) return false;

	if ($('.signup form').valid())
	{
		client.signUp(
			'form',
			$('.signup input[name="name"]').val(),
			$('.signup input[name="email"]').val(),
			$('.signup input[name="password"]').val()
		);
	}
});

$(document).on('click', 'a[href="sign-up"]', function(e){
	e.preventDefault();
	client.initSignUp();
});

$(document).on('click', 'a[href="sign-in"]', function(e){
	e.preventDefault();
	client.initSignIn();
});

$(document).on('click', '.forgot .btn', function(){

	if ($(this).hasClass('disabled')) return false;

	if ($('.forgot input[name="pass-email"]').val() === '')
	{
		pp('Reset my password', 'Please fill in your email address');
		return false;
	}

	$('.forgot .btn').html('<span class="spinner s20"></span>').addClass('disabled');
	request({
		data: {
			act: 'auth-forgot',
			email: $('.forgot input[name="pass-email"]').val()
		},
		success: function(r){
			$('.forgot .btn').html('Reset password').removeClass('disabled');
			$('.access.forgot').modal('toggle');
			pp('New password', r.message);
		}
	});
});

$(document).on('click', 'a[href="forgot"]', function(e){
	e.preventDefault();
	$('.access.signin').modal('hide');
	$('.access.forgot').modal('show');
});

validateForm('.signup form', {
	name: {required: true},
	email: {required: true, email: true},
	password: {required: true}
}, 'bottom');

validateForm('.signin form', {
	email: {required: true, email: true},
	password: {required: true}
}, 'bottom');