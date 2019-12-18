<?php
class Migrasi_1912_ke_2001 extends CI_model {

	public function up()
	{
	  $this->siskeudes_2019();
	  // Sesuaikan dengan sql_mode STRICT_TRANS_TABLES
		$this->db->query("ALTER TABLE user MODIFY COLUMN last_login datetime NULL");
		$this->surat_mandiri();		  
	}

	private function surat_mandiri()
	{
    // Table ref_surat_format tempat nama dokumen sbg syarat Permohonan surat
		if (!$this->db->table_exists('ref_surat_format') )
		{
	    $this->dbforge->add_field(array(
				'ref_surat_id' => array(
					'type' => 'INT',
					'constraint' => 1,
					'unsigned' => TRUE,
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'ref_surat_nama' => array(
					'type' => 'VARCHAR',
					'constraint' => 255,
					'null' => FALSE,
				),
			));
			$this->dbforge->add_key("ref_surat_id",true);
			$this->dbforge->create_table("ref_surat_format", TRUE);

	    // Menambahkan Data Table ref_surat_format
	    $query = "
	    INSERT INTO `ref_surat_format` (`ref_surat_id`, `ref_surat_nama`) VALUES
		    (1, 'Surat Pengantar RT/RW'),
		    (2, 'Fotokopi KK'),
		    (3, 'Fotokopi KTP'),
		    (4, 'Fotokopi Surat Nikah/Akta Nikah/Kutipan Akta Perkawinan'),
		    (5, 'Fotokopi Akta Kelahiran/Surat Kelahiran bagi keluarga yang mempunyai anak'),
		    (6, 'Surat Pindah Datang dari tempat asal'),
		    (7, 'Surat Keterangan Kematian dari Rumah Sakit, Rumah Bersalin Puskesmas, atau visum Dokter'),
		    (8, 'Surat Keterangan Cerai'),
		    (9, 'Fotokopi Ijasah Terakhir'),
		    (10, 'SK. PNS/KARIP/SK. TNI – POLRI'),
		    (11, 'Surat Keterangan Kematian dari Kepala Desa/Kelurahan'),
		    (12, 'Surat imigrasi / STMD (Surat Tanda Melapor Diri)');
	    ";
	    $this->db->query($query);
	  }

    // Table surat_format_ref sbg link antara surat yg dimohon dan dokumen yg diperlukan
		if (!$this->db->table_exists('surat_format_ref') )
		{
	    $this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 10,
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'surat_format_id' => array(
					'type' => 'INT',
					'constraint' => 10,
					'null' => FALSE,

				),
				'ref_surat_id' => array(
					'type' => 'INT',
					'constraint' => 10,
					'null' => FALSE,

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("surat_format_ref", TRUE);
		}

    // Menambahkan menu 'Group / Hak Akses' ke table 'setting_modul'
    $data = array();
    $data[] = array(
      'id'=>'97',
      'modul' => 'Daftar Persyaratan',
      'url' => 'surat_mohon',
      'aktif' => '1',
      'ikon' => 'fa fa-book',
      'urut' => '5',
      'level' => '2',
      'hidden' => '0',
      'ikon_kecil' => '',
      'parent' => 4);

    foreach ($data as $modul)
    {
      $sql = $this->db->insert_string('setting_modul', $modul);
      $sql .= " ON DUPLICATE KEY UPDATE
      id = VALUES(id),
      modul = VALUES(modul),
      url = VALUES(url),
      aktif = VALUES(aktif),
      ikon = VALUES(ikon),
      urut = VALUES(urut),
      level = VALUES(level),
      hidden = VALUES(hidden),
      ikon_kecil = VALUES(ikon_kecil),
      parent = VALUES(parent)";
      $this->db->query($sql);
    }

    // Tambah kolom tanda surat yg tersedia untuk layanan mandiri
		if (!$this->db->field_exists('mandiri','tweb_surat_format'))
		{
			$this->db->query("ALTER TABLE tweb_surat_format ADD mandiri tinyint(1) default 0");
		}

    // Tabel mendaftarkan permohonan surat dari layanan mandiri
		if (!$this->db->table_exists('permohonan_surat'))
		{
	    $this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'auto_increment' => TRUE
				),
				'id_pemohon' => array(
					'type' => 'INT',
					'constraint' => 11,
					'null' => FALSE
				),
				'id_surat' => array(
					'type' => 'INT',
					'constraint' => 11,
					'null' => FALSE
				),
				'isian_form' => array(
					'type' => 'TEXT'
				),
				'status' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'default' => 0
				)
			));
			$this->dbforge->add_field("created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$this->dbforge->add_field("updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$this->dbforge->add_key("id", true);
			$this->dbforge->create_table("permohonan_surat", TRUE);
		}
		// Menu permohonan surat untuk operator
		$modul = array(
			'id' => '98',
			'modul' => 'Permohonan Surat',
			'url' => 'permohonan_surat_admin/clear',
			'aktif' => '1',
			'ikon' => 'fa-files-o',
			'urut' => '0',
			'level' => '0',
			'parent' => '14',
			'hidden' => '0',
			'ikon_kecil' => ''
		);
		$sql = $this->db->insert_string('setting_modul', $modul) . " ON DUPLICATE KEY UPDATE modul = VALUES(modul), url = VALUES(url), ikon = VALUES(ikon), parent = VALUES(parent)";
		$this->db->query($sql);
	}

	private function siskeudes_2019()
	{
		// Ubah tabel keuangan untuk Siskeudes 2019
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_anggaran'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_anggaran ADD Kd_SubRinci varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_anggaran_log ADD No_Perkades varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_anggaran_log ADD Petugas varchar(80) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_anggaran_log add Tanggal varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_anggaran_log MODIFY COLUMN UserID VARCHAR(50) NOT NULL");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan add Jbt_PPTKD varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan add Kd_Sub varchar(30) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan add Nilai BIGINT UNSIGNED");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan add NilaiPAK BIGINT UNSIGNED");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan add Satuan VARCHAR(30)");
			$this->db->query("ALTER TABLE keuangan_ta_kegiatan MODIFY COLUMN Kd_Bid varchar(100) NULL");		
		}
		if (!$this->db->field_exists('ID_Bank','keuangan_ref_bank_desa'))
		{
			$this->db->query("ALTER TABLE keuangan_ref_bank_desa ADD ID_Bank varchar(10) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ref_bank_desa MODIFY COLUMN Alamat_Pemilik varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ref_bank_desa MODIFY COLUMN Nama_Pemilik varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ref_bank_desa MODIFY COLUMN No_Identitas varchar(20) NULL");		
		$this->db->query("ALTER TABLE keuangan_ref_bank_desa MODIFY COLUMN No_Telepon varchar(20) NULL");		
		if (!$this->db->field_exists('Jns_Kegiatan','keuangan_ref_kegiatan'))
		{
			$this->db->query("ALTER TABLE keuangan_ref_kegiatan ADD Jns_Kegiatan tinyint(5)");
			$this->db->query("ALTER TABLE keuangan_ref_kegiatan ADD Kd_Sub varchar(30) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ref_kegiatan MODIFY COLUMN Kd_Bid varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ref_korolari MODIFY COLUMN Jenis varchar(30) NULL");		
		if (!$this->db->field_exists('ID_Bank','keuangan_ta_mutasi'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_mutasi ADD ID_Bank varchar(10) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_mutasi MODIFY COLUMN Keterangan varchar(200) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_mutasi MODIFY COLUMN Kd_Bank varchar(100) NULL");		
		if (!$this->db->field_exists('ID_Bank','keuangan_ta_pajak'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_pajak ADD ID_Bank varchar(10) NULL");
		}	
		if (!$this->db->field_exists('NTPN','keuangan_ta_pajak'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_pajak ADD NTPN varchar(30) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_pemda MODIFY COLUMN Logo MEDIUMBLOB NULL");		
		if (!$this->db->field_exists('ID_Bank','keuangan_ta_pencairan'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_pencairan ADD ID_Bank varchar(10) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_pencairan ADD Kunci varchar(10) NULL");
		}	
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_rab'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_rab ADD Kd_SubRinci varchar(10) NULL");
		}	
		if (!$this->db->field_exists('Kd_Sub','keuangan_ta_rpjm_kegiatan'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_rpjm_kegiatan ADD Kd_Sub varchar(30) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_rpjm_kegiatan MODIFY COLUMN Kd_Bid varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_rpjm_misi MODIFY COLUMN Uraian_Misi varchar(250) NULL");		
		if (!$this->db->field_exists('No_ID','keuangan_ta_rpjm_pagu_tahunan'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_rpjm_pagu_tahunan ADD No_ID varchar(20) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_rpjm_visi MODIFY COLUMN Uraian_Visi varchar(250) NULL");		
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_sppbukti'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_sppbukti ADD Kd_SubRinci varchar(10) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_sppbukti ADD No_SPP varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_sppbukti ADD Rek_Bank varchar(100) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_sppbukti MODIFY COLUMN Keterangan varchar(200) NULL");		
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_spp_rinci'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_spp_rinci ADD Kd_SubRinci varchar(10) NULL");
		}	
		if (!$this->db->field_exists('ID_Bank','keuangan_ta_tbp'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_tbp ADD ID_Bank varchar(10) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_tbp MODIFY COLUMN Uraian varchar(250) NULL");		
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_tbp_rinci'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_tbp_rinci ADD Kd_SubRinci varchar(10) NULL");
		}	
		if (!$this->db->field_exists('Agt','keuangan_ta_triwulan'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Jan varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Peb varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Mar varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Apr varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Mei varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Jun varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Jul varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Agt varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Sep varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Okt varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Nop varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Des varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan ADD Kd_SubRinci varchar(10) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_triwulan MODIFY COLUMN Tw1Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan MODIFY COLUMN Tw2Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan MODIFY COLUMN Tw3Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan MODIFY COLUMN Tw4Rinci varchar(100) NULL");		
		if (!$this->db->field_exists('Agt','keuangan_ta_triwulan_rinci'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Jan varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Peb varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Mar varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Apr varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Mei varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Jun varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Jul varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Agt varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Sep varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Okt varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Nop varchar(100) NULL");
			$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci ADD Des varchar(100) NULL");
		}	
		$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci MODIFY COLUMN Tw1Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci MODIFY COLUMN Tw2Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci MODIFY COLUMN Tw3Rinci varchar(100) NULL");		
		$this->db->query("ALTER TABLE keuangan_ta_triwulan_rinci MODIFY COLUMN Tw4Rinci varchar(100) NULL");
		// Sesuaikan tabel keuangan dengan sql_mode STRICT_TRANS_TABLES
		$this->db->query("ALTER TABLE keuangan_ta_spj_rinci MODIFY COLUMN Alamat varchar(100) NULL");
		$this->db->query("ALTER TABLE keuangan_ref_bank_desa MODIFY COLUMN Kantor_Cabang varchar(100) NULL");
		$this->db->query("ALTER TABLE keuangan_ta_pajak MODIFY COLUMN Keterangan varchar(250) NULL");
		$this->db->query("ALTER TABLE keuangan_ta_pencairan MODIFY COLUMN Keterangan varchar(250) NULL");
		$this->db->query("ALTER TABLE keuangan_ta_spp MODIFY COLUMN Keterangan varchar(250) NULL");
		$this->db->query("ALTER TABLE keuangan_ta_pemda MODIFY COLUMN Logo MEDIUMBLOB NULL");	
		// Sesuaikan dengan data 2019
		$this->db->query("ALTER TABLE keuangan_ta_rpjm_tujuan MODIFY COLUMN Uraian_Tujuan varchar(250)");		
		if (!$this->db->field_exists('Kunci','keuangan_ta_spj'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_spj ADD Kunci varchar(10) NULL");			
		}
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_spj_bukti'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_spj_bukti ADD Kd_SubRinci varchar(10) NULL");			
		}
		$this->db->query("ALTER TABLE keuangan_ta_spj_bukti MODIFY COLUMN Keterangan varchar(250)");		
		if (!$this->db->field_exists('Kd_SubRinci','keuangan_ta_spj_rinci'))
		{
			$this->db->query("ALTER TABLE keuangan_ta_spj_rinci ADD Kd_SubRinci varchar(10) NULL");			
		}
		$this->db->query("ALTER TABLE keuangan_ta_rpjm_sasaran MODIFY COLUMN Uraian_Sasaran varchar(250)");		
	}
}