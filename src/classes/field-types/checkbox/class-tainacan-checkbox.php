<?php

namespace Tainacan\Field_Types;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Class TainacanFieldType
 */
class Checkbox extends Field_Type {

    function __construct(){
        // call field type constructor
        parent::__construct();
        parent::set_primitive_type('date');
    }

    /**
     * @param $metadata
     * @return string
     */

    public function render( $metadata ){
        return '<tainacan-checkbox name="'.$metadata->get_name().'"></tainacan-checkbox>';
    }

    /**
     * generate the fields for this field type
     */
    public function form(){
        ?>
        <tr>
            <td>
                <label><?php echo __('Options','tainacan'); ?></label><br/>
                <small><?php echo __('Insert the options, separate by lines for the metadata value','tainacan'); ?></small>
            </td>
            <td>
                <textarea name="tnc_metadata_options"><?php echo ( $this->options ) ? $this->options : ''; ?></textarea>
            </td>
        </tr>
        <?php
    }
}