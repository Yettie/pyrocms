<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @package 		PyroCMS
 * @subpackage 	Menu Builder
 * @author		Stephen Cozart
 * 
 * Allows you to more easily create navigation menus and display them as a widget
 */

class Menu_builder extends Widgets
{
	public $title = 'Menu Builder';
	public $description = 'Allows you to more easily create navigation menus and display them as a widget';
	public $author = 'Stephen Cozart';
	public $website = 'http://github.com/clip/pyrocms-widgets';
	public $version = '1.1';
	
	public $fields = array(
		array(
			'field'   => 'link_list',
			'label'   => 'Link List',
			'rules'   => 'xss_clean|trim'
		)
	);

	public function run($options)
	{
		
		$links = explode('|', $options['link_list']);
		
		return array('links' => $links);
	}
	
	public function form()
	{
		$output = FALSE;
		
		$m = new $this->module_m();
		$w = new $this->widget_m();
		
		$widget = $w->get_by('slug', strtolower(get_class()));
		
		$widget_data = $w->get_instance($widget->id);
		
		$links = array();
		
		if(!empty($widget_data->options)):
			$options = unserialize($widget_data->options);
			$links = explode('|', $options['link_list']);
			$output['links_array'] = $links;
		endif;
		
		
		//build array of links to modules
		$modules = $m->get_all(array('is_frontend' => 1));
		$module_links = array();
		if(sizeof($modules > 0)):
		
			foreach($modules as $module):
				$link = anchor($module['slug'], ucfirst($module['slug']));
				if(!in_array($link, $links)):
					$module_links[] = $link;
				endif;
			endforeach;
			$output['modules'] = $module_links;
		endif;
		
		//build array of links to pages
		$this->load->model('pages/pages_m');
		$p = new $this->pages_m();
		
		$pages = $p->get_many_by('status', 'live');
		$page_links = array();
		if(!empty($pages)):		
			foreach($pages as $page):
				$link = anchor($page->slug, $page->title);
				if(!in_array($link, $links)):
					$page_links[] = $link;
				endif;
			endforeach;
			$output['pages'] = $page_links;
		endif;
		
		return $output;
	}
	
	
	
}
