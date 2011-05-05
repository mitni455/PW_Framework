<?php
/**
 * PW_Form
 *
 * A helper class to build a form based on a PW_Model object
 *
 * This class primarily does these things:
 * 1) Renders the markup of the form fields
 * 2) Adds error messages for any validation errors that are found
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Multi_Model_Form extends PW_Form
{
	/**
	 * The multi model array key that identifies the current model instance to display
	 * @since 1.0
	 */
	protected $_instance = 0;
	
	
	public function __construct( $model = null )
	{
		$this->_model = $model;
		
		if ( isset($_GET['_pw_mm_id']) ) {
			$this->set_instance( $_GET['_pw_mm_id'] );
		} else {
			$this->set_instance(0);
		}
	}

	public function set_model( $model ) {
		$this->_model = $model;
	}
	
	/**
	 * Set the instance ID
	 * @param int The instance of the model array key to laod
	 * @since 1.0
	 */
	public function set_instance( $instance ) {
		$this->_instance = $instance;
	}
	
	public function begin_form( $atts = array() )
	{
		$output = parent::begin_form($atts);
		
		$this->return_or_echo( $output );
	}
	
	public function end_form()
	{
		$this->return_or_echo( '<p class="submit"><input class="button-primary" type="submit" value="Save" /></p></form>' );
	}
	

	
	
	/**
	 * @param string $property The model option property
	 * @return array An array of the property's id, name (the HTML attribute), label, desc, value, and error (if one exists)
	 * @since 1.0
	 */
	protected function get_field_data_from_model( $property )
	{		
		$errors = $this->_model->get_errors();
		$error = isset($errors[$property]) ? $errors[$property] : null;
	
		$data = $this->_model->data();
	
		// get the label and description of this property
		$label = isset($data[$property]['label']) ? $data[$property]['label'] : '';
		$desc = isset($data[$property]['desc']) ? $data[$property]['desc'] : '';
			
		// get the value of the model attribute by this name
		// if there was a validation error, get the previously submitted value
		// rather than what's stored in the database
		if ( isset($this->_model->input[$property]) ) {
			$value =  $this->_model->input[$property];
		} else {
			$value = $this->_model->get_option();
			$value = $value[$this->_instance][$property];
		}

		
		// add the model's option name for easy getting from the $_POST variable after submit
		$name = $this->_model->get_name() . '[' . $property . ']';
		
		// get any options defined (for use in select, checkbox_list, and radio_button_list fields)
		$options = isset($data[$property]['options']) ? $data[$property]['options'] : array();
		
		// create the id from the name
		$id = PW_HTML::get_id_from_name( $name );
		
		return array( 'error'=>$error, 'label'=>$label, 'desc'=>$desc, 'value'=>$value, 'name'=>$name, 'id'=>$id, 'options'=>$options );
	}
	
}