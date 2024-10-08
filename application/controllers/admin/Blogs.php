<?php defined('BASEPATH') OR exit('No direct script access allowed'); 
/**
 * Blogs Controller
 *
 * This class handles blogs module functionality
 *
 * @package     classiebit
 * @author      prodpk
*/

class Blogs extends Admin_Controller {

    /**
     * Constructor
    **/
    function __construct()
    {
        parent::__construct();
        $this->load->model('admin/blogs_model');

        $this->load->helper('string');
        

        // Page Title
        $this->set_title( lang('menu_blogs') );
    }


    /**
     * index
     */
    function index()
    {
         /* Initialize assets */
        $this->include_index_plugins();
        $data = $this->includes;

        // Table Header
        $data['t_headers']  = array(
                                '#',
                                lang('common_title'),
                                lang('menu_user'),
                                lang('common_updated'),
                                lang('common_status'),
                                lang('action_action'),
                            );

        // load views
        $content['content'] = $this->load->view('admin/index', $data, TRUE);
        $this->load->view($this->template, $content);
    }

    /**
     * ajax_list
    */
    public function ajax_list()
    {
        $this->load->library('datatables');

        $table              = 'blogs';        
        $columns            = array(
                                "$table.id",
                                "$table.title",
                                "$table.users_id",
                                "$table.status",
                                "$table.date_updated",
                                "(SELECT u.username FROM users u WHERE u.id = $table.users_id) username",
                            );
        $columns_order      = array(
                                "#",
                                "$table.title",
                                "$table.users_id",
                                "$table.date_updated",
                                "$table.status",
                            );
        $columns_search     = array(
                                'title',
                                'slug',
                            );
        $order              = array('date_updated' => 'DESC');
        
        $result             = $this->datatables->get_datatables($table, $columns, $columns_order, $columns_search, $order);
        $data               = array();
        $no                 = $_POST['start'];
        
        foreach ($result as $val) 
        {
            $no++;
            $row            = array();
            $row[]          = $no;
            $row[]          = mb_substr($val->title, 0, 30, 'utf-8');
            $row[]          = $val->username;
            $row[]          = date('g:iA d/m/y', strtotime($val->date_updated));
            $row[]          = $val->status == 1 ? '<span class="label label-success">'.lang('blogs_status_published').'</span>' : ($val->status == 2 ? '<span class="label label-warning">'.lang('blogs_status_pending').'</span>' : '<span class="label label-default">'.lang('blogs_status_disabled').'</span>');
            $row[]          = action_buttons('blogs', $val->id, mb_substr($val->title, 0, 20, 'utf-8'), lang('menu_blog'));
            $data[]         = $row;
        }
 
        $output             = array(
                                "draw"              => $_POST['draw'],
                                "recordsTotal"      => $this->datatables->count_all(),
                                "recordsFiltered"   => $this->datatables->count_filtered(),
                                "data"              => $data,
                            );
        
        //output to json format
        echo json_encode($output);exit;
    }


