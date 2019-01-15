/*
 * @Author: Slash Web Design
 * Login module
*/

var client = {

	initSignIn: function(r){
		this.buildModal('signin');
		
		$('.access.signup, .access.forgot, #notify-modal').modal('hide');
		$('.access.signin').modal('show');

		if (r !== undefined) client.redirect = r;
	},

	initSignUp: function(type){
		if (global.user !== null)
		{
			window.location.href = 'account';
			return false;
		}
		
		this.buildModal('signup');
		
		$('.access.signin, .access.forgot, #notify-modal').modal('hide');
		$('.access.signup').modal('show');
		
		if (typeof type === undefined) type = 'customer';

		$('.type-' + type).prop('checked', true);
	},

	signIn: function(email, password){
		$('.access.signin .btn-info').addClass('disabled').html('<span class="spinner s20"></span>');
		request({
			data: {
				act: 'auth-signIn',
				email: email,
				password: password
			},
			success: function(r){
				$('.access.signin .btn-info').removeClass('disabled').html(__('Sign in'));
				if (r.status === false)
				{
					pp(__('Sign in'), r.message);
					return false;
				}

				window.location.href = 'account';
			}
		});
	},

	signUp: function(type, fname, lname, email, password){
		$('.access.signup .btn-info').addClass('disabled').html('<span class="spinner s20"></span>');
		
		request({
			data: {
				act: 'auth-backgroundCheck',
				email: email
			},
			error: function(){
				$('.access.signup .btn-info').removeClass('disabled').html(__('Sign up'));
				pp(__('Error'), __('Please try again later'));
			},
			success: function(r){
				if (r.status)
				{
					request({
						data: {
							act: 'auth-signUp',
							type: type,
							fname: fname,
							lname: lname,
							email: email,
							name: name,
							password: password
						},
						success: function(r){
							$('.access.signup .btn-info').removeClass('disabled').html(__('Sign up'));
							
							if (r.status === false)
							{
								pp(__('Sign in'), r.message);
								return false;
							}

							window.location.href = 'account';
						}
					});
					return false;
				}

				pp(__('Error'), r.message);
			}
		});
	},
	
	buildModal: function(type){
		var html = {
			signin: 
				'<div class="modal hide access signin">' +
					'<div class="modal-body">' +
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
						'<div class="row-fluid">' +
							'<div class="span6 text-center">' +
								'<img src="assets/img/logo.big.light.png" alt="conexe logo" />' +
							'</div>' +
							'<div class="span6 text-center">' +
								'<h1>' + __('Sign in') + '</h1>' +
								'<h2>' + __('Let\'s do something') + '</h2>' +
								'<form>' +
									'<input type="text" name="email" placeholder="' + __('Email') + '" />' +
									'<input type="password" name="password" placeholder="' + __('Password') + '" />' +
									'<button class="btn btn-info">' + __('Sign in') + '</button>' +
								'</form>' +
								'<p><a href="sign-up">' + __('Not a member?') + '</a> <a href="forgot">' + __('Forgot your password?') + '</a></p>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>',
			signup: 
				'<div class="modal hide access signup">' +
					'<div class="modal-body">' +
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
						'<div class="row-fluid">' +
							'<div class="span6 text-center">' +
								'<img src="assets/img/logo.big.light.png" alt="conexe logo" />' +
							'</div>' +
							'<div class="span6 text-center">' +
								'<h1>' + __('Sign up') + '</h1>' +
								'<h2>' + __('Create your account on Conexe<br />and get access to all features') + '</h2>' +
								'<form>' +
									'<p>' +
										'<label><input type="radio" name="type" class="type-customer" value="customer" /> ' + __('as customer') + '</label>' +
										'<label><input type="radio" name="type" class="type-vendor" value="vendor" /> ' + __('as vendor') + '</label>' +
									'</p>' +
									'<input type="text" name="fname" placeholder="' + __('First name') + '" />' +
									'<input type="text" name="lname" placeholder="' + __('Last name') + '" />' +
									'<input type="text" name="email" placeholder="' + __('Email address') + '" />' +
									'<input type="password" name="password" placeholder="' + __('Password') + '" />' +
									'<p><button class="btn btn-info">' + __('sign up') + '</button></p>' +
								'</form>' +
								'<p><a href="sign-in">' + __('Already have an account?') + '</a></p>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>',
			forgot:
				'<div class="modal hide access forgot">' +
					'<div class="modal-body">' +
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
						'<div class="row-fluid">' +
							'<div class="span6 text-center">' +
								'<img src="assets/img/logo.big.light.png" alt="conexe logo" />' +
							'</div>' +
							'<div class="span6 text-center">' +
								'<h1>' + __('Forgot your password') + '</h1>' +
								'<h2>' + __('We will send you a new password<br />once you log in you can update it') + '</h2>' +
								'<p><input type="text" name="pass-email" placeholder="' + __('Email') + '" /></p>' +
								'<p><button class="btn btn-info">' + __('Reset password') + '</button></p>' +
								'<p><a href="sign-in">' + __('Sign in') + '</a> ' + __('or') + ' <a href="sign-in">' + __('Sign up') + '</a></p>' +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>'
		};
		
		if (!$('.' + type).length) $('body').append(html[type]);
	}
};

