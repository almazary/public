<?php

/**
 * isi dengan api token Anda
 */
define('WAWEBHOOK_API_TOKEN', '');

write_log('Ada panggilan masuk: ' . date('Y-m-d H:i:s'));

function write_log($msg) {
    $logFile = __DIR__ . '/log.txt';

    /**
     * create file jika belum ada
     */
    if (! file_exists($logFile)) {
        fopen($logFile, 'w') or exit('Tidak dapat membuat file log.');
    }

    /**
     * cek bisa ditulis gak?
     */
    if (! is_writable($logFile)) {
        throw new Exception("ERROR: file log tidak dapat ditulis.");
    }

    $fopen = fopen($logFile, 'a');
    fwrite($fopen, $msg . PHP_EOL);
    fclose($fopen);
}

function send_msg($postField = [], $type = null) {
    $apiUrl = 'https://wawebhook.mediadidik.com/api/send';
    if ($type == 'media') {
        $apiUrl .= '-media';
    }
    if ($type == 'location') {
        $apiUrl .= '-location';
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postField,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . WAWEBHOOK_API_TOKEN
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    write_log($response);
}

/**
 * tangkap post data
 */
$postJson = file_get_contents('php://input');

/**
 * jika ada isinya
 */
if (! empty($postJson)) {
    /**
     * log semua data post
     */
    write_log($postJson);

    /**
     * decode ke array
     */
    $post = json_decode($postJson, 1);

    /**
     * disini anda dapat melalukan aksi apapun.
     * contohnya seperti berikut:
     */

    /**
     * cek isi pesan, jika !ping, balas dengan pong
     */
    if ($post['body'] == '!ping') {
        /**
         * kirim balasan pong
         */
        send_msg([
            'from' => str_replace('@c.us', '', $post['to']),
            'to' => str_replace('@c.us', '', $post['from']),
            'content' => 'PONG',
        ]);
    }

    /**
     * jika isi pesan !reply, maka reply pesannya
     */
    if ($post['body'] == '!reply') {
        /**
         * kirim balasan reply
         */
        send_msg([
            'from' => str_replace('@c.us', '', $post['to']),
            'to' => str_replace('@c.us', '', $post['from']),
            'content' => 'ya kenapa?',
            'quoted_message_id' => $post['id']['_serialized'],
        ]);
    }

    /**
     * test kirim lokasi
     */
    if ($post['body'] == '!location') {
        /**
         * kirim balasan reply
         */
        send_msg([
            'from' => str_replace('@c.us', '', $post['to']),
            'to' => str_replace('@c.us', '', $post['from']),
            'latitude' => '-2.2616388782216417',
            'longitude' => '113.93617337714733',
            'description' => 'Lokasi saya',
        ], 'location');
    }

    /**
     * jika pesan yang masuk adalah media/file
     */
    if ($post['hasMedia']) {
        /**
         * ambil informasi file pada index media, yang berisi:
         * - base64_encoded_url
         * - mimetype
         * - extension
         * - filename
         */

        /**
         * simpan file
         */
        $fileName = time() . '.' . $post['media']['extension'];

        /**
         * download content file dari url base64_encoded_url, dan write dilocal file
         */
        file_put_contents($fileName, base64_decode(file_get_contents($post['media']['base64_encoded_url'])));

        /**
         * coba kirimkan kembali
         */
        send_msg([
            'from' => str_replace('@c.us', '', $post['to']),
            'to' => str_replace('@c.us', '', $post['from']),
            'content' => 'Anda tadi mengirimkan file ini:',
        ]);

        send_msg([
            'from' => str_replace('@c.us', '', $post['to']),
            'to' => str_replace('@c.us', '', $post['from']),
            'caption' => 'ini adalah captionnya', //hanya jika gambar/video
            'file' => new CURLFile(__DIR__ . '/' . $fileName, $post['media']['mimetype'], $post['media']['filename'] ?? null),
        ], 'media');
    }

    echo "Success Handled...";
}
