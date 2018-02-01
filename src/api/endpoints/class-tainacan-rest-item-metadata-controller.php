<?php

use Tainacan\Entities;
use Tainacan\Repositories;

class TAINACAN_REST_Item_Metadata_Controller extends TAINACAN_REST_Controller {
	private $field;
	private $item_metadata_repository;
	private $item_repository;
	private $collection_repository;
	private $field_repository;

	public function __construct() {
		$this->namespace = 'tainacan/v2';
		$this->rest_base = 'metadata';

		add_action('rest_api_init', array($this, 'register_routes'));
		add_action('init', array(&$this, 'init_objects'), 11);
	}

	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
		$this->field = new Entities\Field();
		$this->field_repository = new Repositories\Fields();
		$this->item_metadata_repository = new Repositories\Item_Metadata();
		$this->item_repository = new Repositories\Items();
		$this->collection_repository = new Repositories\Collections();
	}

	/**
	 * If POST on field/collection/<collection_id>, then
	 * a field will be created in matched collection and all your item will receive this field
	 *
	 * If POST on field/item/<item_id>, then a value will be added in a field and field passed
	 * id body of requisition
	 *
	 * Both of GETs return the field of matched objects
	 */
	public function register_routes() {
		register_rest_route($this->namespace, '/item/(?P<item_id>[\d]+)/' . $this->rest_base . '/(?P<metadata_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check')
				)
			)
		);
		register_rest_route($this->namespace,  '/item/(?P<item_id>[\d]+)/'. $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_items'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_collection_params(),
				)
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return object|void|WP_Error
	 */
	public function prepare_item_for_database( $request ) {
		$meta = json_decode($request[0]->get_body(), true);

		foreach ($meta as $key => $value){
			$set_ = 'set_' . $key;
			$this->field->$set_($value);
		}

		$collection = new Entities\Collection($request[1]);

		$this->field->set_collection($collection);
	}

	/**
	 * @param mixed $item
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$field_as = [];

		foreach ( $item as $field ) {
			$field_as[] = $field->__toArray();
		}

		return $field_as;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$item_id = $request['item_id'];

		$item = new Entities\Item($item_id);

		$item_metadata = $this->item_metadata_repository->fetch($item, 'OBJECT');

		$prepared_item = $this->prepare_item_for_response($item_metadata, $request);

		return new WP_REST_Response($prepared_item, 200);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 * @throws Exception
	 */
	public function get_items_permissions_check( $request ) {
		if(isset($request['item_id'])){
			$item = $this->item_repository->fetch($request['item_id']);

			if($item instanceof Entities\Item) {
				return $item->can_read();
			}

		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {
		return parent::get_collection_params(); // TODO: Change the autogenerated stub
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		if(!empty($request->get_body())){
			$body = json_decode($request->get_body());

			$collection_id = $request['collection_id'];
			$field_id = $body['metadata_id'];

			return new WP_REST_Response(['error' => 'Not Implemented.'], 400);
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 * @throws Exception
	 */
	public function delete_item_permissions_check( $request ) {
		if(isset($request['item_id'])){
			$item = $this->item_repository->fetch($request['item_id']);

			if($item instanceof Entities\Item) {
				return $item->can_delete();
			}

		}

		return false;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$body = json_decode( $request->get_body(), true );

		if($body) {

			$item_id     = $request['item_id'];
			$field_id = $request['metadata_id'];
			$value       = $body['values'];

			$item     = $this->item_repository->fetch( $item_id );
			$field = $this->field_repository->fetch( $field_id );

			$item_metadata = new Entities\Item_Metadata_Entity( $item, $field );
			$item_metadata->set_value( $value );

			if ( $item_metadata->validate() ) {
				$field_updated = $this->item_metadata_repository->update( $item_metadata );

				return new WP_REST_Response( $field_updated->__toArray(), 200 );
			} else {
				return new WP_REST_Response( [
					'error_message' => __( 'One or more values are invalid.', 'tainacan' ),
					'errors'        => $item_metadata->get_errors(),
					'item_metadata' => $item_metadata->__toArray(),
				], 400 );
			}
		}

		return new WP_REST_Response([
			'error_message' => 'The body could not be empty',
			'body'          => $body
		], 400);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 * @throws Exception
	 */
	public function update_item_permissions_check( $request ) {
		if (isset($request['item_id'])) {
			$item = $this->item_repository->fetch($request['item_id']);

			if ($item instanceof Entities\Item) {
				return $item->can_edit();
			}

		}

		return false;
	}
}

?>