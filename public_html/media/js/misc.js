$.fn.hasAttr = function(name)
{
	return this.attr(name) !== undefined;
};

$(function()
{
	$('.search').submit(function(e)
	{
		var userName = $(this).find('[name=user-name]').val();
		if (userName.replace(/^\s+|\s+$/, '') == '')
		{
			e.preventDefault();
			e.stopPropagation();
			$(this).find('[name=user-name]').val('');
		}
	});

	$('.tooltip, .tooltip span, .highcharts-tooltip, .highcharts-tooltip span')
		.click(function(e)
		{
			e.preventDefault();
			e.stopPropagation();
			$(this).hide();
			$(document.elementFromPoint(e.clientX,e.clientY)).trigger("click");
			$(this).show();
		});
});

function getProcessedDate()
{
	return new Date(Date.parse($('#processed').val()));
}

function ucfirst(str)
{
	str += '';
	var f = str.charAt(0).toUpperCase();
	return f + str.substr(1);
}

function lpad(str, width)
{
	str += '';
	return str.length >= width
		? str
		: new Array(width - str.length + 1).join('0') + str;
}
