<?php
class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_user($username) {
        $this->db->where('username', $username);
        $query = $this->db->get('admin');
        return $query->row();
    }

    public function verify_password($password, $hashed_password) {
        return password_verify($password, $hashed_password);
    }
}