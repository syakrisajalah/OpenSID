<?php
class Migrasi_2002_ke_2003 extends CI_model {

	public function up()
	{
		$this->surat_mandiri();
		$this->mailbox();
	}

	private function surat_mandiri()
	{
    // Table ref_syarat_surat tempat nama dokumen sbg syarat Permohonan surat
		if (!$this->db->table_exists('ref_syarat_surat') )
		{
	    $this->dbforge->add_field(array(
				'ref_syarat_id' => array(
					'type' => 'INT',
					'constraint' => 1,
					'unsigned' => TRUE,
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'ref_syarat_nama' => array(
					'type' => 'VARCHAR',
					'constraint' => 255,
					'null' => FALSE,
				),
			));
			$this->dbforge->add_key("ref_syarat_id",true);
			$this->dbforge->create_table("ref_syarat_surat", TRUE);

	    // Menambahkan Data Table ref_syarat_surat
	    $query = "
	    INSERT INTO `ref_syarat_surat` (`ref_syarat_id`, `ref_syarat_nama`) VALUES
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

    // Table syarat_surat sbg link antara surat yg dimohon dan dokumen yg diperlukan
		if (!$this->db->table_exists('syarat_surat') )
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
				'ref_syarat_id' => array(
					'type' => 'INT',
					'constraint' => 10,
					'null' => FALSE,

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("syarat_surat", TRUE);
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
				),
				'keterangan' => array(
					'type' => 'TEXT',
					'null' => TRUE
				),
				'no_hp_aktif' => array(
					'type' => 'VARCHAR',
					'constraint' => 50
				),
				'syarat' => array(
					'type' => 'TEXT'
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

	private function mailbox()
	{
		$modul_mailbox = array(
			'modul' => 'Kotak Pesan',
			'url' => 'mailbox/clear'
		);

		$this->db
			->where('id', '55')
			->update('setting_modul', $modul_mailbox);

		// Tambahkan kolom untuk menandai apakah pesan diarsipkan atau belum
		if (!$this->db->field_exists('is_archived', 'komentar')) 
		{
			$fields = array(
				'is_archived' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'default' => 0
				)
			);
			$this->dbforge->add_column('komentar', $fields);
		}

		// ubah nama kolom menjadi status untuk penanda status di mailbox
		if ($this->db->field_exists('enabled', 'komentar')) 
		{
			$this->dbforge->modify_column('komentar', array(
				'enabled' => array(
					'name' => 'status',
					'type' => 'TINYINT',
					'constraint' => 1
				)
			));
		}

		// Tambahkan kolom tipe untuk membedakan pesan inbox dan outbox
		if (!$this->db->field_exists('tipe', 'komentar')) 
		{
			$fields = array(
				'tipe' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'after' => 'status'
				)
			);
			$this->dbforge->add_column('komentar', $fields);
		}

		// Paksa data lapor yang sudah ada memiliki tipe inbox
		$tipe = array(
			'tipe' => '1',
		);
		$this->db
			->where('id_artikel', '775')
			->where('tipe', NULL)
			->update('komentar', $tipe);

		// Tambahkan kolom subjek untuk digunakan di menu mailbox
		if (!$this->db->field_exists('subjek', 'komentar')) 
		{
			$this->dbforge->add_column('komentar', array(
				'subjek' => array(
					'type' => 'TINYTEXT',
					'after' => 'email'
				)
			));
		}

		$subjek = array(
			'subjek' => 'Tidak ada subjek pesan',
		);
		$this->db
			->where('id_artikel', '775')
			->where('subjek', NULL)
			->update('komentar', $subjek);

		// Tambahkan kolom id_syarat untuk link ke dokumen syarat
		if (!$this->db->field_exists('id_syarat', 'dokumen')) 
		{
			$fields = array(
				'id_syarat' => array(
					'type' => 'INT',
					'constraint' => 11,
					'after' => 'deleted'
				)
			);
			$this->dbforge->add_column('dokumen', $fields);
		}
	}

}
