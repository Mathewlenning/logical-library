# Logical\Widget
The logical widget rendering system is a markup abstraction layer designed to provide a cleaner separation of markup and program logic.

This allows you to reuse markup to reduce duplication within the presentation layer, decrease procedural code within you application, prevent hardcoded CSS framework dependencies.

I designed this widget system to be infinitely expandable which makes it easy to integrate with external systems without altering the widget system.

I hope you find this useful and that it helps make your application better than ever!

## Getting Started

```php

	// First set any controls we need
	\Logical\Widget\WidgetRenderer::setControls(
	array(
		'foreach' => '\Logical\Widget\Control\ForeachControl',
		'var' => '\Logical\Widget\Control\VarControl',
    	'widget' => '\Logical\Widget\Control\WidgetControl'
    	)
    )
    
    // Next we set our template dir and file
    $templatePath = '/path/to/template';
    \Logical\Widget\WidgetRenderer::setSearchPaths($templatePath);
    
    $templateFile = 'bootstrap-v2.xml';
    \Logical\Widget\WidgetRenderer::setTemplateFile($templateFile);
    
    // Finally we create our widget render and start rendering widgets
    $widgetRenderer = new \Logical\Widget\WidgetRenderer();
    
    echo $widgetRenderer->render('widget.template.id');
    
```

In the above example we echoed the widget to the output, but its important to remember that `WidgetRenderer::render` actually returns an instance of WidgetElement which can be further manipulated before echoing.

```

	$widget = $widgetRenderer->render('widget.template.id');
	
	if ($someThing == true)
	{
		$widget->addClass('active');
	}
	
	echo $widget;
```

## Template XML

The template xml file is a single file containing one or more template definitions.

```xml

    <widgets>
	    <template id="list.state.control">
		    <a href="@href" class="btn btn-mini @class">
        	    <i class="icon-state-icon"/>	
    	    </a>
	   </template>
	   
	   <template id="list.check.all.control">
       		<input type="checkbox" name="checkall-toggle" value="" class="hasTooltip" title="LOGICAL_CHECK_ALL" onclick="logical.form.checkAll(event);"/>
       </template>
       
        <template id="filter.list.pagination">
        	<div class="pagination pagination-toolbar">
            	<ul class="pagination-list">
                	<foreach data-source="@page_links">
                    	<widget data-source="@widgetId"/>
                	</foreach>
            	</ul>
         	</div>
        </template>
    </widgets>
```

As you can see I've tried to keep the syntax as close to the HTML specification as possible. In the above template file
there are three widget templates. 

Attributes prefixed with the `@` symbol are replaced with dynamic values from the data array provided when they are rendered.

## Rendering simpleXMLElements

In addition to the `WidgetRenderer::render` which allows you to render widgets from the template xml file, you can also use `WidgetRenderer::renderElement` to render SimpleXMLElements directly.

```php
		
	$xml = simplexml_load_file("myXmlFile.xml");
	echo $widgetRenderer->renderElement($xml, $data);
```

## WidgetControl system

If you look at the first example again you'll notice I set some controls to the widget renderer before initialising it.

```php

		// First set any controls we need
    	\Logical\Widget\WidgetRenderer::setControls(
    	array(
    		'foreach' => '\Logical\Widget\Control\ForeachControl',
    		'var' => '\Logical\Widget\Control\VarControl',
        	'widget' => '\Logical\Widget\Control\WidgetControl'
        	)
        )
```

With this I am defining my template language. By default all tags are converted into a `WidgetElement`, unless a control has been assigned to the tag name.

In the above declaration I've defined the tag name "foreach", "var", and "widget" as control structures. 

So when the renderer encounters a simpleXMLElement with one of those names, it will pass it, and the display data to the assigned control.

This makes the widget rendering system very easy to integrate with any system. You can add new functionality by simply implementing the control and assigning it to the renderer.

## ACL

The widget system has a built-in acl mechanism. Use ACL you need to add both `data-action` and `data-asset` properties to tags which require permission checks.

Any tag that contains those properties will be checked Access object provided either in the constructor or during rendering. 

The actual ACL system is not included, so you'll also need to implement or integrate with your systems ACL for it to work. 

## Dependencies

### Logical Dependencies

* Logical\Access\AccessInterface
* Logical\Access\UserAccess
* Logical\Registry\Registry

### Joomla! Dependencies

At the moment there are a few Joomla! CMS class dependencies. However I'm working on replacing those dependencies with home grown alternatives in the near future.

* JText
* JRegistry

