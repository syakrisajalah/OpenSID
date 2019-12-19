<?php class Mailbox_model extends CI_Model {

	/**
	 * Gunakan model ini untuk memindahkan semua method terkait laporan layanan mandiri.
	 * Saat ini laporan layanan mandiri masih bercampur dengan komentar artikel, dan
	 * seharusnya dipisah.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('referensi_model');
		
	}

	/**
	 * Simpan laporan yang dikirim oleh pengguna layanan mandiri
	 */
	public function insert()
	{
		$data['komentar'] = strip_tags(" Permohonan Surat ".$_POST["nama_surat"]." - ".$_POST["hp"]." - ".$_POST["komentar"]);
		/** ambil dari data session saja */
		$data['owner'] = $_SESSION['nama'];
		$data['email'] = $_SESSION['nik'];

		// load library form_validation
		$this->load->library('form_validation');
		$this->form_validation->set_rules('komentar', 'Laporan');
		$this->form_validation->set_rules('owner', 'Nama', 'required');
		$this->form_validation->set_rules('email', 'NIK', 'required');

		if ($this->form_validation->run() == TRUE)
		{
			unset($_SESSION['validation_error']);
			$data['enabled'] = 2;
			$data['id_artikel'] = 775; //id_artikel untuk laporan layanan mandiri
			$outp = $this->db->insert('komentar',$data);
		}
		else
		{
			$_SESSION['validation_error'] = 'Form tidak terisi dengan benar';
			$_SESSION['success'] = -1;
		}
		if (!$outp)
			$_SESSION['success'] = -1;
		return ($_SESSION['success'] == 1);
	}

	public function list_menu()
	{
		return $this->referensi_model->list_kode_array(KATEGORI_MAILBOX);
	}

	public function get_kat_nama($kat)
	{
		$sub_menu = $this->list_menu();	
		$data = $sub_menu[$kat];
		return $data;
	}

}
?>
