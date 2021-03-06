<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * Parser Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Parser
 * @author		Phil Sturgeon
 * @link		http://philsturgeon.co.uk/code/codeigniter-dwoo
 * @version		2.0
 */
include(APPPATH . 'libraries/dwoo/dwooAutoload.php');

class MY_Parser extends CI_Parser {

	private $_ci;
	private $_dwoo;
	private $_parser_compile_dir = '';
	private $_parser_cache_dir = '';
	private $_parser_cache_time = 0;
	private $_parser_allow_php_tags = array();
	private $_parser_allowed_php_functions = array();
	private $_parser_assign_refs = array();

	function __construct($config = array())
	{
		if (!empty($config))
		{
			$this->initialize($config);
		}

		$this->_ci = & get_instance();
		$this->_dwoo = self::spawn();
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			$this->{'_' . $key} = $val;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Spawn Dwoo instance
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function spawn()
	{
		// Main Dwoo object
		$dwoo = new Dwoo;

		// The directory where compiled templates are located
		$dwoo->setCompileDir($this->_parser_compile_dir);
		$dwoo->setCacheDir($this->_parser_cache_dir);
		$dwoo->setCacheTime($this->_parser_cache_time);

		// Security
		$security = new MY_Security_Policy;

		$security->setPhpHandling($this->_parser_allow_php_tags);
		$security->allowPhpFunction($this->_parser_allowed_php_functions);

		$dwoo->setSecurityPolicy($security);

		return $dwoo;
	}

	// --------------------------------------------------------------------

	/**
	 *  Parse a view file
	 *
	 * Parses pseudo-variables contained in the specified template,
	 * replacing them with the data in the second param
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	function parse($template, $data = array(), $return = FALSE, $is_include = FALSE)
	{
		$string = $this->_ci->load->view($template, $data, TRUE);

		return $this->_parse($string, $data, $return, $is_include);
	}

	// --------------------------------------------------------------------

	/**
	 *  String parse
	 *
	 * Parses pseudo-variables contained in the string content,
	 * replacing them with the data in the second param
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	function string_parse($string, $data = array(), $return = FALSE, $is_include = FALSE)
	{
		return $this->_parse($string, $data, $return, $is_include);
	}

	function parse_string($string, $data = array(), $return = FALSE, $is_include = FALSE)
	{
		return $this->_parse($string, $data, $return, $is_include);
	}

	// --------------------------------------------------------------------

	/**
	 *  Parse
	 *
	 * Parses pseudo-variables contained in the specified template,
	 * replacing them with the data in the second param
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	function _parse($string, $data, $return = FALSE, $is_include = FALSE)
	{
		// Start benchmark
		$this->_ci->benchmark->mark('dwoo_parse_start');

		// Convert from object to array
		if (!is_array($data))
		{
			$data = (array) $data;
		}

		$data = array_merge($data, $this->_ci->load->_ci_cached_vars);

		try
		{
			// TAG SUPPORT
			$this->_ci->load->library('tags');
			$this->_ci->tags->set_trigger('pyro:');
			$parsed = $this->_ci->tags->parse($string, $data, array($this, 'parser_callback'));
			// END TAG SUPPORT

			foreach ($this->_parser_assign_refs as $ref)
			{
				$data[$ref] = & $this->_ci->{$ref};
			}

			// Object containing data
			$dwoo_data = new Dwoo_Data;
			$dwoo_data->setData($data);

			// Object of the template
			$tpl = new Dwoo_Template_String($parsed['content']);

			$dwoo = $is_include ? self::spawn() : $this->_dwoo;

			// render the template
			$parsed_string = $dwoo->get($tpl, $dwoo_data);
		}

		catch (Exception $e)
		{
			show_error($e);
		}

		// Finish benchmark
		$this->_ci->benchmark->mark('dwoo_parse_end');

		// Return results or not ?
		if (!$return)
		{
			$this->_ci->output->append_output($parsed_string);
			return;
		}

		return $parsed_string;
	}

	// ------------------------------------------------------------------------

	/**
	 * Callback from template parser
	 *
	 * @param	array
	 * @return 	mixed
	 */
	public function parser_callback($data)
	{
		if ( ! isset($data['segments'][0]) OR ! isset($data['segments'][1]))
		{
			return FALSE;
		}

		// Setup our paths from the data array
		$class = $data['segments'][0];
		$method = $data['segments'][1];
		$addon = strtolower($class);
		$return_data = '';

		// Get active add-ons
		$this->_ci->load->model('modules/module_m');
		$addons = $this->_ci->module_m->get_all();

		foreach ($addons as $item)
		{
			// First check core addons then 3rd party
			if ($item['is_core'] == 1)
			{
				$addon_path = APPPATH.'modules/'.$class.'/libraries/'.ucfirst($class).'.plugin'.EXT;
				if ( ! file_exists($addon_path))
				{
					log_message('error', 'Unable to load: '.$class);
					$return = FALSE;
				}
				else
				{
					include_once($addon_path);
					$class_name = 'Plugin_'.$class;
					$class_init = new $class_name;
					$return_data = $this->_process($class_init, $method, $data);
					break;
				}
			}
			else
			{
				$addon_path = ADDONPATH.'modules/'.$class.'/libraries/'.$class.'.plugin'.EXT;
				$library_path = ADDONPATH.'modules/libraries/'.$class.'.plugin'.EXT;

				// First check addon_path
				if (file_exists($addon_path))
				{
					// Load it up
					include_once($addon_path);
					$class_name = 'Plugin_'.$class;
					$class_init = new $class_name;

					// How about a language file?
					$lang_path = ADDONPATH.'modules/'.$class.'/language/'.$this->_ci->config->item('language').'/'.$addon.'_lang'.EXT;
					if (file_exists($lang_path))
					{
						$this->_ci->lang->load($addon.'/'.$addon);
					}

					// Now the fun stuff!
					$return_data = $this->_process($class_init, $method, $data);
					break;
				}
				elseif (file_exists($library_path))
				{
					// Load it up
					include_once($library_path);
					$class_name = 'Plugin_'.$class;
					$class_init = new $class_name;

					// Now the fun stuff!
					$return_data = $this->_process($class_init, $method, $data);
					break;
				}
				else
				{
					log_message('error', 'Unable to load: '.$class);
					$return = FALSE;
				}
			}
		}

		if (is_array($return_data))
		{
			if ( ! $this->_is_multi($return_data))
			{
				$return_data = $this->_make_multi($return_data);
			}

			$content = $data['content'];
			$parsed_return = '';
			$simpletags = new Tags();
			foreach ($return_data as $result)
			{
				$parsed = $simpletags->parse($content, $result);
				$parsed_return .= $parsed['content'];
			}
			unset($simpletags);

			$return_data = $parsed_return;
		}

		return $return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Process
	 *
	 * Just process the class
	 *
	 * @access	private
	 * @param	object
	 * @param	string
	 * @param	array
	 * @return	mixed
	 */
	private function _process($class, $method, $data)
	{
		if (method_exists($class, $method))
		{
			return $class->$method($data);
		}
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Ensure we have a multi array
	 *
	 * @param	array
	 * @return 	int
	 */
	private function _is_multi($array)
	{
		return (count($array) != count($array, 1));
	}

	// --------------------------------------------------------------------

	/**
	 * Forces a standard array in multidimensional.
	 *
	 * @param	array
	 * @param	int		Used for recursion
	 * @return	array	The multi array
	 */
	private function _make_multi($flat, $i=0)
	{
	    $multi = array();
		$return = array();
	    foreach ($flat as $item => $value)
	    {
	        $return[$i][$item] = $value;
	    }
	    return $return;
	}
}

class MY_Security_Policy extends Dwoo_Security_Policy {

	public function callMethod(Dwoo_Core $dwoo, $obj, $method, $args)
	{
		return call_user_func_array(array($obj, $method), $args);
	}

	public function isMethodAllowed()
	{
		return TRUE;
	}

}

// END MY_Parser Class

/* End of file MY_Parser.php */
/* Location: ./application/libraries/MY_Parser.php */