$(document).on('keyup', '.signin input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signin form').valid())
		{
			client.signIn($('.signin input[name="email"]').val(), $('.signin input[name="password"]').val());
		}
	}
});

$(document).on('keyup', '.signup input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signup form').valid())
		{
			client.signUp($('.signup input[name="type"]').val(), $('.signup input[name="fname"]').val(), $('.signup input[name="lname"]').val(), $('.signup input[name="email"]').val(), $('.signup input[name="password"]').val());
		}
	}
});

$(document).on('click', '.signin .btn-info', function(e){
	e.preventDefault();

	if ($(this).hasClass('disabled')) return false;

	if ($('.signin form').valid())
	{
		client.signIn($('.signin input[name="email"]').val(), $('.signin input[name="password"]').val());
	}
});

$(document).on('click', '.signup .btn-info', function(e){
	e.preventDefault();

	if ($(this).hasClass('disabled')) return false;

	if ($('.signup form').valid())
	{
		client.signUp(
			$('.signup input[name="type"]').val(),
			$('.signup input[name="fname"]').val(),
			$('.signup input[name="lname"]').val(),
			$('.signup input[name="email"]').val(),
			$('.signup input[name="password"]').val()
		);
	}
});

$(document).on('click', 'a[href="sign-up"]', function(e){
	e.preventDefault();
	client.initSignUp($(this).data('type'));
});

$(document).on('click', 'a[href="sign-in"]', function(e){
	e.preventDefault();
	client.initSignIn();
});

$(document).on('click', '.forgot .btn', function(){
	if ($(this).hasClass('disabled')) return false;

	if ($('.forgot input[name="pass-email"]').val() === '')
	{
		pp(__('Reset my password'), __('Please fill in your email address'));
		return false;
	}

	$('.forgot .btn').html('<span class="spinner s20"></span>').addClass('disabled');
	request({
		data: {
			act: 'auth-forgot',
			email: $('.forgot input[name="pass-email"]').val()
		},
		success: function(r){
			$('.forgot .btn').html(__('Reset password')).removeClass('disabled');
			$('.access.forgot').modal('toggle');
			pp(__('New password'), r.message);
		}
	});
});

$(document).on('click', 'a[href="forgot"]', function(e){
	e.preventDefault();

	client.buildModal('forgot');
		
	$('.access.signin').modal('hide');
	$('.access.forgot').modal('show');
});

validateForm('.signup form', {
	type: {required: true},
	fname: {required: true},
	lname: {required: true},
	email: {required: true, email: true},
	password: {required: true}
}, 'bottom');

validateForm('.signin form', {
	email: {required: true, email: true},
	password: {required: true}
}, 'bottom');