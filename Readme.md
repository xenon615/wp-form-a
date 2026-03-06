## About  
This plugin is designed to simplify the task of creating various forms that can be used in the WordPress admin panel.  

#### It is based on [form-a-js](https://github.com/xenon615/form-a-js) so for the details of the form definition please refer there.  

## Installation

Place folder "form-a" in  "plugins" folder.  Then activate it.  
To use this plugin as a as "Must Use plugin", place it in the "mu-plugins" folder and create a form-a.php file next to it with the content.
```php
<?php
require WPMU_PLUGIN_DIR . '/form-a/index.php';

```


## Usage 
Let's say we want to create a form called "my-cool-settings".

First, let's prepare a place for it.


```php
    function get_page() {
        //wp_enqueue_media();  
        //wp_enqueue_editor();
        // uncomment lines above when your form useв wysiwyg or media field ()
        echo 
        '<div>
            <form method="POST">
                <div id="my-cool-settings" class="form-a-placeholder"></div>
            </form>
        </div>';
    }

```

Secondly, we will announce our desire to have a form :)  (something like this)

```php

add_filter('form-a_need-a-form', function($forms) {
    $screen = get_current_screen();
    if ($screen->id == 'my-cool-settings-page') {
        $forms = [
            'my-cool-settings' => ['remoteLoad' => true],  
        ];
    }
    return $forms;
});

```

Next, if you chose to remotely (separately) get the form definition (personally, I always do this), we need to give the form definition on request.  
For example:  

```php

add_filter('form-a_form_load', function($form, $formSlug) {
    if ($formSlug == 'my-cool-settings') {
        $form = [
            'def' => [
                'title' => 'My cool settings',
                'remoteSubmit' => true,   
                'buttons' => [
                    [
                        'text' => 'Update Settings',
                        'classes' => ['button', 'button-primary','button-large'],
                        'type' => 'submit'
                    ]   
                ],    
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'my-param1',
                        'label' => 'My Param1',
                        'classes' => ['col-6'],
                    ],
                    //  ...
                ]
            ],
            'data' => get_my_cool_settings()   // or []
        ];
    } 
    return $form;
}, 10, 2);


```



And finally, processing the form submission (for forms submitted by AJAX)

```php

add_filter('form-a_submit', function($result, $data, $slug) {
    if ($slug == 'my-cool-settings') {
        save_my_cool_settings($data);
        $result['message'] = 'My Cool settings saved successfully!';
    } 
    return $result;
}, 10, 3);

```

