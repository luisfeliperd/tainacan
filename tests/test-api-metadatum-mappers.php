<?php

namespace Tainacan\Tests;

/**
 * @group api
 */
class TAINACAN_REST_Metadatum_Mappers_Controller extends TAINACAN_UnitApiTestCase {

    protected function create_meta_requirements() {
        $collection = $this->tainacan_entity_factory->create_entity(
            'collection',
            array(
                'name' => 'testItemMetadatumMappers',
                'description' => 'No description',
            ),
            true,
            true
        );
        
        $type = $this->tainacan_metadatum_factory->create_metadatum('text');
        
        $metadatum = $this->tainacan_entity_factory->create_entity(
            'metadatum',
            array(
                'name'              => 'test_MetadatumMappers',
                'description'       => 'descricao',
                'collection'        => $collection,
                'metadata_type'		=> $type,
                'exposer_mapping'	=> [
                    'dublin-core' => 'language'
                ]
            ),
            true,
            true
        );
        
        $metadatum2 = $this->tainacan_entity_factory->create_entity(
            'metadatum',
            array(
                'name'              => 'test_MetadatumMappers2',
                'description'       => 'descricao2',
                'collection'        => $collection,
                'metadata_type'		=> $type
            ),
            true,
            true
        );
        
        $item = $this->tainacan_entity_factory->create_entity(
            'item',
            array(
                'title'       => 'item_teste_MetadatumMappers',
                'description' => 'adasdasdsaadsf',
                'collection'  => $collection
            ),
            true,
            true
        );
        $this->collection = $collection;
        $this->item = $item;
        $this->metadatum = $metadatum;
        return ['collection' => $collection, 'item' => $item, 'metadatum' => $metadatum, 'metadatum2' => $metadatum2];
    }
    
	public function test_get_metadatum_mappers(){

		$metadatum_mapper_request = new \WP_REST_Request('GET', $this->namespace . '/metadatum-mappers');

		$metadatum_mapper_response = $this->server->dispatch($metadatum_mapper_request);

		$data = $metadatum_mapper_response->get_data();

		$Tainacan_Metadata = \Tainacan\Exposers\Exposers::get_instance();

		$metadatum_mappers = $Tainacan_Metadata->get_mappers("OBJECT");
		/** @var \Tainacan\Exposers\Mappers\Mapper $metadatum_mapper **/
		foreach ($metadatum_mappers as $k => $metadatum_mapper) {
		    if(!$metadatum_mapper->show_ui) unset($metadatum_mappers[$k]);
		}

		$this->assertEquals(count($metadatum_mappers), count($data));
		
	    for ($i = 0; $i < count($data); $i++) {
	        $this->assertEquals($metadatum_mappers[$i]->slug, $data[$i]['slug']);
	        $this->assertEquals($metadatum_mappers[$i]->name, $data[$i]['name']);
	        $this->assertEquals($metadatum_mappers[$i]->allow_extra_metadata, $data[$i]['allow_extra_metadata']);
	        $this->assertEquals($metadatum_mappers[$i]->context_url, $data[$i]['context_url']);
	        $this->assertEquals($metadatum_mappers[$i]->metadata, $data[$i]['metadata']);
	        $this->assertEquals($metadatum_mappers[$i]->prefix, $data[$i]['prefix']);
	        $this->assertEquals($metadatum_mappers[$i]->sufix, $data[$i]['sufix']);
	        $this->assertEquals($metadatum_mappers[$i]->header, $data[$i]['header']);
		}
	}
	
