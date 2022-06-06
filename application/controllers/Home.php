<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        //con database en libraries: $autoload['libraries'] = array('database');
        //$this->load->model('Article_model', 'obj_article');
        //sin database en libraries: $autoload['libraries'] = array();
        $this->load->model('Article_model', 'obj_article', TRUE);
        
        $this->load->library('Ci_elasticsearch');
    }
    
    public function index() {
        try {
            $response = $this->ci_elasticsearch->get_info();
            $data['version'] =  $response['version']['number'];
            $this->load->view('home/index', $data);
        }catch(\Throwable $e) {
            echo $e->getMessage();
        }
    }
    
    public function create() {
        $this->load->view('home/create_article');
    }
    
    public function save_article() {
        if($this->input->post()) {
            $this->form_validation->set_rules('title', 'Título', 'required|trim');
            $this->form_validation->set_rules('description', 'Descripción', 'required|trim');
            $this->form_validation->set_message('required', 'Campo %s es requerido.');
            if($this->form_validation->run($this) === FALSE) {
                $this->session->set_flashdata('error', validation_errors());
                $this->redirect_refer();
            }
            $article = [
                'title' => $this->input->post('title'),
                'description' => $this->input->post('description'),
                'created_at' => date('Y-m-d H:i:s')];
            $obj_article = $this->obj_article->save_article($article);
            if($obj_article['status'] === TRUE) {
                $exists_index = $this->ci_elasticsearch->exists_index('es_articles');
                if($exists_index === 200) {
                    $body = ['title' => $article['title'], 'description' => $article['description']];
                    $this->ci_elasticsearch->create_document('es_articles', 'es_articles', $obj_article['id'], $body);
                }
                $this->session->set_flashdata('success', 'Artículo creado.');
            }else {
                $this->session->set_flashdata('error', 'Artículo no fue creado.');
            }
            redirect('/home/create');    
        }else {
            echo 'No es POST.'. PHP_EOL;
        }
    }
    
    public function search() {
        $query = '';
        if($this->input->get()) {
            $this->form_validation->set_data($this->input->get());
            $this->form_validation->set_rules('query', 'Buscar', 'required|trim');
            $this->form_validation->set_message('required', 'Campo %s es requerido.');
            if($this->form_validation->run($this) == FALSE) {
                $this->session->set_flashdata('error', validation_errors());
                redirect('/home/search');    
            }
            $query = $this->input->get('query');
        }
        $data = [
            'articulos' => [], 
            'total' => 0, 
            'msg' => 'No se encontraron resultados.'];
        if(!isset($query) || $query == '' || $query == NULL) {
            $query_search = ['match_all' => new \stdClass()];
            $all = TRUE;
        }else {
            $query_search = [
                'multi_match' => [
                    'fields' => ['title', 'description'],
                    'query' => $query]];
            $all = FALSE;
        }
        $response_count = $this->ci_elasticsearch->count_documents('es_articles', 'es_articles', $query_search);
        if(isset($response_count['count']) && $response_count['count'] > 0) {
            $response = $this->ci_elasticsearch->search_document('es_articles', 'es_articles', 0, $response_count['count'], $query_search, $all);
            $data = [
                'articulos' => $response['hits']['hits'], 
                'total' => $response['hits']['total']['value'],
                'msg' => 'Se encontraron resultados.'];
        }
        $this->load->view('home/search_article', $data);
    }
    
    public function edit($codigo = 0) {
        $id = (int) $codigo;
        $data = [
            'id' => $id,
            'title' => '',
            'description' => ''];
        $obj_article = $this->obj_article->get_article($id);
        if($obj_article) {
            $data = [
                'id' => $id,
                'title' => $obj_article->title,
                'description' => $obj_article->description];
        }
        $this->load->view('home/edit_article', $data);
    }
    
    public function update_article() {
        if($this->input->post()) {
            $id = $this->input->post('id');
            $this->form_validation->set_rules('title', 'Título', 'required|trim');
            $this->form_validation->set_rules('description', 'Descripción', 'required|trim');
            $this->form_validation->set_rules('id', 'Id', 'required|trim|is_natural_no_zero', 
                array('is_natural_no_zero' => 'Para editar, el %s debe ser un valor numérico mayor a 0.'));
            $this->form_validation->set_message('required', 'Campo %s es requerido.');
            if($this->form_validation->run($this) == FALSE) {
                $this->session->set_flashdata('error', validation_errors());
                if(is_numeric($id) && $id > 0) {
                    redirect('/home/edit/'.$id);
                }
                redirect('/home/edit');
            }
            $exists_article = $this->obj_article->exists_article($id);
            if(!$exists_article) {
                $this->session->set_flashdata('error', '<p>Artículo no existe.</p>');
                redirect('/home/edit/'.$id);
            }
            $article = [
                'title' => $this->input->post('title'),
                'description' => $this->input->post('description'),
                'updated_at' => date('Y-m-d H:i:s')];
            $obj_article = $this->obj_article->update_article($id, $article);
            if($obj_article === TRUE) {
                $exists_index = $this->ci_elasticsearch->exists_index('es_articles');
                if($exists_index === 200) {
                    $response = $this->ci_elasticsearch->exists_document('es_articles', 'es_articles', $id);
                    if($response->getStatusCode() === 200) {
                        $body = [
                            'doc' => [
                                'title' => $this->input->post('title'), 
                                'description' => $this->input->post('description')]];
                        $response = $this->ci_elasticsearch->update_document('es_articles', 'es_articles', $id, $body);
                    }else {
                        $body = [
                            'title' => $this->input->post('title'), 
                            'description' =>  $this->input->post('description')];
                        $response = $this->ci_elasticsearch->create_document('es_articles', 'es_articles', $id, $body);
                    }
                }
                $this->session->set_flashdata('success', 'Artículo actualizado.');
            }else {
                $this->session->set_flashdata('error', 'Artículo no fue actualizado.');
            }
            redirect('/home/edit/'.$id); 
        }else {
            echo 'No es POST.'. PHP_EOL;
        }
    }
    
    public function delete($codigo = 0) {
        $id = (int) $codigo;
        $this->form_validation->set_data(['id' => $id]);
        $this->form_validation->set_rules('id', 'Id', 'required|trim|is_natural_no_zero', 
                array('is_natural_no_zero' => 'Para eliminar, el %s debe ser un valor numérico mayor a 0.'));
        $this->form_validation->set_message('required', 'Campo %s es requerido.');
        if($this->form_validation->run($this) == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('/home/search');
        }
        $exists_article = $this->obj_article->exists_article($id);
        if(!$exists_article) {
            $this->session->set_flashdata('error', '<p>Artículo no existe.</p>');
            redirect('/home/search');
        }
        $obj_article = $this->obj_article->delete_article($id);
        if($obj_article === TRUE) {
            $exists_index = $this->ci_elasticsearch->exists_index('es_articles');
            if($exists_index === 200) {
                $response = $this->ci_elasticsearch->delete_document('es_articles', 'es_articles', $id);
            }
            $this->session->set_flashdata('success', 'Artículo eliminado.');
        }else {
            $this->session->set_flashdata('error', 'Artículo no fue elimiando.');
        }
        redirect('/home/search');
    }
    
    public function redirect_refer() {
        if(isset($_SERVER["HTTP_REFERER"])) {
            redirect($_SERVER["HTTP_REFERER"]);
        }else {
            redirect("/");
        }
    }
}
