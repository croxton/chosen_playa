<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Chosen Playa
 *
 * This file must be in your /system/third_party/Chosen_playa directory of your ExpressionEngine installation
 *
 * @package             Chosen Playa (EE2)        
 * @author              Mark Croxton                        
 * @copyright			Copyright (c) 2011 Mark Croxton     
 * @link                http://hallmark-design.co.uk    
 */

// include Playa fieldtype class
// => rewrite protected methods to protected so we can extend without replicating Brandon's code
if ( ! class_exists('Playa_ft2'))
{
	$playa_ft2 = file_get_contents(PATH_THIRD.'playa/ft.playa'.EXT);
	$playa_ft2 = str_replace('private function', 'protected function', $playa_ft2);
	$playa_ft2 = str_replace('class Playa_ft', 'class Playa_ft2', $playa_ft2);
	$playa_ft2 = str_replace('<?php', '', $playa_ft2);
	eval($playa_ft2);
}

class Chosen_playa_ft extends Playa_ft2 {

	public $info = array(
		'name'		=> 'Chosen Playa',
		'version'	=> '1.0.0'
	);
	
	protected static $init = TRUE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
		$this->site_id = $this->EE->config->item('site_id');	
	}
	
   /**
	* Default Settings
	*
	* @param array
	* @return array settings
	*/
	protected function _default_settings($data)
	{
		return array_merge(array(
			"allow_multiple"	=> 'n',
			"min_width"			=> '',
			"select_msg"		=> '',
			"channels" 			=> array(),
			"cats"				=> array(),
			"authors"			=> array(),
			"statuses"			=> array(),
		), $data);
	}
	
	/**
	 * Field Settings
	 */
	protected function _field_settings($data)
	{
		$data = $this->_default_settings($data);
		
		return array(
			
			// Allow multiple selections?
			array(
				'Allow multiple selections?',
				$this->_build_multi_radios($data['allow_multiple'], 'allow_multiple')
			),
			
			// Min width
			array(
				'Minimum width of element (px)?',
				form_input($this->field_id.'_field_settings[min_width]', $data['min_width'])
			),
			
			// Default label
			array(
				'Default label when empty',
				form_input($this->field_id.'_field_settings[select_msg]', $data['select_msg'])
			),
			
			// Channels
			array(
				'Channels',
				$this->_channels_select($data['channels'])
			),

			// Categories
			array(
				'Categories',
				$this->_cats_select($data['cats'])
			),

			// Authors
			array(
				'Authors',
				$this->_authors_select($data['authors'])
			),

			// Statuses
			array(
				'Statuses',
				$this->_statuses_select($data['statuses'])
			),
		);
	}
	
	/**
	 * Builds a string of yes/no radio buttons
	 */
	private function _build_multi_radios($data, $name)
	{
		$name = $this->field_id.'_field_settings['.$name.']';
		
		return form_radio($name, 'y', ($data == 'y') ) . NL
			. 'Yes' . NBS.NBS.NBS.NBS.NBS . NL
			. form_radio($name, 'n', ($data == 'n') ) . NL
			. 'No';
	}

	/**
	 * Save the custom field settings
	 * 
	 * @return boolean Valid or not
	 */
	public function save_settings()
	{	
		// remove empty values
		$new_settings = array_filter( $this->EE->input->post('chosen_playa_field_settings') );
		return $new_settings;
	}
	
	/**
	 * Display Global Settings
	 */
	function display_global_settings()
	{
		$this->EE->cp->add_to_head('
			<script type="text/javascript">
			$(document).ready(function() {
				$(\'.pageContents input\').attr("value", "OK");
			});				
			</script>');
			
		return '<p>This fieldtype requires a valid license for <a href="http://pixelandtonic.com/playa">Playa</a>.</p>';	
	}
	
	/**
	 * Publish form validation
	 * 
	 * @param $data array Contains the submitted field data.
	 * @return mixed TRUE or an error message
	 */
	public function validate($data)
	{
		// is this a required field?
		if ($this->settings['field_required'] == 'y')
		{
			// make sure there are selections
			if (! isset($data['selections']) || ! array_filter($data['selections']))
			{
				return lang('required');
			}
		}

		return TRUE;
	}	
	
	/**
	 * Save Field
	 */
	function save($data)
	{	
		// ignore everything but the selections
		$selections = array_filter( (is_array($data) ? $data : array($data)) );

		// save the post data for later
		$this->cache['selections'][$this->settings['field_id']] = $selections;

		// just return 'y' if there are any selections
		// for the sake of Required field validation
		return count($selections) > 0 ? 'y' : '';
	}
	
	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		// ignore everything but the selections
		$selections = array_filter( (is_array($data) ? $data : array($data)) );

		// save the post data for later
		if (! isset($this->cache['selections'][$this->settings['field_id']])) $this->cache['selections'][$this->settings['field_id']] = array();
		if (! isset($this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']])) $this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']] = array();
		$this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']][$this->settings['row_name']] = $selections;

		// just return 'y' if there are any selections
		// for the sake of Required field validation
		return count($selections) > 0 ? 'y' : '';
	}
	
	// --------------------------------------------------------------------
		
	/**
     * Normal Fieldtype Display
     */
	public function display_field($data)
	{	
		$selected = $this->_get_selected_entry_ids($data);
		return $this->_display_field_chosen($selected, $this->field_name, $this->field_id, $this->settings);
	}

	/**
     * Matrix Cell Display
     */
	public function display_cell($data)
	{	
		$selected = $this->_get_selected_entry_ids($data);
		return $this->_display_field_chosen($selected, $this->cell_name, $this->field_id, $this->settings);
	}
	
	/**
    * Get selected entry IDs
    *
	* @param $data array
    * @return array
    * @access protected
    */
	protected function _get_selected_entry_ids($data)
	{
		$selected = array();

		// autosave data?
		if ( is_array($data) )
		{
			$selected = $data;
		}
		else if ( isset($_POST[$this->field_name]) && $_POST[$this->field_name] )
		{
			$selected = $_POST[$this->field_name];
		}
		else
		{
			// existing entry?
			$entry_id = $this->EE->input->get('entry_id');

			if ( $entry_id && ( ! isset($this->cell_name) || isset($this->row_id) ) )
			{
				$where = array(
					'parent_entry_id' => $entry_id,
					'parent_field_id' => $this->field_id
				);

				// Matrix?
				if ( isset($this->cell_name) )
				{
					$where['parent_col_id'] = $this->col_id;
					$where['parent_row_id'] = $this->row_id;
				}

				$rels = $this->EE->db->select('child_entry_id')
				                     ->where($where)
				                     ->order_by('rel_order')
				                     ->get('playa_relationships');

				foreach ($rels->result() as $rel)
				{
					$selected[] = $rel->child_entry_id;
				}
			}
		}
		
		return $selected;
	}   
	
	// --------------------------------------------------------------------
	
	/**
    * Render a Chosen field
    *
    * @return string HTML
    * @access protected
    */
	protected function _display_field_chosen($data, $name, $field_id = false, $settings=array(), $multiselect = false)
	{
		// get entries according to user settings
		$field_options = $this->_populate_field($settings);
		
		// add css & js
		$this->_add_css_js();
	
		// build our extras content for dropdown
		$extras = array(
			'data-placeholder="' . @$settings['select_msg'] . '"',
			($settings['allow_multiple'] == 'y') ? 'multiple' : '',
			(0 < @$settings['min_width']) ? 'style="min-width:' . @$settings['min_width'] . 'px;"' : '',
			'class="chzn-select"',
			'id="' . $name . '"'
		);
		
		// needed for decode_multi_field functionality
		$this->EE->load->helper('custom_field');

		// decode & set up our field value & options
		$values = decode_multi_field($data);
		
		// if allowing multiple, due to the UI difference in Chosen, we need to add an empty option up top
		// In the future this could possibly be removed if the field is configured as required
		$field_options = ($settings['allow_multiple'] == 'y') ? $field_options : array('' => @$settings['select_msg']) + $field_options;
		
		return form_dropdown($name.'[]', $field_options, $values, implode(' ', $extras));
	}	
	
	/**
	* Internal function, places required css & js into document
	*
	* @return Void
	* @access protected
	*/
	protected function _add_css_js()
	{
		if (self::$init)
		{
			$theme_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_url, -1) != '/') $theme_url .= '/';
			$theme_url = $theme_url . 'third_party/chosen_playa/';

			// Are we working on SSL?
			if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
			{
				$theme_url = str_replace('http://', 'https://', $theme_url);
			}

			$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $theme_url . 'chosen.css" type="text/css" media="screen" /><style type="text/css">.publish_field .chzn-container a { text-decoration: none; }</style>');
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'chosen.jquery.min.js"></script><script type="text/javascript">jQuery(function($) {$("select.chzn-select").chosen();});</script>');

			$this->cache['head'] = TRUE;

			unset($theme_url);
			
			self::$init = false;
		}
	}

	/**
    * Populate field options according to settings
    *
	* @param array 
    * @return array
    * @access protected
    */
	protected function _populate_field($settings = array())
	{
		$settings = $this->_default_settings($settings);
		$entries = array();
		$sql_join = '';
		$sql_where = '';
		
		// filter by channel
		if ( ! empty($settings['channels']) && ! empty($settings['channels'][0]) )
		{							
			$sql_where .= "AND titles.channel_id IN ( ".implode(',', $settings['channels']).")";	
		}
		
		// filter by category
		if ( ! empty($settings['cats']) && ! empty($settings['cats'][0]) )
		{		
			$sql_join = "INNER JOIN exp_category_posts cat 
							ON titles.entry_id = cat.entry_id";
								
			$sql_where .= "AND cat.cat_id IN ( ".implode(',', $settings['cats']).")";	
		}
		
		// filter by authors
		if ( ! empty($settings['authors']) && ! empty($settings['authors'][0]) )
		{							
			$sql_where .= "AND titles.author_id IN ( ".implode(',', $settings['authors']).")";	
		}
		
		// filter by statuses
		if ( ! empty($settings['statuses']) && ! empty($settings['statuses'][0]) )
		{	
			$statuses = array_map(array($this->EE->db, 'escape_str'), $settings['statuses']);						
			$sql_where .= "AND titles.status IN ( ".$statuses.")";	
		}
		
		$sql = "SELECT titles.entry_id, titles.title
				FROM exp_channel_titles AS titles
				{$sql_join}
				WHERE site_id = {$this->site_id}
				{$sql_where}
				ORDER BY titles.title ASC";
		#echo $sql;
	
		$result = $this->EE->db->query($sql);
		
		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{	
				$entries[$row['entry_id']] = $row['title'];
			}
		}			

		return $entries;
	}
	
	
	/**
	 * Field Settings Select
	 */
	protected function _field_settings_select($name, $rows, $selected_ids, $multi = TRUE, $optgroups = TRUE, $default = "any", $attr='')
	{
		$name = $this->field_id.'_field_settings['.$name.']';
		
		$attr = ' style="width: 230px" '.$attr;
		
		$options = $this->_field_settings_select_options($rows, $selected_ids, $optgroups, $row_count, $default);
		
		if ($multi)
		{
			return '<select name="'.$name.'[]" multiple="multiple" size="'.($row_count < 10 ? $row_count : 10).'"'.$attr.'>'
		       . $options
		       . '</select>';
		}
		else
		{
			return '<select name="'.$name.'"'.$attr.'>'
		       . $options
		       . '</select>';
		}

	}
	
	/**
	 * Select Options
	 */
	protected function _field_settings_select_options($rows, $selected_ids = array(), $optgroups = TRUE, &$row_count = 0, $default = "any")
	{
		if ($optgroups) $optgroup = '';
		$options = '<option value=""'.($selected_ids || empty($data) ? '' : ' selected="selected"').'>&mdash; '.lang($default).' &mdash;</option>';
		$row_count = 1;

		foreach ($rows as $row)
		{
			if ($optgroups && isset($row['group']) && $row['group'] != $optgroup)
			{
				if ($optgroup) $options .= '</optgroup>';
				$options .= '<optgroup label="'.$row['group'].'">';
				$optgroup = $row['group'];
				$row_count++;
			}

			$selected = in_array($row['id'], $selected_ids) ? 1 : 0;
			$options .= '<option value="'.$row['id'].'"'.($selected ? ' selected="selected"' : '').'>'.$row['title'].'</option>';
			$row_count++;
		}

		if ($optgroups && $optgroup) $options .= '</optgroup>';

		return $options;
	}
}

// END chosen_playa_ft class

/* End of file ft.chosen_playa.php */
/* Location: ./system/expressionengine/third_party/Chosen_playa/ft.chosen_playa.php */