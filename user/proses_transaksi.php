    <?php
    session_start();
    require_once '../config/koneksi.php';

    // ======================================
    // VALIDASI AKSES
    // ======================================

    if (
        !isset($_SESSION['user_id']) ||
        $_SESSION['role'] !== 'peserta' ||
        $_SERVER['REQUEST_METHOD'] != 'POST'
    ) {
        header("Location: dashboard.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];

    $asset_id = (int)$_POST['asset_id'];

    $tipe = $_POST['tipe'];

    $nominal = floatval($_POST['nominal']);

    if ($nominal <= 0) {

        echo "
        <script>
            alert('Nominal transaksi tidak valid');
            window.location.href='dashboard.php';
        </script>
        ";

        exit;
    }


    // ======================================
    // AMBIL SETTING SISTEM
    // ======================================

    $q_set = mysqli_query($conn, "
        SELECT active_period, period_status
        FROM system_settings
        LIMIT 1
    ");

    $setting = mysqli_fetch_assoc($q_set);

    $periode_aktif = (int)$setting['active_period'];

    if ($setting['period_status'] == 'closed') {

        echo "
        <script>
            alert('Market periode ini sudah ditutup');
            window.location.href='dashboard.php';
        </script>
        ";

        exit;
    }


    // ======================================
    // AMBIL DATA ASET
    // ======================================

    $kolom_val = "value_p" . $periode_aktif;

    $kolom_laba = "laba_p" . $periode_aktif;

    $q_asset = mysqli_query($conn, "
        SELECT
            id,
            nama_aset,
            tipe_simulasi,
            multiplier,
            $kolom_val AS val_now,
            $kolom_laba AS bunga_now
        FROM market_assets
        WHERE id = '$asset_id'
    ");

    $asset = mysqli_fetch_assoc($q_asset);

    if (!$asset) {

        echo "
        <script>
            alert('Aset tidak ditemukan');
            window.location.href='dashboard.php';
        </script>
        ";

        exit;
    }

    $nama_aset = $asset['nama_aset'];

    $tipe_simulasi = $asset['tipe_simulasi'];

    $val_now = floatval($asset['val_now']);

    $bunga_now = floatval($asset['bunga_now']);

    $multiplier = floatval($asset['multiplier']);


    // ======================================
    // HITUNG QTY
    // ======================================
        if (
            $tipe_simulasi == 'persentase'
        )
        {

            $qty=$nominal;

        }
        elseif(
            $tipe_simulasi=='edukasi'
        )
        {

            $qty=1;

        }
        else
        {

            if ($val_now <= 0)
            {

                echo "
                <script>

                alert('Harga aset tidak valid');

                location='dashboard.php';

                </script>
                ";

                exit;

            }

            $qty=
            round(
                $nominal/$val_now,
                4
            );

        }


    // ======================================
    // AMBIL SALDO USER
    // ======================================

    $q_user = mysqli_query($conn, "
        SELECT balance
        FROM users
        WHERE id = '$user_id'
    ");

    $user = mysqli_fetch_assoc($q_user);

    $saldo_sekarang = floatval($user['balance']);


    // =====================================================
    // BUY
    // =====================================================

    if ($tipe == 'buy') {

        // ======================================
        // VALIDASI SALDO
        // ======================================

        // KHUSUS EDUKASI

    if(
        $tipe_simulasi=='edukasi'
    )
    {

    if($periode_aktif>=3)
    {

        echo "

        <script>

        alert('Periode edukasi telah berakhir');

        location='dashboard.php';

        </script>

        ";

        exit;

    }

    $cek_edukasi=mysqli_query(
        $conn,
        "
        SELECT id
        FROM transactions
        WHERE user_id='$user_id'
        AND asset_id='$asset_id'
        AND type='buy'
        AND is_active=1
        LIMIT 1
        "
    );

    if(
        mysqli_num_rows(
            $cek_edukasi
        )>0
    )
    {

        echo "

        <script>

        alert('Anda sudah memiliki pendidikan ini');

        location='portfolio.php';

        </script>

        ";

        exit;

    }

}

        if ($saldo_sekarang < $nominal) {

            echo "
            <script>
                alert('Saldo tidak mencukupi');
                window.location.href='dashboard.php';
            </script>
            ";

            exit;
        }

        // ======================================
        // UPDATE SALDO
        // ======================================

        $saldo_baru = $saldo_sekarang - $nominal;

        mysqli_query($conn, "
            UPDATE users
            SET balance = '$saldo_baru'
            WHERE id = '$user_id'
        ");


        // ======================================
        // KHUSUS DEPOSITO
        // ======================================

        $maturity_period = NULL;

        if ($tipe_simulasi == 'persentase') {

            $maturity_period = $periode_aktif + 1;
        }


        // ======================================
        // INSERT TRANSAKSI BUY
        // ======================================

        $buy_price = $val_now;

        $buy_period = $periode_aktif;

        mysqli_query($conn, "
            INSERT INTO transactions
            (
                user_id,
                asset_id,
                period,
                type,
                amount_money,
                qty,
                buy_price,
                buy_period,
                maturity_period,
                locked_return_rate
            )
            VALUES
            (
                '$user_id',
                '$asset_id',
                '$periode_aktif',
                'buy',
                '$nominal',
                '$qty',
                '$buy_price',
                '$buy_period',
                " . ($maturity_period === NULL ? "NULL" : "'$maturity_period'") . ",
                '$bunga_now'
            )
        ");


        // ======================================
        // SESSION NOTIF
        // ======================================

        $_SESSION['trade_success'] = [

            'type' => 'buy',

            'aset' => $nama_aset,

            'nominal' => $nominal

        ];


    echo '

    <!DOCTYPE html>
    <html>
    <head>

    <meta charset="UTF-8">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    <body>

    <script>

    Swal.fire({

        icon: "success",

        title: "Pembelian Berhasil",

        html: `

            <div style="text-align:left">

                <p>
                    Anda berhasil membeli:
                </p>

                <hr>

                <table style="width:100%">

                    <tr>
                        <td>Instrumen</td>

                        <td align="right">
                            <b>'.htmlspecialchars($nama_aset).'</b>
                        </td>
                    </tr>

                    <tr>
                        <td>Nominal</td>

                        <td align="right">
                            <b>
                                Rp '.number_format($nominal,0,",",".").'
                            </b>
                        </td>
                    </tr>

                </table>

            </div>

        `,

        confirmButtonText: "OK",

        allowOutsideClick: false

    }).then(() => {

        window.location.href = "portfolio.php";

    });

    </script>

    </body>
    </html>

    ';

    exit;
    }



    // =====================================================
    // SELL
    // =====================================================

    elseif ($tipe == 'sell') {

        // ======================================
        // HITUNG KEPEMILIKAN
        // ======================================

        $q_portfolio = mysqli_query($conn, "
            SELECT
                SUM(
                    CASE
                        WHEN type='buy' THEN qty
                        ELSE -qty
                    END
                ) AS total_qty
            FROM transactions
            WHERE user_id = '$user_id'
            AND asset_id = '$asset_id'
        ");

        $portfolio = mysqli_fetch_assoc($q_portfolio);

        $qty_dimiliki = floatval($portfolio['total_qty']);


        // ======================================
        // VALIDASI UNIT
        // ======================================

        if ($qty_dimiliki < ($qty - 0.0001)) {

            echo "
            <script>
                alert('Unit aset tidak mencukupi');
                window.location.href='portfolio.php';
            </script>
            ";

            exit;
        }


        // ======================================
        // HITUNG AVG BUY PRICE
        // ======================================

        $q_avg = mysqli_query($conn, "
            SELECT

                SUM(
                    CASE
                        WHEN type='buy'
                        THEN qty * buy_price
                        ELSE 0
                    END
                ) /

                NULLIF(

                    SUM(
                        CASE
                            WHEN type='buy'
                            THEN qty
                            ELSE 0
                        END
                    ),

                    0

                ) AS avg_buy_price

            FROM transactions

            WHERE user_id = '$user_id'
            AND asset_id = '$asset_id'
        ");

        $data_avg = mysqli_fetch_assoc($q_avg);

        $avg_buy_price =
            floatval($data_avg['avg_buy_price']);

// ======================================
        // HITUNG REALIZED PROFIT
        // ======================================

        $sell_price = $val_now;
        
        // KUNCI PENTING: Matikan bonus laba di sini agar tidak dobel.
        // Laba/Bagi Hasil bisnis HANYA diberikan melalui Pop-Up (proses_bisnis.php)
        $bonus_laba = 0; 

        if ($tipe_simulasi == 'persentase') {
            
            // LOGIKA DEPOSITO / REKSADANA
            $modal_asli = $nominal; 
            $hasil_penjualan = $nominal; 
            $realized_profit = 0;

        } else {
            
            // LOGIKA SAHAM, EMAS, BISNIS, PROPERTI
            // Murni hanya menghitung Capital Gain (Selisih Harga Jual dan Modal Beli Rata-rata)
            $modal_asli = $qty * $avg_buy_price;
            $hasil_penjualan = $qty * $sell_price;
            $realized_profit = $hasil_penjualan - $modal_asli;
            
        }


        // ======================================
        // UPDATE SALDO
        // ======================================

        $saldo_baru =
        $saldo_sekarang + $hasil_penjualan; 

        mysqli_query($conn, "
            UPDATE users
            SET balance = '$saldo_baru'
            WHERE id = '$user_id'
        ");


        // ======================================
        // INSERT TRANSAKSI SELL
        // ======================================

        mysqli_query($conn, "
            INSERT INTO transactions
            (
                user_id,
                asset_id,
                period,
                type,
                amount_money,
                realized_profit,
                qty,
                buy_price,
                sell_price,
                buy_period
            )
            VALUES
            (
                '$user_id',
                '$asset_id',
                '$periode_aktif',
                'sell',
                '$hasil_penjualan',
                '$realized_profit',
                '$qty',
                '0',
                '$sell_price',
                '$periode_aktif'
            )
        ");


        // ======================================
        // SESSION NOTIF
        // ======================================

        $_SESSION['trade_success'] = [

            'type' => 'sell',

            'aset' => $nama_aset,

            'nominal' => $nominal

        ];


    echo '

    <!DOCTYPE html>
    <html>
    <head>

    <meta charset="UTF-8">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    <body>

    <script>

    Swal.fire({

        icon: "success",

        title: "Penjualan Berhasil",

        html: `

            <div style="text-align:left">

                <p>
                    Anda berhasil menjual:
                </p>

                <hr>

                <table style="width:100%">

                    <tr>
                        <td>Instrumen</td>

                        <td align="right">
                            <b>'.htmlspecialchars($nama_aset).'</b>
                        </td>
                    </tr>

                    <tr>
                        <td>Nominal</td>

                        <td align="right">
                            <b>
                                Rp '.number_format($nominal,0,",",".").'
                            </b>
                        </td>
                    </tr>
                    '.($bonus_laba > 0 ? '

<tr>
    <td>Laba Bisnis</td>

    <td align="right">
        <b style=\"color:green;\">
            + Rp '.number_format($bonus_laba,0,",",".").'
        </b>
    </td>
</tr>

' : '').'

                    <tr>
                        <td>Realized Profit</td>

                        <td align="right">
                            <b style=\"color: '.($realized_profit >= 0 ? 'green' : 'red').';\">
                                '.($realized_profit >= 0 ? '+' : '-').'
                                Rp '.number_format(abs($realized_profit),0,",",".").'
                            </b>
                        </td>
                    </tr>

                </table>

            </div>

        `,

        confirmButtonText: "OK",

        allowOutsideClick: false

    }).then(() => {

        window.location.href = "portfolio.php";

    });

    </script>

    </body>
    </html>

    ';

        exit;
    }