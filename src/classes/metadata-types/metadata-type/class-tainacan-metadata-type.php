<?php

namespace Tainacan\Metadata_Types;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Class TainacanMetadatumType
 */
abstract class Metadata_Type  {

    
    /**
     * Indicates the type of variable that this metadatum type handles.
     *
     * This is used to relate Metadatum types and filter types, so we know which filter types
     * will be available to be used for each metadatum based on its Metadatum Type
     *
     * For instance, the Filter Type "input text" may be used to search in any metadatum that has
     * a Metadatum Type with a string primitive type.
     * 
     * @var string
     */
    private $primitive_type;
    
    /**
     * Array of options specific to this metadatum type. Stored in metadata_type_options property of the Metadatum object
     * @var array
     */
    private $options = [];
    
    /**
     * The default values for the metadatum type options array
     * @var array
     */
    private $default_options = [];
    
    private $errors;
    
    /**
     * Indicates whether this is a core Metadatum Type or not
     *
     * Core metadatum types are used by Title and description metadata. These metadata:
     * * Can only be used once, they belong to the repository and can not be deleted
     * * Its values are saved in th wp_post table, and not as post_meta 
     * 
     */
    private $core = false;
    
    /**
     * Used by core metadatum types to indicate where it should be saved
     */
    private $related_mapped_prop = false;
    
    /**
     * The name of the web component used by this metadatum type
     * @var string
     */
    private $component;

    /**
     * The name of the web component used by the Form
     * @var bool | string
     */
    private $form_component = false;
    
    abstract function render( $itemMetadata );

    public function __construct(){
        
    }

    public function validate(\Tainacan\Entities\Item_Metadata_Entity $item_metadata) {
        return true;
    }

    public function get_related_mapped_prop(){
    	return $this->related_mapped_prop;
    }

    public function set_related_mapped_prop($related_mapped_prop){
    	$this->related_mapped_prop = $related_mapped_prop;
    }
    
    public function get_validation_errors() {
        return [];
    }

    public function get_primitive_type(){
        return $this->primitive_type;
    }

    public function set_primitive_type($primitive_type){
        $this->primitive_type = $primitive_type;
    }

    public function get_errors() {
        return $this->errors;
    }
    
    public function get_component() {
        return $this->component;
    }

    public function set_component($component){
    	$this->component = $component;
    }

    public function get_form_component() {
        return $this->form_component;
    }

    public function set_form_component($form_component){
    	$this->form_component = $form_component;
    }

    /**
     * @param $options
     */
    public function set_options( $options ){
	    $this->options = ( is_array( $options ) ) ? $options : (!is_array(unserialize( $options )) ? [] : unserialize( $options ));
    }
    
    public function set_default_options(Array $options) {
        $this->default_options = $options;
    }
    
    /**
     * Gets the options for this metadatum types, including default values for options
     * that were not set yet.
     * @return array Metadatum type options
     */
    public function get_options() {
        return array_merge($this->default_options, $this->options);
    }
    
    /**
     * Gets one option from the options array.
     *
     * Checks if option exist or if it have a default value. Otherwise return an empty string
     * 
     * @param  string $key the desired option
     * @return mixed the option value, the default value or an empty string
     */
    public function get_option($key) {
        $options = $this->get_options();
        return isset($options[$key]) ? $options[$key] : '';
    }

    /**
     * allow i18n from messages
     */
    public function get_form_labels(){
       return [];
    }
    /**
     * generate the metadata for this metadatum type
     */
    public function form(){

    }
    
    public function _toArray(){
	    $attributes = [];

	    $attributes['errors']              = $this->get_errors();
	    $attributes['related_mapped_prop'] = $this->get_related_mapped_prop();
	    $attributes['options']             = $this->get_options();
        $attributes['className']           = get_class($this);
        $attributes['core']                = $this->get_core();
        $attributes['component']           = $this->get_component();
        $attributes['primitive_type']      = $this->get_primitive_type();
        $attributes['form_component']      = $this->get_form_component();

        return $attributes;
        
    }
    
    /**
     * Validates the options Array
     *
     * This method should be declared by each metadatum type sub classes
     * 
     * @param  \Tainacan\Entities\Metadatum  $metadatum The metadatum object that is beeing validated
     * @return true|Array True if optinos are valid. If invalid, returns an array where keys are the metadatum keys and values are error messages.
     */
    public function validate_options(\Tainacan\Entities\Metadatum $metadatum) {
        return true;
    }

	/**
	 * @return mixed
	 */
	public function get_core() {
		return $this->core;
	}

	public function set_core($core){
		$this->core = $core;
	}
}