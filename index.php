<?php
session_start();

// Jika sudah login, arahkan sesuai role
if(isset($_SESSION['user_id'])){
    if($_SESSION['role'] === 'admin'){
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Transaksi - Pelatihan Karier Kedua</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            background:#e9ecef;
            font-family:'Poppins', sans-serif;
            overflow:hidden;
        }

        .mobile-container{
            max-width:480px;
            height:100vh;
            margin:0 auto;
            position:relative;
            overflow:hidden;

            /* Gradasi Cerdas */
            background-image:
                linear-gradient(
                    to bottom,
                    rgba(4, 20, 45, 0.85) 0%,
                    rgba(4, 20, 45, 0.50) 40%,
                    rgba(4, 20, 45, 0.05) 100%
                ),
                url('assets/img/bg-cover.png');

            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;

            display:flex;
            flex-direction:column;
            
            /* Mengangkat posisi konten, diturunkan sedikit dari sebelumnya (dari 12vh jadi 16vh) */
            justify-content:flex-start;
            padding-top: 16vh; 
            align-items:center;

            box-shadow: 0 0 35px rgba(0,0,0,0.25);
        }

        .content-wrapper{
            width:100%;
            padding:0 30px;
            text-align:center;
            z-index: 2;
        }

        .small-label{
            color:#8bb4f5; 
            font-size:0.95rem;
            letter-spacing:3px;
            text-transform:uppercase;
            margin-bottom:15px;
            font-weight:600;
            text-shadow: 1px 2px 4px rgba(0,0,0,0.8);
        }

        .title-main{
            font-size:3.5rem;
            font-weight:900;
            line-height:1.05;
            color: #ffffff;
            text-transform:uppercase;
            text-shadow: 2px 5px 10px rgba(0,0,0,0.8), 0px 0px 20px rgba(13, 110, 253, 0.6);
            margin-bottom:10px;
            padding-top:80px;
        }

        .title-main span {
            color: #ffd700; 
        }

        .subtitle{
            color:#ffffff;
            font-size:1.3rem;
            font-weight:500;
            letter-spacing:1px;
            margin-bottom:1px;
            text-shadow: 1px 2px 5px rgba(0,0,0,0.8);
        }

        .location-date{
            color:#ffffff;
            font-size:1.25rem;
            font-weight:400;
            margin-bottom:10px; /* Margin bawah dikurangi agar jarak diambil alih oleh margin-top tombol */
            text-shadow: 1px 2px 5px rgba(0,0,0,0.8);
        }

        .location-date::after{
            content:'';
            width:50px;
            height:3px;
            background:#ffd700;
            border-radius: 5px;
            display:block;
            margin:10px auto 0 auto;
            box-shadow: 1px 2px 5px rgba(0,0,0,0.5);
        }

        /* PERUBAHAN TOMBOL ADA DI SINI */
        .btn-start{
            display: inline-block;
            width: 85%; /* Tidak full 100% lagi */
            max-width: 320px; /* Batas maksimal lebar */
            border:none;
            border-radius:50px; /* Dibuat melingkar seperti kapsul (pill) */
            padding:14px 20px; /* Sedikit dirampingkan tingginya */
            font-size:1rem;
            font-weight:700;
            letter-spacing:1px;
            text-transform:uppercase;
            color:#ffffff;
            background:linear-gradient(135deg, #0d6efd, #0b5ed7, #084298);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.5);
            transition:all 0.3s ease;
            margin-top: 40px; /* Mendorong tombol lebih ke bawah, menjauh dari garis kuning */
        }

        .btn-start:hover{
            transform:translateY(-3px);
            box-shadow: 0 12px 30px rgba(13, 110, 253, 0.7);
            color:#ffffff;
        }

        .bottom-text{
            position: absolute;
            bottom: 25px;
            width: 100%;
            text-align: center;
            color:rgba(255,255,255,0.7);
            font-size:0.75rem;
            font-weight:500;
            letter-spacing: 2px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        }

        @media(max-width:480px){
            .title-main{
                font-size:3.3rem;
            }
            .content-wrapper{
                padding: 0 25px;
            }
        }
    </style>
</head>
<body>

<div class="mobile-container">

    <div class="content-wrapper">

    

        <div class="title-main">
            Simulasi<br>
            <span>Transaksi</span>
        </div>

        <div class="subtitle">
            Pelatihan Karier Kedua
        </div>

        <div class="location-date">
            Jakarta, 4 Juni 2026
        </div>

        <a href="login.php" class="btn btn-start">
            Mulai Masuk
            <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>

    </div>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>