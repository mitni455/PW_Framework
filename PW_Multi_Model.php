<?php
/**
 * PW_Multi_Model
 *
 * A PW_Multi_Model stores an option that is an array of model objects
 *
 * The basic structure of the option is as follows:
 *  array(
 *  	0 => array(
 *  		'prop1' => 'prop1 value',
 *  		'prop2' => 'prop2 value',
 *  		'prop3' => 'prop3 value',
 *  	),
 *  	1 => array(
 *  		'prop1' => 'prop1 a different value',
 *  		'prop2' => 'prop2 a different value',
 *  		'prop3' => 'prop3 a different value',
 *  	),
 *  	'auto_id' => 2
 *  )
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Multi_Model extends PW_Model
{	
	/**
	 * @var int The model instance (the option array key) currently being used
	 * @since 1.0
	 */
	protected $_instance;
	
	
	/**
	 * Check to see if this model should be deleted, then run parent::__construct()
	 * @since 1.0
	 */
	public function __construct()
	{		
		$this->get_option();
		
		// Set $this->_instance, the default is 0 which is the new instance form
		$this->_instance = isset($_GET['_instance']) ? (int) $_GET['_instance'] : 0;
		
		// if $this->_instance 
		if ( empty($this->_option[$this->_instance]) ) {
			wp_die( "Oops, this page doesn't exist.", "Page does not exist", array('response' => 403) );
		}
				
		if ( 
			isset($_GET['delete_instance'])
			&& isset($_GET['_instance'])
			&& check_admin_referer('delete_instance')
		) {
			unset( $this->_option[ (int) $_GET['_instance'] ] );
			update_option( $this->_name, $this->_option );
			
			// redirect the page and remove _instance' and 'delete_instance' from the URL
			wp_redirect( remove_query_arg( array( '_instance', 'delete_instance'), wp_get_referer() ) );
			exit();
		}
		
		
		
		parent::__construct();
	}
	
	
	/**
	 * Save the option to the database if (and only if) the option passes validation
	 * @param array $option The option value to store
	 * @return boolean Whether or not the option was successfully saved
	 * @since 1.0
	 */
	public function save( $input )
	{	
		
		if ( $this->validate($input) ) {
			$this->_errors = array();
			
			// Make sure an instance ID was passed in the POST, 0 means a new instance
			if ( isset($_POST['_instance']) ) {
				
				// set the instance ID and increment the auto_id
				$instance = $_POST['_instance'] == 0 ? $this->_option['auto_id']++ : $_POST['_instance'];
				
				$this->_option[$instance] = $input;
				$this->_updated = true;
				if ( update_option( $this->_name, $this->_option ) ) {
					return true;
				}
			}
		}
		// If you get to here, return false
		return false;
	}
	
	
	/**
	 * Returns an array specifying the default option property values as index 0, and an auto_id of 1
	 * @return array The default property values
	 * @since 1.0
	 */
	protected function defaults()
	{
		$defaults = array();
		$data = $this->data();
		foreach($data as $property=>$value) {
			$defaults[$property] = isset($value['default']) ? $value['default'] : '';
		}
		return array( 0 => $defaults, 'auto_id' => 1);
	}
	
	
	/**
	 * Merges a single model at a certain index within the multi model with the defaults from self::defaults()
	 * Override in a child class for custom merging.
	 * @return array The merged option
	 * @since 1.0
	 */
	protected function merge_with_defaults( $option )
	{
		$defaults = $this->defaults();	
		
		foreach( $option as $key=>$instance )
		{			
			if ( $key !== 'auto_id')  {
				$option[$key] = wp_parse_args( $instance, $defaults[0] );
			}
		}
	
		return $option;
	}
	
	
}