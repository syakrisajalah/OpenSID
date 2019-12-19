<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mailbox extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();
		session_start();
		$this->load->model('header_model');
		$this->load->model('web_komentar_model');
		$this->load->model('mandiri_model');
		$this->load->model('mailbox_model');
		$this->load->model('config_model');
		$this->modul_ini = 14;
	}

	public function clear($kat = 1, $p = 1, $o = 0)
	{
		unset($_SESSION['cari']);
		unset($_SESSION['filter_status']);
		unset($_SESSION['filter_nik']);
		unset($_SESSION['filter_archived']);
		redirect("mailbox/index/$kat/$p/$o");
	}

	public function index($kat = 1, $p = 1, $o = 0)
	{
		$data['p'] = $p;
		$data['o'] = $o;
		$data['kat'] = $kat;

		$list_session = array('cari', 'filter_status', 'filter_nik', 'filter_archived', 'per_page');

		foreach ($list_session as $session) {
			$data[$session] = $this->session->userdata($session) ?: '';
		}

		if ($nik = $this->session->userdata('filter_nik')) {
			$data['individu'] = $this->mandiri_model->get_pendaftar_mandiri($nik);
		}

		$data['paging'] = $this->web_komentar_model->paging($p, $o, $kat);
		$data['main'] = $this->web_komentar_model->list_data($o, $data['paging']->offset, $data['paging']->per_page, $kat);
		$data['keyword'] = $this->web_komentar_model->autocomplete();
		$data['submenu'] = $this->mailbox_model->list_menu();
		
		foreach ($data['submenu'] as $id => $value) 
		{
			if ($kat == $id)
			{
				$session = array(
					'submenu' => $id
				);
				
				$this->session->set_userdata($session);
			}
		}

		$header = $this->header_model->get_data();
		$nav['act'] = 14;
		$nav['act_sub'] = 55;
		$header['minsidebar'] = 1;

		$this->load->view('header', $header);
		$this->load->view('nav', $nav);
		$this->load->view('mailbox/table', $data);
		$this->load->view('footer');
	}

	public function baca_pesan($kat = 1, $id)
	{
		$this->web_komentar_model->komentar_lock($id, 1);
		unset($_SESSION['success']);
		
		$data['kat'] = $kat;
		$data['pesan'] = $this->web_komentar_model->get_komentar($id);
		$data['tipe_mailbox'] = $this->mailbox_model->get_kat_nama($kat); 
		$header = $this->header_model->get_data();
		$nav['act'] = 14;
		$nav['act_sub'] = 55;

		$this->load->view('header', $header);
		$this->load->view('nav', $nav);
		$this->load->view('mailbox/detail', $data);
		$this->load->view('footer');
	}

	public function search()
	{
		$cari = $this->input->post('cari');
		if ($cari != '')
			$_SESSION['cari'] = $cari;
		else unset($_SESSION['cari']);
		redirect('mailbox');
	}

	public function filter_status()
	{
		$status = $this->input->post('status');
		if ($status != 0){
			if ($status == 3) {
				$_SESSION['filter_archived'] = true;
				unset($_SESSION['filter_status']);
			} else {
				$_SESSION['filter_status'] = $status;
				unset($_SESSION['filter_archived']);
			}
		}
		else {
			unset($_SESSION['filter_status']);
			unset($_SESSION['filter_archived']);
		} 
		redirect('mailbox');
	}

	public function filter_nik($kat = 1)
	{
		$nik = $this->input->post('nik');
		if (!empty($nik) AND $nik != 0) 
			$_SESSION['filter_nik'] = $nik;
		else unset($_SESSION['filter_nik']);
		redirect("mailbox/index/{$kat}");
	}

	public function list_pendaftar_mandiri_ajax()
	{
		$cari = $this->input->get('q');
		$page = $this->input->get('page');
		$list_pendaftar_mandiri = $this->mandiri_model->list_data_ajax($cari, $page);
		echo json_encode($list_pendaftar_mandiri);
	}

	/**
	 * Kirim mailboxan pengguna layanan mandiri
	 *
	 * Diakses dari web untuk pengguna layanan mandiri
	 * Tidak memerlukan login pengguna modul admin
	 */
	public function insert()
	{
		if ($_SESSION['mandiri'] != 1)
		{
			redirect('first');
		}

		$_SESSION['success'] = 1;
		$res = $this->mailbox_model->insert();
		$data['data_config'] = $this->config_model->get_data();
		// cek kalau berhasil disimpan dalam database
		if ($res)
		{
			$this->session->set_flashdata('flash_message', 'mailboxan anda telah berhasil dikirim dan akan segera diproses.');
		}
		else
		{
			$_SESSION['post'] = $_POST;
			if (!empty($_SESSION['validation_error']))
				$this->session->set_flashdata('flash_message', validation_errors());
			else
				$this->session->set_flashdata('flash_message', 'mailboxan anda gagal dikirim. Silakan ulangi lagi.');
		}

		redirect("first/mandiri/1/3");
	}

	public function archive($kat = 1, $p = 1, $o = 0, $id = '')
	{
		$this->redirect_hak_akses('h', "mailbox/index/$p/$o");
		$this->web_komentar_model->archive($id);
		redirect("mailbox/index/$kat/$p/$o");
	}

	public function archive_all($kat = 1, $p = 1, $o = 0)
	{
		$this->redirect_hak_akses('h', "mailbox/index/$p/$o");
		$this->web_komentar_model->archive_all();
		redirect("mailbox/index/$kat/$p/$o");
	}

	public function komentar_lock($id = '')
	{
		$this->web_komentar_model->komentar_lock($id, 1);
		redirect("mailbox/index/$p/$o");
	}

	public function komentar_unlock($id = '')
	{
		$this->web_komentar_model->komentar_lock($id, 2);
		redirect("mailbox/index/$p/$o");
	}
}
