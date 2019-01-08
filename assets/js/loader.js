/*
 * © 2019 SlashWebDesign
 */

function lg(o, level){
	if (!level) level = 'info';

	if (console)
	{
		switch (level)
		{
			case 'trace': console.log('%c' + o, 'color: #2f68b4'); break;
			case 'error': console.error(o); break;
			case 'warn': console.warn(o); break;
			case 'log':
			case 'info':
			default: console.log(o); break;
		}
	}
};

function loadScript(source, callback){
	var 
		tsNow = new Date().getTime(),
		script = document.createElement('script');

	script.src = source + '?v=' + tsNow;
	script.async = false;
	script.defer = false;
	script.onerror = function(){
		lg('loading > error loading file [' + source + ']', 'error');
	};
	
	if (typeof callback === 'function') script.onload = callback;

	document.getElementsByTagName('body')[0].appendChild(script);
};

function loadStyle(source, callback){
	var 
		tsNow = new Date().getTime(),
		style = document.createElement('link');

	style.href = source + '?v=' + tsNow;
	style.rel = 'stylesheet';
	style.type = 'text/css';
	style.onerror = function(){
		lg('loading > error loading file [' + source + ']', 'error');
		if (typeof callback === 'function') callback();
	};

	document.getElementsByTagName('head')[0].appendChild(style);
};

window.onerror = function(msg, source, line, col, error){
	lg(msg + ' in ' + source + ' on line ' + line, 'error');
	if (error) lg(error, 'error');
};

loadScript('assets/js/jquery.min.js', function(){
	loadScript('assets/js/jquery.validate.min.js', function(){
		loadScript('assets/js/bootstrap.min.js', function(){
			loadScript('assets/js/app.js');
			loadScript('assets/js/auth.js');
			
			for (var i = 0; i < global.assets.js.length; i++)
			{
				loadScript(global.assets.js[i]);
			}
			
			for (var i = 0; i < global.assets.css.length; i++)
			{
				loadStyle(global.assets.css[i]);
			}
			
			loadStyle('assets/css/cookieconsent.min.css');
			loadScript('assets/js/cookieconsent.js', function(){
				window.cookieconsent.initialise({
				  "palette": {
					"popup": {
					  "background": "#eaf7f7",
					  "text": "#5c7291"
					},
					"button": {
					  "background": "#56cbdb",
					  "text": "#ffffff"
					}
				  },
				  "theme": "edgeless",
				  "type": "opt-out",
				  "content": {
					"href": "privacy-policy"
				  }
				});
			});
		});
	});
});
