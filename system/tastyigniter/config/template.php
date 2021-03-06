<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Breadcrumb Config
| -------------------------------------------------------------------
| This will contain some breadcrumbs settings.
|
| $config['breadcrumb_divider']			The string used to separate each breadcrumb link
| $config['breadcrumb_tag_open'] 		The opening tag for breadcrumbs container.
| $config['breadcrumb_tag_close'] 		The closing tag for breadcrumbs container.
| $config['breadcrumb_link_open'] 		The opening tag for breadcrumb link.
| $config['breadcrumb_link_close'] 		The closing tag for breadcrumb link.
|
*/
$config['breadcrumb_divider'] 		= '<span class="divider">/</span>';
$config['breadcrumb_tag_open'] 		= '<div id="breadcrumb" class="btn-group btn-breadcrumb">';
$config['breadcrumb_tag_close'] 	= '</div>';
$config['breadcrumb_link_open'] 	= '<a href="{link}" class="btn btn-default">';
$config['breadcrumb_link_close'] 	= '</a>';

/*
|--------------------------------------------------------------------------
| Title Separator
|--------------------------------------------------------------------------
|
| What string should be used to separate title segments sent via $this->template->title('Foo', 'Bar');
|
|   Default: ' | '
|
*/

$config['title_separator'] = ' - ';

/*
|--------------------------------------------------------------------------
| Theme
|--------------------------------------------------------------------------
|
| Which theme to use by default?
|
| Can be overriden with $this->template->set_theme('foo');
|
|   Default: ''
|
*/

$config['theme'] = '';

/*
|--------------------------------------------------------------------------
| Default template head tags
|--------------------------------------------------------------------------
|
| Which template head tag is allowed by default?
|
|
|
*/

$config['head_tags']['doctype'] = 'html5';
$config['head_tags']['favicon'] = '';
$config['head_tags']['meta'] = array();
$config['head_tags']['title'] = '';
$config['head_tags']['style'] = array();
$config['head_tags']['script'] = array();
$config['head_tags']['heading'] = '';
$config['head_tags']['buttons'] = array();
$config['head_tags']['icons'] = array();
$config['head_tags']['back_button'] = '';

/*
|--------------------------------------------------------------------------
| Theme Locations
|--------------------------------------------------------------------------
|
| Where should we expect to see themes?
|
|	Default: array(VIEWPATH.'themes/') in the views folder
|
*/

$config['theme_locations'] = array(
    THEMEPATH,
);