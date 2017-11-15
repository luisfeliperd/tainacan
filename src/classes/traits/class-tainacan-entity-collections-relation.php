<?php

namespace Tainacan\Traits;

// used by Taxonomy

trait Entity_Collections_Relation {

    function get_collections_ids() {
        return $this->get_mapped_property('collections_ids');
    }
    

    function get_collections() {
        if (isset($this->collection) && !empty($this->collection) && is_array($this->collection)){
            return $this->collection;
        }
        
        if (is_array($this->get_collections_ids()) && !empty(is_array($this->get_collections_ids()))) {
            
            global $Tainacan_Collections;
            $collections = [];
            
            foreach ($this->get_collections_ids() as $col_id) {
                $collections[] = $Tainacan_Collections->get_collection_by_id($col_id);
            }
            
            return $collections;
        }
        
        return null;
        
    }
    
    function set_collections_ids(Array $value) {
        return $this->set_mapped_property('collection_id', $value);
        $this->collections = null;
    }
    
    function set_collections(Array $collections) {
        $collections_ids = [];
        $this->collections = $collections;
        
        foreach ($collections as $collection){
            $collections_ids[] = $collection->get_id();
        }
        
        $this->set_collections_ids($collections_ids);
    }

}