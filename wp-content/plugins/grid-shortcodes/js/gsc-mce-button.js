(function() {
	tinymce.PluginManager.add('gsc_mce_button', function( editor, url ) {
		editor.addButton( 'gsc_mce_button', {
			icon: 'gsc-icon',
            tooltip: 'Grid Shortcodes',
			type: 'menubutton',
			menu: [
				{
				    text: 'Two halfs',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="half"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="half"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'Three thirds',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="third"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="third"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="third"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'One third & two thirds',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="third"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="two-thirds"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'Two thirds & a third',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="two-thirds"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="third"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'Four quarters',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'A half & two quarters',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="half"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'Two quarters & a half',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="half"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'Three quarters & a quarter',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="three-quarters"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
                {
				    text: 'A quarter & three quarters',
				    onclick: function() {
				    	editor.insertContent('[GDC_row]<br/>[GDC_column size="quarter"]<br/>Your content here<br/>[/GDC_column]<br/>[GDC_column size="three-quarters"]<br/>Your content here<br/>[/GDC_column]<br/>[/GDC_row]');
				    },
                },
			]
		});
	});
})();