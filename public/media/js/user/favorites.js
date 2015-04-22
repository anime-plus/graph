$(function()
{
	$('.genres, .creators').each(function()
	{
		var section = $(this);
		section.find('.entries-trigger').click(function(e)
		{
			e.preventDefault();
			var target = $(this);
			toggleEntries(section.find('.entries-wrapper'),
				{'sender': target.attr('data-sender'), 'filter-param': target.attr('data-id')},
				true,
				function()
				{
					section.find('.entries-wrapper-row').insertAfter(target.parents('tr'));
				}
			);
		});
	});

	$.tablesorter.addWidget(
	{
		id: 'ord',
		format: function(table)
		{
			for (var i = 0; i < table.tBodies[0].rows.length; i ++)
			{
				$('tbody tr:not(.entries-wrapper-row):eq(' + i + ') td.ord', table).text(i + 1);
			}
		}
	});

	var opt = {
		headers:
		{
			0:
			{
				sorter: false
			},
			5:
			{
				sorter: 'percent'
			}
		},

		widgets:
		[
			'ord'
		],

		sortList:
		[
			[4,1]
		]
	};

	$('table').tablesorter(opt);

	$('.toggle-decades-msg').click(function(e)
	{
		//decadesChart.series[1].setVisible(!decadesChart.series[1].visible);
		if (decadesChart.series[0].visible && decadesChart.series[1].visible)
		{
			decadesChart.xAxis[0].axisTitle.attr({text: 'Years'});
			decadesChart.series[0].show();
			decadesChart.series[1].hide();
		}
		else if (decadesChart.series[0].visible)
		{
			decadesChart.xAxis[0].axisTitle.attr({text: 'Decades'});
			decadesChart.series[0].hide();
			decadesChart.series[1].show();
		}
		else
		{
			decadesChart.xAxis[0].axisTitle.attr({text: 'Years and decades'});
			decadesChart.series[0].show();
			decadesChart.series[1].show();
		}
		e.preventDefault();
	});
});
