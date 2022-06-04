<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Es_articles extends CI_Controller {
	
    public function __construct() {
        parent::__construct();
        
        if(!$this->input->is_cli_request()) {
            die('Request is not permited.');
        }
        $this->load->model('Article_model', 'obj_article', TRUE);
        $this->load->library('Ci_elasticsearch');
    }
    
    public function create_index_es_articles() {
        echo '=========================================' . PHP_EOL;
        echo 'CRON: ' . __METHOD__ . PHP_EOL;
        echo '=========================================' . PHP_EOL;
        echo '=== INICIO CREAR INDICE es_articles ===' . PHP_EOL;
        $is_delete = $this->ci_elasticsearch->delete_index('es_articles');
        if($is_delete) {
            $body = [
                'mappings' => [
                    'properties' => [
                        'title' => [
                            'type' =>'text'
                        ],
                        'description' => [
                            'type' =>'text'
                        ]
                    ]
                ]
            ];
            $this->ci_elasticsearch->create_index('es_articles', $body);
        }
        echo '=== FIN CREAR INDICE es_articles ===' . PHP_EOL;
    }
    
    public function add_documents_index_es_articles() {
        echo '=========================================' . PHP_EOL;
        echo 'CRON: ' . __METHOD__ . PHP_EOL;
        echo '=========================================' . PHP_EOL;
        echo '=== INICIO AGREGAR DOCUMENTOS A INDICE es_articles ===' . PHP_EOL;
        $articles = $this->obj_article->get_articles();
        foreach ($articles as $article) {
            echo '=== INICIO CREAR DOCUMENTO CON ID ' . $article->id . '===' . PHP_EOL;
            $body = ['title' => $article->title, 'description' => $article->description];
            $response = $this->ci_elasticsearch->create_document('es_articles', 'es_articles', $article->id, $body);
            echo '=== FIN CREAR DOCUMENTO CON ID ' . $article->id . '===' . PHP_EOL;
        }
        $this->ci_elasticsearch->refresh_index('es_articles');
        echo '=== FIN AGREGAR DOCUMENTOS A INDICE es_articles ===' . PHP_EOL;
    }
}
