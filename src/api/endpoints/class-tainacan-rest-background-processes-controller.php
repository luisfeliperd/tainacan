<?php

namespace Tainacan\API\EndPoints;

use \Tainacan\API\REST_Controller;
use Tainacan\Repositories;
use Tainacan\Entities;

/**
 * Represents the Background Processes REST Controller
 *
 * */
class REST_Background_Processes_Controller extends REST_Controller {
    private $collections_repository;
    private $collection;

	/**
	 * REST_Background_Processes_Controller constructor.
	 * Define the namespace, rest base and instantiate your attributes.
	 */
	public function __construct(){
        global $wpdb;
        $this->rest_base = 'bg-processes';
        $this->table = $wpdb->prefix . 'tnc_bg_process';
		parent::__construct();
    }


	/**
	 * Register the BG Processes route and their endpoints
	 */
	public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base , array(
	        array(
		        'methods'             => \WP_REST_Server::READABLE,
		        'callback'            => array($this, 'get_items'),
		        'permission_callback' => array($this, 'bg_processes_permissions_check'),
		        'args'                => [
                    'user_id' => [
                        'type'        => 'integer',
                        'description' => __( 'The ID of the owner of the background processes. Defaults to current user', 'tainacan' ),
                    ],
                    'all_users' => [
                        'type'        => 'bool',
                        'description' => __( 'Whether to return processes from all users (if current user is admin). Default false.', 'tainacan' ),
                    ],
                    'status' => [
                        'type'        => 'string',
                        'description' => __( '"open" returns only processes currently running. "closed" returns only finished or aborted. "all" returns all. Default "all"', 'tainacan' ),
                    ],
                    'perpage' => [
                        'type'        => 'integer',
                        'description' => __( 'Number of processes to return per page. Default 10', 'tainacan' ),
                    ],
                    'paged' => [
                        'type'        => 'integer',
                        'description' => __( 'Page to retrieve. Default 1', 'tainacan' ),
                    ],
                ],
	        ),
        ));
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[0-9]+)', array(
            
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'bg_processes_permissions_check'),
            ),
            
        ));
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[0-9]+)', array(
            
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'bg_processes_permissions_check'),
                'args'                => [
                    'status' => [
                        'type'        => 'string',
                        'description' => __( '"open" or "closed" ', 'tainacan' ),
                    ]
                ],
            ),
            
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[0-9]+)', array(
            
            array(
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'bg_processes_permissions_check'),
                
            ),
            
        ));


    }

	
	/**
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function  bg_processes_permissions_check($request){
        // TODO
        return current_user_can('read');
	}


    public function get_items( $request ) {
        global $wpdb;
        //$body = json_decode($request->get_body(), true);

        $perpage = isset($request['perpage']) && is_numeric($request['perpage']) ? $request['perpage'] : 10;
        if ($perpage < 1) {
            $perpage = 1;
        }
        $paged = isset($request['paged']) && is_numeric($request['paged']) ? $request['paged'] : 1;
        if ($paged < 1) {
            $paged = 1;
        }

        $offset = ($paged - 1) * $perpage;

        $limit_q = "LIMIT $offset,$perpage";

        $user_q = $wpdb->prepare("AND user_id = %d", get_current_user_id());
        $status_q = "";

        if (current_user_can('edit_users')) {
            if (isset($request['user_id'])) {
                $user_q = $wpdb->prepare("AND user_id = %d", $request['user_id']);
            }

            if ( isset($user_q['all_users']) && $user_q['all_users'] ) {
                $user_q = "";
            }
        }

        if ( isset($request['status']) && $request['status'] != 'all' ) {
            if ( $request['status'] == 'open' ) {
                $status_q = "AND done = 0";
            }
            if ( $request['status'] == 'closed' ) {
                $status_q = "AND done = 1";
            }
        }

        $base_query = "FROM $this->table WHERE 1=1 $status_q $user_q ORDER BY priority DESC, queued_on DESC";

        $query = "SELECT * $base_query $limit_q";
        $count_query = "SELECT COUNT(ID) $base_query";

        $result = $wpdb->get_results($query);
        $total_items = $wpdb->get_var($count_query);

        $rest_response = new \WP_REST_Response( $result, 200 );

        $max_pages = ceil($total_items / (int) $perpage);

		$rest_response->header('X-WP-Total', (int) $total_items);
        $rest_response->header('X-WP-TotalPages', (int) $max_pages);
        
        return $rest_response;

    }

    public function get_item( $request ) {
        global $wpdb;
        $id = $request['id'];

        $user_q = $wpdb->prepare("AND user_id = %d", get_current_user_id());
        $id_q = $wpdb->prepare("AND ID = %d", $id);

        // do not allow users without permission to see others people process
        if (current_user_can('edit_users')) {
            if ( isset($user_q['all_users']) && $user_q['all_users'] ) {
                $user_q = "";
            }
        }

        $query = "SELECT * FROM $this->table WHERE 1=1 $id_q $user_q LIMIT 1";

        $result = $wpdb->get_row($query);

        $result->log = $this->get_log_url($id, $result->action);
        $result->error_log = $this->get_log_url($id, $result->action, 'error');

        return new \WP_REST_Response( $result, 200 );

    }

    public function update_item( $request ) {
        global $wpdb;
        $id = $request['id'];
        $body = json_decode($request->get_body(), true);

        if ( !isset($body['status']) || ($body['status'] != 'open' && $body['status'] != 'closed') ) {
            return new \WP_REST_Response([
                'error_message' => __('Status must be informed', 'tainacan' ),
                'session_id' => $session_id
            ], 400);
        }

        if ( $body['status'] == 'open' ) {
            $status_q = "done = 0";
        }
        if ( $body['status'] == 'closed' ) {
            $status_q = "done = 1";
        }

        $id_q = $wpdb->prepare("AND ID = %d", $id);
        $user_q = $wpdb->prepare("AND user_id = %d", get_current_user_id());

        // do not allow users without permission to see others people process
        if (current_user_can('edit_users')) {
            if ( isset($user_q['all_users']) && $user_q['all_users'] ) {
                $user_q = "";
            }
        }

        $query = "UPDATE $this->table $status_q WHERE 1=1 $id_q $user_q";

        $result = $wpdb->query($query);

        return new \WP_REST_Response( $result, 200 );

    }

    public function delete_item( $request ) {
        global $wpdb;
        $id = $request['id'];

        $user_q = $wpdb->prepare("AND user_id = %d", get_current_user_id());
        $id_q = $wpdb->prepare("AND ID = %d", $id);

        // do not allow users without permission to see others people process
        if (current_user_can('edit_users')) {
            if ( isset($user_q['all_users']) && $user_q['all_users'] ) {
                $user_q = "";
            }
        }

        $query = "DELETE FROM $this->table WHERE 1=1 $id_q $user_q LIMIT 1";

        $result = $wpdb->query($query);

        // TODO: delete log files

        return new \WP_REST_Response( $result, 200 );

    }

    public function get_log_url($id, $action, $type = '') {
        $suffix = $type ? '-' . $type : '';
		
        $filename = 'bg-' . $action . '-' . $id . $suffix . '.log';
        
        $upload_url = wp_upload_dir();
		$upload_url = trailingslashit( $upload_url['baseurl'] );
        $logs_url = $upload_url . 'tainacan/' . $filename;
        
        return $logs_url;

    }




}

?>
