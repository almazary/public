<?php

function write_log($msg) {
    $logFile = __DIR__ . '/kerjoo-webhook.txt';

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
     * decode json data
     */
    $data = json_decode($postJson, 1);
    
    write_log("personnel_id: {$data['personnel_id']}");
}

echo "OK";