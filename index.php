<?php
/**
 * LRsoft Corp.
 * https://lrsoft.id
 *
 * Author : Zaf
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Origin: ' . SSO_URI);

$_usso = Helpers::_arr($_POST, 'usso', array());
$_info = Helpers::_arr($_usso, 'info', array());
$_login = Helpers::_arr($_usso, 'login', array());
$_level = Helpers::_arr($_usso, 'level', array());

$_signature = Helpers::_arr($_usso, 'signature', time());
$__akses = Helpers::_arr($_level, 'kode_akses', time());

if (Helpers::_signature_verify($_signature, array($_login, $__akses), SSO_SECRET)) {

    switch (Helpers::_arr($_level, 'kode_view')) {

        case Helpers::sso_staf:

            $_kode = Helpers::_arr($_info, 'kode');
            $obj_staf = CStaf::_gi()->_get($_kode);
            $obj_staf || $obj_staf = new MStaf();

            $obj_staf->_init_SIA($_kode);

            if ($obj_staf->_empty())
                CStaf::_gi()->_insert(
                    $obj_staf->setStafKode($_kode));

            else CStaf::_gi()->_update($obj_staf);

            Sessions::_gi()->_set(
                Helpers::dir_staf, $_kode, $obj_staf);

            echo json_encode(array(
                'status' => true,
                'redirect' => Helpers::_a(Helpers::$_dir_default_page_map[Helpers::dir_staf])
            ));

            break;

        case Helpers::sso_dosen:

            $_kode = Helpers::_arr($_info, 'kode');
            $obj_dosen = CDosen::_gi()->_get($_kode);
            $obj_dosen || $obj_dosen = new MDosen();

            $obj_dosen
                ->_init_SIA($_kode)
                ->_filter();

            if ($obj_dosen->_empty())
                CDosen::_gi()->_insert(
                    $obj_dosen->setDosenKode($_kode));

            else CDosen::_gi()->_update($obj_dosen);

            Sessions::_gi()->_set(
                Helpers::dir_dosen, $_kode, $obj_dosen);

            echo json_encode(array(
                'status' => true,
                'redirect' => Helpers::_a(Helpers::$_dir_default_page_map[Helpers::dir_dosen])
            ));

            break;

        case Helpers::sso_mahasiswa:

            $_NIM = Helpers::_arr($_info, 'NIM');
            $obj_mahasiswa = CMahasiswa::_gi()->_get($_NIM, 'mahasiswa_nim');
            $obj_mahasiswa || $obj_mahasiswa = new MMahasiswa();

            $obj_mahasiswa
                ->_init_SIA($_NIM)
                ->_filter();

            if ($obj_mahasiswa->_empty()) {
                $obj_mahasiswa->setMahasiswaNim($_NIM);
                CMahasiswa::_gi()->_insert($obj_mahasiswa);
            } else CMahasiswa::_gi()->_update($obj_mahasiswa);

            Sessions::_gi()->_set(Helpers::dir_mahasiswa,
                $obj_mahasiswa->getMahasiswaNim(), $obj_mahasiswa);

            echo json_encode(array(
                'status' => true,
                'redirect' => Helpers::_a(Helpers::$_dir_default_page_map[Helpers::dir_mahasiswa])
            ));

            break;

        default:
            echo json_encode(array(
                'status' => false,
                'data' => 'Kode akses tidak dikenal.'
            ));
    }

} else echo json_encode(array(
    'status' => false,
    'data' => 'Sesi tidak valid, silakan muat ulang halaman kembali.',
));
