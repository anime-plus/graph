var _params = {};

$(function()
{
	$('.export-trigger').click(function(e)
	{
		e.preventDefault();
		var target = $('.export.popup');
		$('.popup-wrapper').fadeIn('fast');
		updatePreview(target);
	});

	function updatePreview(target)
	{
		var img = $(target).find('img');
		var matches = $(target).find('textarea').val().match(/\[img\](.*)\[\/img]/);
		var newSrc = matches[1];
		if (img.attr('src') != newSrc)
		{
			img.attr('src', newSrc);
		}
	}

	/* prepate type selector */
	var types =
	[
		{ 'type': 1, 'name': 'Anime' },
		{ 'type': 2, 'name': 'Manga' },
		{ 'type': 3, 'name': 'Anime & manga' },
	];
	$('select[name=\'type\']').each(function()
	{
		for (var i in types)
		{
			$(this).append($('<option/>').text(types[i]['name']).data('type', types[i]['type']));
		}
		$(this).change(function()
		{
			var textarea = $(this).parents('.export').find('textarea');
			var type = $(this).find('option:selected').data('type');
			updateParams({0: type});
			updatePreview($(this).parents('.export'));
		});
	});

	function resetParams()
	{
		_params = {0: _params[0]};
	}

	function updateParams(params)
	{
		var textarea = $('.export.popup textarea');
		for (var k in params) {
            _params[k] = params[k];
        }
        params = [];
        for (var k in _params) {
            params.push(_params[k]);
        }

		var newText = btoa(JSON.stringify(params)).replace(/=/g, '');
		textarea.val(textarea.val().replace(/\/[^\/]+\/(\d{10})/, '/' + newText + '/$1'));
		return _params;
	}

	var defaultParams = { 1: 'a4c0f4', 2: '13459a', 3: 'f8fafe', 4: 'eff2f8', 5: 'ffffff', 6: '000000', 7: 'aaaaaa', 8: '577fc2', 9: '577fc2' };

	/* prepare theme selecton */
	var themes =
	[
		{ 'params': { }, 'name': 'Blue (default)' },
		{ 'params': { 1: '00ffaaaa', 2: '00ee6677', 3: 'c0ffaaaa', 4: 'c0ee6677', 5: 'ffffffff', 6: '20442233', 7: '85aa4444', 8: '00cc5566', 9: '00cc5566' }, 'name': 'Pink' },
		{ 'params': { 1: '0044ff44', 2: '00008800', 3: 'dd44ff44', 4: 'dd00aa00', 5: 'ffffffff', 6: '20227722', 7: '90227722', 8: '00227722', 9: '00227722' }, 'name': 'Green' },
		{ 'params': { 1: '00eecc05', 2: '00dd2200', 3: 'aaffdd00', 4: 'aaff0000', 5: 'ffffffff', 6: '20220700', 7: '90220700', 8: '00220700', 9: '00220700' }, 'name': 'Flame (yellow + red)' },
		{ 'params': { 1: 'aa000000', 2: '33000000', 3: 'ff000000', 4: 'dd000000', 5: 'ffffffff', 6: '20000000', 7: '90000000', 8: '20000000', 9: '20000000' }, 'name': 'Gray' },
		{ 'params': { 1: '0084a0d4', 2: '0003359a', 3: '00446084', 4: '0001156a', 5: '00000000', 6: '00779fe2', 7: '50779fe2', 8: '00779fe2', 9: '00779fe2' }, 'name': 'unBlue (blue on black)' },
		{ 'params': { 1: '0044ff44', 2: '00008800', 3: '00004400', 4: '00008800', 5: '00000000', 6: '2044ff44', 7: '00008800', 8: '0033aa33', 9: '0033aa33' }, 'name': 'Matrix (green on black)' },
		{ 'params': { }, 'name': 'Custom' },
	];

	$('select[name=\'theme\']').each(function()
	{
		for (var i in themes)
		{
			$(this).append($('<option/>').text(themes[i]['name']).data('params', themes[i]['params']));
		}
		$(this).change(function()
		{
			var target = $(this).parents('.export');
			var params = $(this).find('option:selected').data('params');
			if ($(this).find('option:selected').text() == 'Blue (default)')
			{
				resetParams();
			}

			if ($(this).find('option:selected').text() == 'Custom')
			{
				updateParams(defaultParams);
				target.find('select.color').trigger('change'); /*update colorpicker on theme change*/
				target.data('interval', window.setInterval(function()
				{
					updatePreview(target);
				}, 750));
				target.find('.custom-theme').animate({width: 'show'});
			}
			else
			{
				target.find('.custom-theme').animate({width: 'hide'});
				window.clearInterval(target.data('interval'));
				updateParams(params);
				updatePreview(target);
			}
		});
	});

	/* select text in textarea on click */
	$('.export textarea').click(function()
	{
		$(this).select();
	});

	function changedColor(color)
	{
		if (!$('.export .colorpicker').is(':visible'))
            return;
        $('.export input.hex').val(color);
		var target = $('.export');
		var key = target.find('select.color').val();
		var value = color.substr(1);
		var newParams = {};
		newParams[key] = value;
		updateParams(newParams);
	}

	/* custom theme editing */
	var opt =
	{
		callback: changedColor,
		width: 200,
		height: 200
	};
	$.farbtastic($('.export .colorpicker'), opt).target = $(this);

	$('.export select.color').change(function()
	{
		var textarea = $(this).parents('.export').find('textarea');
		var colorpicker = $(this).parents('.export').find('.colorpicker');
		var key = $(this).val();
		var params = updateParams({});
		$.farbtastic(colorpicker).setColor('#' + params[key]);
    });

	$('.export input.hex').on('keyup', function()
	{
        if (/^#[0-9a-f]{6}$/i.test($(this).val())) {
            $.farbtastic($('.export .colorpicker')).setColor($(this).val());
        }
    });

	$('.export .close').click(function(e)
	{
		$('.popup-wrapper').fadeOut('fast');
		e.preventDefault();
	});

	updateParams({0: 1});
});
