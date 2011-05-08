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

class PW_Form extends PW_Object
{
	/**
	 * @var string The template to create the HTML label/field pairs
	 * Don't worry about creating extra markup if {extra} or {error} is empty.
	 * All empty tags are removed before output.
	 * @since 1.0
	 */
	protected $_template = '
		<div class="label">{label}</div>
		<div class="field {error_class}">
			{field}
			<span class="description">{desc}</span>
			<div class="extra">{extra}</div>
			<div class="{error_message_class}">{error}</div>
		</div>
	';

	/**
	 * @var string The class applied to the field element if the model contains errors 
	 * @since 1.0
	 */
	protected $_error_class = 'pw-error';
	
	/**
	 * @var string The class applied to the actual error message element
	 * @since 1.0
	 */
	protected $_error_message_class = 'pw-error-message';
	
	/**
	 * @var string The markup that opens a section
	 * @since 1.0
	 */
	protected $_begin_section_template = '<h3>{section_title}</h3>';

	/**
	 * @var string The markup that closes a section
	 * @since 1.0
	 */
	protected $_end_section_template = '';

	/**
	 * @var string The model object associated with this form
	 * @since 1.0
	 */
	protected $_model;
	
	/**
	 * Whether or not methods should output or return the generated HTML
	 * @since 1.0
	 */
	protected $_echo = true;
	
	
	public function __construct( $model = null )
	{
		$this->_model = $model;
	}


	public function begin_form( $atts = array() )
	{
		$this->render_title();
				
		PW_Alerts::render();
		
		$output = '';
				
		// Add only the opening form tag
		$atts = wp_parse_args( $atts, array('class'=>'pw-form', 'method'=>'post' ) );
		$output .= str_replace('</form>', '', PW_HTML::tag('form', '', $atts) );

		// Add the hidden fields for _nonce and _wp_http_referrer
		ob_start();
		ob_implicit_flush(false);
		wp_nonce_field( $this->_model->name . '-options' );
		$output .= ob_get_clean();

		$this->return_or_echo($output);
	}
	
	public function end_form()
	{
		$this->return_or_echo( '<p class="submit"><input class="button-primary" type="submit" value="Save" /></p></form>' );
	}
	
	/**
	 * Create a new section with title and optional description
	 * @param string $title The section title
	 * @param string $desc Optional description text to go below the title
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function begin_section( $title = '', $desc = '' )
	{
		$output = str_replace(
			array('{section_title}','{section_description}'),
			array($title, $desc),
			$this->_begin_section_template
		);
		
		$this->return_or_echo( $output );
	}
	
	/**
	 * Close out a section
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function end_section( )
	{
		$this->return_or_echo( $this->_end_section_template );
	}
	
	
	/**
	 * Creates the markup for the page title and screen icon
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function render_title( )
	{
		$output = '';
		
		// Get the screen icon (this is necessary becuase WordPress only echos it)
		ob_start();
		ob_implicit_flush(false);
		screen_icon();
		$output .= ob_get_clean();

		// Add a title to the form page
		$output .= '<h2>' . $this->_model->title . '</h2>';
		
		$this->return_or_echo( $output );
	}
	
	/**
	 * Creates the markup for any update, info, or error messages that need to be displayed
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function render_alerts( )
	{
		$output = '';
		
		// If options were just updated, show a message
		if ( $this->_model->updated ) {
			$output .= '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
		}
		
		// If there were errors, show an alert
		if ( $errors = $this->_model->get_errors() ) {
			$output .= '<div class="error"><p><strong>' . $this->_error_message . '</strong></p>' . ZC::r('ul>li*' . count($errors), array_values($errors) ) . '</div>';
		}
		
		$this->return_or_echo( $output );
	}
	
	public function render_field($label, $field, $desc, $extra, $error)
	{	
		$output = str_replace(
			array('{label}','{field}','{desc}','{extra}','{error}','{error_class}','{error_message_class}'),
			array($label, $field, $desc, $extra, $error, $error ? $this->_error_class : '', $error ? $this->_error_message_class : '' ),
			$this->_template
		);
		
		// then remove any empty tags
		$output = preg_replace('/<[^>\/]+><\/[^>]+>/', '', $output);

		$this->return_or_echo( $output );
	}
	
	/**
	 * @param string $property The model option property
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $unchecked_value An optional default value in case the box is left unchecked
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function checkbox( $property, $atts=array(), $unchecked_value, $extra = '' )
	{		
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		
		// Make sure value is set (default to "1")
		$atts['value'] = isset($atts['value']) ? $atts['value'] : "1";
		
		// Determine if the checkbox should be selected
		$selected = $value == $atts['value'];

		$field = PW_HTML::checkbox( $name, $selected, $atts, $unchecked_value) . PW_HTML::label($desc, $id);
		
		// set the $desc value to '' because the checkbox is already using it for generate the label		
		$this->return_or_echo( $this->render_field($label, $field, '', $extra, $error) );	
	}


	/**
	 * @param string $property The model option property
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param string $separator Markup to put between each radio button
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function checkbox_list( $property, $separator, $atts=array(), $extra = '' )
	{
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		$field = PW_HTML::checkbox_list( $name, $options, $value, $separator, $atts);
		$this->return_or_echo( $this->render_field($label, $field, $desc, $extra, $error) );
	}
	
	
	/**
	 * @param string $property The model option property
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param string $separator Markup to put between each radio button
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function radio_button_list( $property, $separator, $atts=array(), $extra = '' )
	{
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		$field = PW_HTML::radio_button_list( $name, $options, $value, $separator, $atts);
		$this->return_or_echo( $this->render_field($label, $field, $desc, $extra, $error) );
	}
	
		
	/**
	 * @param string $property The model option property
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function select( $property, $atts=array(), $extra = '' )
	{
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		
		$label = PW_HTML::label($label, $id);
		$field = PW_HTML::select( $name, $options, $value, $atts);
		
		$this->return_or_echo( $this->render_field($label, $field, $desc, $extra, $error) );	
	}
	
	
	/**
	 * @param string $property The model option property
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function textfield( $property, $atts=array(), $extra = '' )
	{
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $desc, $name, $value, $id
		
		$label = PW_HTML::label($label, $id);
		$field = PW_HTML::textfield( $name, $value, $atts);
		
		$this->return_or_echo( $this->render_field($label, $field, $desc, $extra, $error) );
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
		$value = isset($this->_model->input[$property]) ? $this->_model->input[$property] : $this->_model->$property;

		
		// add the model's option name for easy getting from the $_POST variable after submit
		$name = $this->_model->name . '[' . $property . ']';
		
		// get any options defined (for use in select, checkbox_list, and radio_button_list fields)
		$options = isset($data[$property]['options']) ? $data[$property]['options'] : array();
		
		// create the id from the name
		$id = PW_HTML::get_id_from_name( $name );
		
		return array( 'error'=>$error, 'label'=>$label, 'desc'=>$desc, 'value'=>$value, 'name'=>$name, 'id'=>$id, 'options'=>$options );
	}
	
	
	/**
	 * Either echos $output or returns it as a string based on the value of $this->echo
	 * @param string $output The HTML to return or echo
	 * @return string Only if $this->echo == true
	 * @since 1.0
	 */
	protected function return_or_echo( $output )
	{
		if ($this->echo) {
			echo $output;
		} else {
			return $output;
		}
	}
}