	public function test_update_metadatum_mappers(){
	    extract($this->create_meta_requirements());
	    
	    $dc = new \Tainacan\Exposers\Mappers\Dublin_Core();
	    
	    $metadatum_mapper_request = new \WP_REST_Request('POST', $this->namespace . '/metadatum-mappers');
	    $metadatum_mapper_json = json_encode([
	        'metadata_mappers'       => [
	            ['metadatum_id' => $metadatum->get_id(), 'mapper_metadata' => 'contributor'],
	            ['metadatum_id' => $metadatum2->get_id(), 'mapper_metadata' => 'coverage']
	        ],
	        \Tainacan\Exposers\Exposers::MAPPER_PARAM          => $dc->slug
	    ]);
	    $metadatum_mapper_request->set_body($metadatum_mapper_json);
	    $metadatum_mapper_response = $this->server->dispatch($metadatum_mapper_request);
	    $this->assertEquals(200, $metadatum_mapper_response->get_status());
	    $data = $metadatum_mapper_response->get_data();
	    
	    $this->assertEquals('contributor', $data[0]['exposer_mapping']['dublin-core']);
	    $this->assertEquals('coverage', $data[1]['exposer_mapping']['dublin-core']);
	    
	    $Tainacan_Metadata = \Tainacan\Repositories\Metadata::get_instance();
	    
	    $db_metadatum = $Tainacan_Metadata->fetch($metadatum->get_id());
	    $exposer_mapping = $db_metadatum->get('exposer_mapping');
	    $this->assertEquals('contributor', $exposer_mapping['dublin-core']);
	    
	    $db_metadatum = $Tainacan_Metadata->fetch($metadatum2->get_id());
	    $exposer_mapping = $db_metadatum->get('exposer_mapping');
	    $this->assertEquals('coverage', $exposer_mapping['dublin-core']);
	    
	}
	
	/**
	 * @group new_mapper_metadatum
	 */
	public function test_update_metadatum_mappers_new_meta(){
	    extract($this->create_meta_requirements());
	    
	    $dc = new \Tainacan\Exposers\Mappers\Dublin_Core();
	    
	    $metadatum_mapper_request = new \WP_REST_Request('POST', $this->namespace . '/metadatum-mappers');
	    $new_metadatum_mapper = new \stdClass();
	    $new_metadatum_mapper->slug = 'TesteNewMeta';
	    $new_metadatum_mapper->uri = 'TesteNewMetaUri.com';
	    $new_metadatum_mapper->type = 'text';
	    $metadatum_mapper_json = json_encode([
	        'metadata_mappers'       => [
	            ['metadatum_id' => $metadatum->get_id(), 'mapper_metadata' => 'contributor'],
	            ['metadatum_id' => $metadatum2->get_id(), 'mapper_metadata' => $new_metadatum_mapper ]
	        ],
	        \Tainacan\Exposers\Exposers::MAPPER_PARAM          => $dc->slug
	    ]);
	    $metadatum_mapper_request->set_body($metadatum_mapper_json);
	    $metadatum_mapper_response = $this->server->dispatch($metadatum_mapper_request);
	    $this->assertEquals(200, $metadatum_mapper_response->get_status());
	    $data = $metadatum_mapper_response->get_data();
	    
	    $this->assertEquals('contributor', $data[0]['exposer_mapping']['dublin-core']);
	    $this->assertEquals('TesteNewMeta', $data[1]['exposer_mapping']['dublin-core']['slug']);
	    $this->assertEquals('TesteNewMetaUri.com', $data[1]['exposer_mapping']['dublin-core']['uri']);
	    $this->assertEquals('text', $data[1]['exposer_mapping']['dublin-core']['type']);
	    
	    $item__metadata_json = json_encode([
	        'values'       => 'TestValues_exposersCustomMeta',
	    ]);
	    
	    $request  = new \WP_REST_Request('POST', $this->namespace . '/item/' . $item->get_id() . '/metadata/' . $metadatum2->get_id() );
	    $request->set_body($item__metadata_json);
	    
	    $response = $this->server->dispatch($request);
	    
	    $this->assertEquals(200, $response->get_status());
	    
	    $item_exposer_json = json_encode([
	        \Tainacan\Exposers\Exposers::TYPE_PARAM       => 'OAI-PMH',
	    ]);
	    $request = new \WP_REST_Request('GET', $this->namespace . '/item/' . $item->get_id() . '/metadata' );
	    $request->set_body($item_exposer_json);
	    $response = $this->server->dispatch($request);
	    $this->assertEquals(200, $response->get_status());
	    $data = $response->get_data();
	    
	    $xml = new \SimpleXMLElement($data);
	    $dc = $xml->children(\Tainacan\Exposers\Mappers\Dublin_Core::XML_DC_NAMESPACE);
	    $this->assertEquals('adasdasdsaadsf', $dc->description);
	    $this->assertEquals('item_teste_MetadatumMappers', $dc->title);
	    $this->assertEquals('', $dc->contributor);
	    $this->assertEquals('TestValues_exposersCustomMeta', $dc->TesteNewMeta);
	    
	}
	
}

?>