    /**
     * form
    */
    public function form($id = NULL)
    {
        /* Initialize assets */
        $this->add_plugin_theme(array(   
                                "tinymce/tinymce.min.js",
                            ), 'admin')
             ->add_js_theme( "pages/blogs/form_i18n.js", TRUE );
        $data                       = $this->includes;
        
        // in case of edit
        $id                         = (int) $id;
        if($id)
        {
            $result                 = $this->blogs_model->get_blogs_by_id($id);

            if(empty($result))
            {
                $this->session->set_flashdata('error', sprintf(lang('alert_not_found') ,lang('menu_blog')));
                redirect($this->uri->segment(1).'/'.$this->uri->segment(2));            
            }

            // hidden field in case of update
            $data['id']             = $result->id; 
            
            // current image 
            $data['c_image']        = $result->image;
        }
            
        $data['title']      = array(
            'name'          => 'title',
            'id'            => 'title',
            'type'          => 'text',
            'class'         => 'form-control',
            'value'         => $this->form_validation->set_value('title', !empty($result->title) ? $result->title : ''),
        );
        $data['slug']      = array(
            'name'          => 'slug',
            'id'            => 'slug',
            'type'          => 'text',
            'readonly'      => 'readonly',
            'class'         => 'form-control',
            'value'         => $this->form_validation->set_value('slug', !empty($result->slug) ? $result->slug : ''),
        );
        $data['content']      = array(
            'name'          => 'content',
            'id'            => 'content',
            'type'          => 'textarea',
            'class'         => 'form-control tinymce',
            'value'         => $this->form_validation->set_value('content', !empty($result->content) ? $result->content : ''),
        );
        $data['image'] = array(
            'name'      => 'image',
            'id'        => 'image',
            'type'      => 'file',
            'class'     => 'form-control',
            'accept'    => 'image/*',
        );
        $data['meta_title']= array(
            'name'          => 'meta_title',
            'id'            => 'meta_title',
            'type'          => 'text',
            'class'         => 'form-control',
            'value'         => $this->form_validation->set_value('meta_title', !empty($result->meta_title) ? $result->meta_title : ''),
        );
        $data['meta_tags']= array(
            'name'          => 'meta_tags',
            'id'            => 'meta_tags',
            'type'          => 'text',
            'class'         => 'form-control',
            'value'         => $this->form_validation->set_value('meta_tags', !empty($result->meta_tags) ? $result->meta_tags : ''),
        );
        $data['meta_description']= array(
            'name'          => 'meta_description',
            'id'            => 'meta_description',
            'type'          => 'textarea',
            'class'         => 'form-control',
            'value'         => $this->form_validation->set_value('meta_description', !empty($result->meta_description) ? $result->meta_description : ''),
        );
        $data['status']     = array(
            'name'          => 'status',
            'id'            => 'status',
            'class'         => 'form-control show-tick',
            'options'       => array(
                                    '0' => lang('blogs_status_disabled'),
                                    '1' => lang('blogs_status_published'),
                                    '2' => lang('blogs_status_pending'),
                                ),
            'selected'      => $this->form_validation->set_value('status', !empty($result->status) ? $result->status : 0),
        );
        
        /* Load Template */
        $content['content']    = $this->load->view('admin/blogs/form', $data, TRUE);
        $this->load->view($this->template, $content);
    }

    /**
     * save
    */
    public function save()
    {
        $id = NULL;
        if(! empty($_POST['id']))
        {
            if(!$this->acl->get_method_permission($_SESSION['groups_id'], 'blogs', 'p_edit'))
            {
                echo '<p>'.sprintf(lang('manage_acl_permission_no'), lang('manage_acl_edit')).'</p>';exit;
            }

            $id                     = (int) $this->input->post('id');

            $result                 = $this->blogs_model->get_blogs_by_id($id);

            if(empty($result))
            {
                $this->session->set_flashdata('message', sprintf(lang('alert_not_found'), lang('menu_blog')));
                echo json_encode(array(
                                        'flag'  => 0, 
                                        'msg'   => $this->session->flashdata('message'),
                                        'type'  => 'fail',
                                    ));
                exit;
            }
        }
        else
        {
            if(!$this->acl->get_method_permission($_SESSION['groups_id'], 'blogs', 'p_add'))
            {
                echo '<p>'.sprintf(lang('manage_acl_permission_no'), lang('manage_acl_add')).'</p>';exit;
            }
        }

        /* Validate form input */
        $this->form_validation
        ->set_rules('title', lang('common_title'), 'trim|required|max_length[256]|alpha_dash_spaces')
        ->set_rules('content', lang('blogs_content'), 'trim')
        ->set_rules('meta_title', lang('common_meta_title'), 'trim')
        ->set_rules('meta_tags', lang('common_meta_tags'), 'trim')
        ->set_rules('meta_description', lang('common_meta_description'), 'trim')
        ->set_rules('status', lang('common_status'), 'required|in_list[0,1,2]');

        /*Validate Title duplicacy & level*/
        if($id) 
        {
            if(isset($_POST['title']))
                if($this->input->post('title') !== $result->title)
                    $this->form_validation->set_rules('title', lang('common_title'), 'trim|required|max_length[256]|alpha_dash_spaces|is_unique[blogs.title]');
        }
        else
        {   
            $this->form_validation
            ->set_rules('title', lang('common_title'), 'trim|required|max_length[256]|alpha_dash_spaces|is_unique[blogs.title]');
        }

        if(! empty($_FILES['image']['name'])) // if image 
        {
            $file_image         = array('folder'=>'blogs/images', 'input_file'=>'image');
            // Remove old image
            if($id)
                $this->file_uploads->remove_file('./upload/'.$file_image['folder'].'/', $result->image);
            // update blogs image            
            $filename_image     = $this->file_uploads->upload_file($file_image);
            // through image upload error
            if(!empty($filename_image['error']))
                $this->form_validation->set_rules('image_error', lang('common_image'), 'required', array('required'=>$filename_image['error']));
        }
        
        if($this->form_validation->run() === FALSE)
        {
            // for fetching specific fields errors in order to view errors on each field seperately
            $error_fields = array();
            foreach($_POST as $key => $val)
                if(form_error($key))
                    $error_fields[] = $key;
            
            echo json_encode(array('flag' => 0, 'msg' => validation_errors(), 'error_fields' => json_encode($error_fields)));
            exit;
        }

        $data                           = array();
        $data['users_id']               = $this->user['id'];
        $data['title']                  = $this->input->post('title');
        $data['slug']                   = url_title($data['title'], 'dash', TRUE);
        $data['content']                = $this->input->post('content');
        $data['meta_title']             = $this->input->post('meta_title');
        $data['meta_tags']              = $this->input->post('meta_tags');
        $data['meta_description']       = $this->input->post('meta_description');
        $data['status']                 = $this->input->post('status');

        if(! empty($filename_image) && ! isset($filename_image['error']))
            $data['image']          = $filename_image;
        
        $flag                           = $this->blogs_model->save_blogs($data, $id);

        if($flag)
        {
            if($id)
                $this->session->set_flashdata('message', sprintf(lang('alert_update_success'), lang('menu_blog')));
            else
                $this->session->set_flashdata('message', sprintf(lang('alert_insert_success'), lang('menu_blog')));

            echo json_encode(array(
                                    'flag'  => 1, 
                                    'msg'   => $this->session->flashdata('message'),
                                    'type'  => 'success',
                                ));
            exit;
        }
        
        if($id)
            $this->session->set_flashdata('error', sprintf(lang('alert_update_fail'), lang('menu_blog')));
        else
            $this->session->set_flashdata('error', sprintf(lang('alert_insert_fail'), lang('menu_blog')));

        echo json_encode(array(
                                'flag'  => 0, 
                                'msg'   => $this->session->flashdata('message'),
                                'type'  => 'fail',
                            ));
        exit;
    }

