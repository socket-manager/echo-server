<?php

return [

    /**
     * @var string ホスト名
     */
    'host' => '127.0.0.1',

    /**
     * @var int ポート番号
     */
    'port' => 10000,

    /**
     * @var int 周期インターバル時間（μs）
     */
    'cycle_interval' => 10,

    /**
     * @var int アライブチェックタイムアウト時間（s）
     */
    'alive_interval' => 3600,

    /**
     * @var array OS送受信バッファサイズ
     */
    'os_buff_size' => [
        /** @var int OS送信バッファ */
        'send' => 4 * 1024 * 1024,   // 128K
        /** @var int OS受信バッファ */
        'recv' => 4 * 1024 * 1024    // 256K
    ],

    /**
     * @var int 内部受信バッファサイズ
     */
    'internal_recv_size' => 2048, // 2K（MTU + α）

    /**
     * @var int パケット件数上限
     */
    'internal_max_packets' => 64,

    /**
     * @var string 送信データ
     */
    'send_data' => 'test data',

    /**
     * @var bool ブロードキャストフラグ
     */
    'broadcast' => false,
];
