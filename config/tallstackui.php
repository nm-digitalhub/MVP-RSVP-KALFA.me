<?php

use TallStackUi\Components;

return [
    /*
    |--------------------------------------------------------------------------
    | Prefix
    |--------------------------------------------------------------------------
    |
    | Control a prefix for the TallStackUI components. The term here will be used
    | to prefix all TallStackUI components. This is useful to avoid conflicts
    | with other components registered by other libraries or created by yourself.
    |
    | For example, prefixing as 'ts-', the `alert` usage will be: '<x-ts-alert />'
    */
    'prefix' => env('TALLSTACKUI_PREFIX', 'ts-'),

    /*
    |--------------------------------------------------------------------------
    | Color Classes Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace related to classes used for component color personalization.
    */
    'color_classes_namespace' => env('TALLSTACKUI_COLOR_CLASSES_NAMESPACE', 'App\\View\\Components\\TallStackUi\\Colors'),

    /*
    |--------------------------------------------------------------------------
    | Invalidate Components
    |--------------------------------------------------------------------------
    |
    | Controls the "invalidation" of all form components globally. The "invalidate"
    | is the way to prevent showing validation errors in the components. When you
    | set this value as "true," you will use "invalidate" of all form components
    | globally, without the need to specific it individually per component.
    */
    'invalidate_global' => false,

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Control the debug mode for TallStackUI components.
    */
    'debug' => [
        'status' => env('TALLSTACKUI_DEBUG_MODE', false),

        /*
        |----------------------------------------------------------------------
        | Controls the environments where the debug mode can be enabled.
        |----------------------------------------------------------------------
        */
        'environments' => [
            'local',
            'sandbox',
            'staging',
        ],

        /*
        |----------------------------------------------------------------------
        | Ignore debug mode for specific components.
        |----------------------------------------------------------------------
        */
        'ignore' => [
            // Components\Alert\Component::class,
            // Components\Avatar\Component::class
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component List
    |--------------------------------------------------------------------------
    |
    | List of all TallStackUI components and their possible settings.
    */
    'components' => [
        'alert' => Components\Alert\Component::class,
        'back-to-top' => Components\BackToTop\Component::class,
        'avatar' => Components\Avatar\Component::class,
        'avatar.group' => Components\Avatar\Group\Component::class,
        'badge' => Components\Badge\Component::class,
        'banner' => Components\Banner\Component::class,
        'breadcrumbs' => [
            Components\Breadcrumbs\Component::class,
            [
                'files' => [
                    'routes/breadcrumbs.php',
                ],
            ],
        ],
        'boolean' => Components\Boolean\Component::class,
        'button' => Components\Button\Normal\Component::class,
        'button.circle' => Components\Button\Circle\Component::class,
        'carousel' => Components\Carousel\Component::class,
        'card' => Components\Card\Component::class,
        'checkbox' => Components\Form\Checkbox\Component::class,
        'color' => [
            Components\Form\Color\Component::class,
            [
                'custom' => [],
            ],
        ],
        'clipboard' => Components\Clipboard\Component::class,
        'command-palette' => [
            Components\CommandPalette\Component::class,
            [
                'actionable' => null,
                'request' => null,
                'z-index' => 'z-50',
                'blur' => false,
                'overflow' => false,
                'shortcut' => 'ctrl.k',
                'recycle' => true,
                'elements' => true,
                'scrollbar' => true,
                'centered' => false,
            ],
        ],
        'currency' => Components\Form\Currency\Component::class,
        'date' => Components\Form\Date\Component::class,
        'dialog' => [
            Components\Dialog\Component::class,
            [
                'z-index' => 'z-50',
                'overflow' => false,
                'blur' => false,
                'persistent' => false,
            ],
        ],
        'dial' => Components\Dial\Main\Component::class,
        'dial.items' => Components\Dial\Items\Component::class,
        'dropdown' => Components\Dropdown\Main\Component::class,
        'dropdown.items' => Components\Dropdown\Items\Component::class,
        'dropdown.submenu' => Components\Dropdown\Submenu\Component::class,
        'environment' => Components\Environment\Component::class,
        'error' => Components\Form\Error\Component::class,
        'errors' => Components\Errors\Component::class,
        'floating' => Components\Floating\Component::class,
        'hint' => Components\Form\Hint\Component::class,
        'icon' => [
            Components\Icon\Component::class,
            [
                'type' => env('TALLSTACKUI_ICON_TYPE', 'heroicons'),
                'style' => env('TALLSTACKUI_ICON_STYLE', 'solid'),
                'custom' => [
                    'guide' => [
                        'arrow-path' => null,
                        'arrow-trending-up' => null,
                        'arrow-trending-down' => null,
                        'arrow-up-tray' => null,
                        'bars-4' => null,
                        'calendar' => null,
                        'check' => null,
                        'check-circle' => null,
                        'chevron-down' => null,
                        'chevron-left' => null,
                        'chevron-right' => null,
                        'chevron-up' => null,
                        'chevron-up-down' => null,
                        'clipboard' => null,
                        'clipboard-document' => null,
                        'cloud-arrow-up' => null,
                        'clock' => null,
                        'document-check' => null,
                        'document-text' => null,
                        'exclamation-circle' => null,
                        'eye' => null,
                        'eye-slash' => null,
                        'information-circle' => null,
                        'magnifying-glass' => null,
                        'minus' => null,
                        'moon' => null,
                        'photo' => null,
                        'plus' => null,
                        'question-mark-circle' => null,
                        'swatch' => null,
                        'sun' => null,
                        'trash' => null,
                        'x-circle' => null,
                        'x-mark' => null,
                    ],
                ],
            ],
        ],
        'input' => Components\Form\Input\Component::class,
        'input.select' => Components\Form\InputSelect\Component::class,
        'label' => Components\Form\Label\Component::class,
        'layout' => [
            Components\Layout\Main\Component::class,
            [
                'ignore' => env('TALLSTACKUI_IGNORE_LAYOUT_REGISTRATION', false),
            ],
        ],
        'layout.header' => Components\Layout\Header\Component::class,
        'link' => Components\Link\Component::class,
        'loading' => [
            Components\Loading\Component::class,
            [
                'z-index' => 'z-50',
                'overflow' => false,
                'blur' => false,
                'opacity' => true,
            ],
        ],
        'kbd' => Components\Kbd\Component::class,
        'key-value' => Components\KeyValue\Component::class,
        'modal' => [
            Components\Modal\Component::class,
            [
                'z-index' => 'z-50',
                'overflow' => false,
                'blur' => false,
                'persistent' => false,
                'size' => '2xl',
                'center' => false,
                'scrollable' => false,
                'scrollbar' => 'thin',
            ],
        ],
        'number' => Components\Form\Number\Component::class,
        'password' => [
            Components\Form\Password\Component::class,
            [
                'rules' => [
                    'min' => '8',
                    'mixed' => true,
                    'numbers' => true,
                    'symbols' => '!@#$%^&*()_+-=',
                ],
            ],
        ],
        'pin' => Components\Form\Pin\Component::class,
        'progress' => Components\Progress\Bar\Component::class,
        'progress.circle' => Components\Progress\Circle\Component::class,
        'radio' => Components\Form\Radio\Component::class,
        'range' => Components\Form\Range\Component::class,
        'rating' => Components\Rating\Component::class,
        'side-bar' => Components\Layout\SideBar\Main\Component::class,
        'side-bar.item' => Components\Layout\SideBar\Item\Component::class,
        'side-bar.separator' => Components\Layout\SideBar\Separator\Component::class,
        'select.native' => Components\Form\Select\Native\Component::class,
        'select.styled' => [
            Components\Form\Select\Styled\Component::class,
            [
                'unfiltered' => false,
                'recycle' => false,
            ],
        ],
        'signature' => Components\Signature\Component::class,
        'slide' => [
            Components\Slide\Component::class,
            [
                'z-index' => 'z-50',
                'overflow' => false,
                'blur' => false,
                'persistent' => false,
                'size' => 'lg',
                'position' => 'right',
            ],
        ],
        'stats' => Components\Stats\Component::class,
        'step' => Components\Step\Main\Component::class,
        'step.items' => Components\Step\Items\Component::class,
        'tab' => Components\Tab\Main\Component::class,
        'tag' => Components\Form\Tag\Component::class,
        'table' => Components\Table\Component::class,
        'tab.items' => Components\Tab\Items\Component::class,
        'textarea' => Components\Form\Textarea\Component::class,
        'theme-switch' => Components\ThemeSwitch\Component::class,
        'time' => Components\Form\Time\Component::class,
        'toast' => [
            Components\Toast\Component::class,
            [
                'z-index' => 'z-50',
                'progress' => true,
                'expandable' => false,
                'position' => 'top-right',
                'timeout' => 3,
            ],
        ],
        'toggle' => Components\Form\Toggle\Component::class,
        'tooltip' => Components\Tooltip\Component::class,
        'upload' => Components\Form\Upload\Component::class,
        'reaction' => Components\Reaction\Component::class,
        'wrapper.input' => Components\Wrapper\Input\Component::class,
        'wrapper.radio' => Components\Wrapper\Radio\Component::class,
    ],
];