    /**
     * view
     */
    public function view($id = NULL)
    {
        /* Initialize assets */
        $data                   = $this->includes;

        /* Get Data */
        $id                     = (int) $id;
        $result                 = $this->blogs_model->get_blogs_by_id($id);

        if(empty($result))
        {
            $this->session->set_flashdata('error', sprintf(lang('alert_not_found') ,lang('menu_blog')));
            redirect($this->uri->segment(1).'/'.$this->uri->segment(2));            
        }

        $data['blogs']          = $result;
        
        /* Load Template */
        $content['content']    = $this->load->view('admin/blogs/view', $data, TRUE);
        $this->load->view($this->template, $content);
    }

     /**
     * delete
     */
    public function delete()
    {
        if(!$this->acl->get_method_permission($_SESSION['groups_id'], 'blogs', 'p_delete'))
        {
            echo '<p>'.sprintf(lang('manage_acl_permission_no'), lang('manage_acl_delete')).'</p>';exit;
        }
        /* Validate form input */
        $this->form_validation->set_rules('id', sprintf(lang('alert_id'), lang('menu_blog')), 'required|numeric');

        /* Get Data */
        $id                 = (int) $this->input->post('id');
        $result             = $this->blogs_model->get_blogs_by_id($id);

        if(empty($result))
        {
            echo json_encode(array(
                                    'flag'  => 0, 
                                    'msg'   => sprintf(lang('alert_not_found') ,lang('menu_blog')),
                                    'type'  => 'fail',
                                ));exit;
        }


        $flag                   = $this->blogs_model->delete_blogs($id, $result);

        if($flag)
        {
            // Remove image
            if(!empty($result->image))
                $this->file_uploads->remove_file('./upload/blogs/images/', $result->image);
            
            echo json_encode(array(
                                    'flag'  => 1, 
                                    'msg'   => sprintf(lang('alert_delete_success'), lang('menu_blog')),
                                    'type'  => 'success',
                                ));exit;
        }
        
        echo json_encode(array(
                                    'flag'  => 0, 
                                    'msg'   => sprintf(lang('alert_delete_fail'), lang('menu_blog')),
                                    'type'  => 'fail',
                                ));
        exit;
    }


}

/* Blogs controller ends */