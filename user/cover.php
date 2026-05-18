<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$q = mysqli_query($conn,"
    SELECT active_period
    FROM system_settings
    LIMIT 1
");

$s = mysqli_fetch_assoc($q);

$periode = $s['active_period'] ?? 1;

// simpan bahwa cover sudah dilihat
mysqli_query($conn,"
    INSERT INTO period_cover_views(user_id,period)
    VALUES('$user_id','$periode')
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Simulasi Transaksi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#f4f6f9;
    font-family:'Poppins', sans-serif;
    overflow:hidden;
}

/* container hp */
.mobile-container{

    max-width:480px;

    margin:auto;

    min-height:100vh;

    position:relative;

    overflow:hidden;

    background:#ffffff;

    box-shadow:
    0 0 35px rgba(0,0,0,0.15);
}

/* gambar background */
.bg-cover{

    position:absolute;

    inset:0;

    width:100%;

    height:100%;

    object-fit:cover;

    filter:
    brightness(1.08)
    contrast(1.02);
}

/* overlay putih terang */
.overlay{

    position:absolute;

    inset:0;

    background:
    linear-gradient(
        to bottom,
        rgba(255,255,255,0.05),
        rgba(255,255,255,0.12),
        rgba(255,255,255,0.35),
        rgba(255,255,255,0.55)
    );
}

/* konten */
.content{

    position:relative;

    z-index:2;

    min-height:100vh;

    display:flex;

    flex-direction:column;

    justify-content:flex-end;

    align-items:center;

    padding:40px 25px;

    text-align:center;
}

/* tombol */
.btn-start{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:10px;

    width:auto;

    min-width:260px;

    border:none;

    border-radius:999px;

    padding:16px 28px;

    font-size:0.95rem;

    font-weight:700;

    letter-spacing:1px;

    color:#ffffff;

    text-decoration:none;

    background:rgba(13,110,253,0.78);

    backdrop-filter:blur(12px);

    -webkit-backdrop-filter:blur(12px);

    border:1px solid rgba(255,255,255,0.25);

    box-shadow:
    0 10px 30px rgba(13,110,253,0.35),
    0 0 20px rgba(13,110,253,0.25);

    animation:floating 2.8s ease-in-out infinite;

    transition:all .3s ease;
}

.btn-start:hover{

    transform:translateY(-4px) scale(1.02);

    color:#fff;

    box-shadow:
    0 15px 40px rgba(13,110,253,0.5),
    0 0 30px rgba(13,110,253,0.35);
}

.btn-start i{

    animation:arrowMove 1.2s infinite;
}

@keyframes floating{

    0%{
        transform:translateY(0px);
    }

    50%{
        transform:translateY(-8px);
    }

    100%{
        transform:translateY(0px);
    }
}

@keyframes arrowMove{

    0%{
        transform:translateX(0px);
    }

    50%{
        transform:translateX(5px);
    }

    100%{
        transform:translateX(0px);
    }
}

/* efek glow bawah */
.bottom-glow{

    position:absolute;

    bottom:-120px;

    left:50%;

    transform:translateX(-50%);

    width:420px;

    height:220px;

    background:
    radial-gradient(
        circle,
        rgba(255,255,255,0.85),
        rgba(255,255,255,0)
    );

    z-index:1;
}

</style>
</head>

<body>

<div class="mobile-container">

    <!-- GANTI dengan nama gambar kamu -->
    <img
        src="../assets/img/crystal-ball.png"
        class="bg-cover"
    >

    <div class="overlay"></div>

    <div class="bottom-glow"></div>

    <div class="content">

        <a
            href="dashboard.php"
            class="btn-start"
        >
            Tap untuk Melihat
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</div>

</body>
</html>