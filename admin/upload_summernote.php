<?php
require_once '../config/koneksi.php';

if(isset($_FILES['file'])) {

    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    $nama_file = time() . '_' . uniqid() . '.' . $ext;

    $target = '../assets/img/summernote/' . $nama_file;

    if(move_uploaded_file($_FILES['file']['tmp_name'], $target)) {

        echo '../assets/img/summernote/' . $nama_file;

    }

}