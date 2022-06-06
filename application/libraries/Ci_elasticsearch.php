<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Elastic\Elasticsearch\ClientBuilder;

class Ci_elasticsearch {
    
    private $client;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->client = ClientBuilder::create()->setHosts([HOST_ELASTICSEARCH])->build();
    }
    
    public function get_info() {
        $response = $this->client->info();
        return $response;
    }
    
    public function exists_index($index) {
        $status = 404;
        if($index) {
            $params = ['index' => $index];
            $response = $this->client->indices()->exists($params);
            $status = $response->getStatusCode();
        }
        return $status;
    }
    
    public function create_index($index, $body) {
        $params = ['index' => $index, 'body' => $body];
        $response = $this->client->indices()->create($params);
    }
    
    public function refresh_index($index) {
        $params = ['index' => $index];
        $response = $this->client->indices()->refresh($params);
    }
    
    public function delete_index($index) {
        if(!$index) {return FALSE;}
        $params = ['index' => $index];
        try {
            $response = $this->client->indices()->delete($params);
        }catch(\Throwable $e) {}
        return TRUE;
    }
    
    public function get_found_document($index, $type, $id) {
        //db - collection - id
        $params = ['index' => $index, 'type' => $type, 'id'  => $id];
        $results = $this->client->get($params);
        return $results['found'];
    }
    
    public function exists_document($index, $type, $id) {
        $params = ['index' => $index, 'type' => $type, 'id' => $id];
        $response = $this->client->exists($params);
        return $response;
    }
    
    public function create_document($index, $type, $id, $body) {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $body];
        $response = $this->client->index($params);
        return $response;
    }
    
    public function update_document($index, $type, $id, $body) {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $body];
        $response = $this->client->update($params);
        return $response;
    }
    
    public function delete_document($index, $type, $id) {
        $params = [
            'index' => $index,
            'type' => $type,
            'refresh' => 'wait_for', //or true
            'id' => $id];
        $response = $this->client->delete($params);
        return $response;
    }
    
    public function search_document($index, $type, $from, $size, $query, $all) {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                'from' => $from,
                'size' => $size,
                'query' => $query]];
        if($all) {
            $params = [
                'index' => $index,
                'type' => $type,
                'from' => $from,
                'size' => $size,
                'body' => ['query' => $query]];
        }
        $response = $this->client->search($params);
        return $response;
    }
    
    public function count_documents($index, $type, $query) {
        $exists_index = $this->exists_index($index);
        if($exists_index === 200) {
            $params = [
                'index' => $index,
                'type' => $type,
                'body' => ['query' => $query]];
            $response = $this->client->count($params);
        }else {
            $response['count'] = 0;
        }
        return $response;
    }
}

