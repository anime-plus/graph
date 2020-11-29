//animate glider - change background position every x ms
function animateGlider()
{
	var pos = $('#glider .target').css('background-position');
	var matches = pos.match(/(-?\d+)/g);
	var left = matches[0];
	var top = matches[1];
	left -= 96;
	if (left <= 96 * -4)
	{
		left = 0;
	}
	$('#glider .target').css('background-position', left + 'px ' + top + 'px');

	var diff = (new Date() - $('#glider').data('start')) / 1000;
	for (var i in messages)
	{
		var subMessages = messages[i];
		var message = subMessages[Math.floor(Math.random() * subMessages.length)];
		if (diff >= i)
		{
			delete messages[i];
			$('#glider p').fadeOut(function()
			{
				$(this).html(message).fadeIn();
			});
			break;
		}
	}
}

var messages =
{
	5:
	[
		'downloading your data&hellip;',
		'performing magic tricks&hellip;',
		'reticulating splines&hellip;',
		'making you read this text&hellip;',
		'preparing the awesome&hellip;',
		'cropping avatars&hellip;'
	],

	10:
	[
		'applying final touches&hellip;',
		'antialiasing buttons&hellip;',
		'rendering gradients&hellip;',
		'please wait&hellip;',
		'connecting the dots&hellip;'
	],

	15:
	[
		'[your ad here]',
		'proving P=NP&hellip;',
		'warming up the server&hellip;',
		'breaking fourth wall&hellip;',
		'signing the contract&hellip;'
	],

	22:
	[
		'this shouldn&rsquo;t take much longer.',
		'don&rsquo;t panic yet.',
		'the engine needs some oil.',
		'moving the gears&hellip;',
		'randomizing messages&hellip;'
	],

	30:
	[
		'yep, still loading.',
		'loading just according to keikaku.',
		'analyzing your taste&hellip;',
		'transforming to final form&hellip;'
	],

	40:
	[
		'does your list even end?',
		'you should have clicked harder.',
		'there&rsquo;s a reward at the end.',
		'tick&hellip; tock&hellip;'
	],

	50:
	[
		'the game.',
		'your stats will appear any second now.',
		'good things come to those who wait.',
		'it&rsquo;ll be worth the wait.'
	],

	60:
	[
		'loading&hellip;'
	],

	90:
	[
		'yawn.',
		'hmm&hellip;'
	],

	100:
	[
		'does your list even end?',
		'please wait a bit longer.'
	],

	110:
	[
		'loading&hellip;'
	]
};

//show glider
function showGlider()
{
	$('#glider').data('start', new Date());
	$('#glider').fadeIn('slow');
	window.setInterval(animateGlider, 550);
}

//show glider with short delay
var timeout;
function showGliderDelayed()
{
	timeout = window.setTimeout(showGlider, 550);
}

//attach glider showing to an event to an element
function attachGlider(elems, event)
{
	elems.each(function()
	{
		var target = $(this);
		target.bind(event, function(e, data)
		{
			if (e.isPropagationStopped())
			{
				return true;
			}
			//supress showing glider on middle mouse button click
			if (!(e.type == 'click' && e.which == 2))
			{
				showGliderDelayed();
			}
			return true;
		});
	});
}

$(function()
{
	//fix some weird history issues on some browsers
	$(window).unload(function()
	{
		if (timeout)
		{
			window.clearTimeout(timeout);
		}
	});

	attachGlider($('a.waitable, button.waitable'), 'click');
	attachGlider($('form.waitable'), 'submit');
});
