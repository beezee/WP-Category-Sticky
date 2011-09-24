<?php

class WpJqMultiSelect
{
    protected $_url;
    protected $_elements;
    protected $_styleurl;

    public function __construct(array $elements, $admin=false, $thestyleurl=false)
        {
            $this->_urls = $this->get_urls();
            $this->_styleurl = $this->set_style_url($thestyleurl);
            $this->_elements = $elements;
            $this->add_actions($admin);
        }
    
    public function get_urls()
        {
            $parts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
	    $plugindir_parts = explode(DIRECTORY_SEPARATOR, WP_PLUGIN_DIR);
            $filename = basename(__FILE__);
            unset($parts[array_search($filename, $parts)]);
	    foreach ($parts as $key => $part) if (in_array($part, $plugindir_parts)) unset($parts[$key]);
            $dir = join(DIRECTORY_SEPARATOR, $parts);
            $base_url = path_join(WP_PLUGIN_URL, $dir);
            return array( 'base_url' => $base_url, 'js_url' => path_join($base_url, 'js'), 'style_url' => path_join($base_url, 'css'));
        }
    
    public function set_style_url($thestyleurl)
        {
            if ($thestyleurl) return $thestyleurl; else return path_join($this->_urls['style_url'], 'jqui.css');
        }
        
    public function add_actions($admin)
        {
            if ($admin) :
                add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));
                add_action('admin_print_styles', array($this, 'enqueue_styles'));
            else:
                add_action('wp_enqueue_scripts', array($this, 'enqueue_js'));
                add_action('wp_print_styles', array($this, 'enqueue_styles'));
            endif; 
        }
 
    public function enqueue_js()
	{
		wp_register_script('jquiwidget', path_join($this->_urls['js_url'], 'jquery.ui.widget.js') , array('jquery'));
                wp_register_script('jqmultiselect', path_join($this->_urls['js_url'], 'ui.multiselect.js') , array('jquery', 'jquiwidget', 'jquery-ui-core'));
                wp_register_script('bzwpmultiselect', path_join($this->_urls['js_url'], 'bzwp_multiselect.js') , array('jquery', 'jquery-ui-core', 'jqmultiselect'));
                wp_enqueue_script('jqmultiselect');
		wp_enqueue_script('bzwpmultiselect');
                $count = 0;
                foreach ($this->_elements as $element) {$elements['element'.$count] = $element; $count++;}
                wp_localize_script('bzwpmultiselect', 'elements', $elements);  
	}
        
    public function enqueue_styles()
        {
            $styleurl = $this->_styleurl;
            wp_register_style('bz_wp_multiselect_jqui', $styleurl);
            wp_enqueue_style('bz_wp_multiselect_jqui');
        }
        
        
}