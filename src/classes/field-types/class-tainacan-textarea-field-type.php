<?php

namespace Tainacan\Field_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TainacanFieldType
 */
class Field_Type extends Field_Type {

    function __construct(){
        $this->primitive_type = 'string';
    }

    /**
     * @param $metadata
     * @return string
     */

    function render( $metadata ){
        return '<tainacan-textarea name="'.$metadata->get_name().'"></tainacan-textarea>';
    }
}