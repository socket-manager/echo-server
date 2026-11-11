<?php
/**
 * メイン処理クラスのファイル
 * 
 * PureUdpManagerの実行
 */

namespace App\MainClass;

use SocketManager\Library\PureUdpManager;
use SocketManager\Library\FrameWork\Console;

use App\InitClass\InitPureUdpEchoServer;
use App\ProtocolUnits\ProtocolPureUdpEchoServer;


/**
 * メイン処理クラス
 * 
 * PureUdpManagerの初期化と実行
 */
class PureUdpEchoServer extends Console
{
    /**
     * @var string $identifer サーバー識別子
     */
    protected string $identifer = 'app:pure-udp-echo-server {max_concurrent_connections?} {port?}';

    /**
     * @var string $description コマンド説明
     */
    protected string $description = '純粋 UDP エコーサーバー';

    /**
     * @var string $host ホスト名（リッスン用）
     */
    private string $host = '127.0.0.1';

    /**
     * @var int $port ポート番号（リッスン用）
     */
    private int $port = 10000;

    /**
     * @var int $cycle_interval 周期インターバル時間（μs）
     */
    private int $cycle_interval = 10;

    /**
     * @var int $alive_interval アライブチェックタイムアウト時間（s）
     */
    private int $alive_interval = 3600;

    /**
     * @var array $os_buff_size OSバッファサイズ
     */
    private array $os_buff_size = ['send' => 131072, 'recv' => 262144];

    // 内部受信バッファサイズ
    private int $internal_recv_size = 2048;

    // パケット件数上限
    private int $internal_max_packets = 64;


    /**
     * サーバー起動
     * 
     */
    public function exec()
    {
        //--------------------------------------------------------------------------
        // 設定値の反映
        //--------------------------------------------------------------------------

        // ホスト名の設定
        $this->host = config('pure_udp.host', $this->host);

        // ポート番号の設定
        $this->port = config('pure_udp.port', $this->port);

        // 周期インターバルの設定
        $this->cycle_interval = config('pure_udp.cycle_interval', $this->cycle_interval);

        // アライブチェックタイムアウト時間の設定
        $this->alive_interval = config('pure_udp.alive_interval', $this->alive_interval);

        // OSバッファサイズ
        $this->os_buff_size = config('pure_udp.os_buff_size', $this->os_buff_size);

        // 内部受信バッファサイズ
        $this->internal_recv_size = config('pure_udp.internal_recv_size', $this->internal_recv_size);

        // パケット件数上限
        $this->internal_max_packets = config('pure_udp.internal_max_packets', $this->internal_max_packets);

        //--------------------------------------------------------------------------
        // 引数の反映
        //--------------------------------------------------------------------------

        // 接続制限数の取得
        $max_concurrent_connections = $this->getParameter('max_concurrent_connections');

        // ポート番号の取得
        $port = $this->getParameter('port');
        if($port !== null)
        {
            $this->port = $port;
        }

        //--------------------------------------------------------------------------
        // PureUdpManagerの初期化
        //--------------------------------------------------------------------------

        /** @var PureUdpManager $manager 純粋UDPマネージャーのインスタンス設定 */
        $manager = new PureUdpManager(
            $this->host,
            $this->port,
            $max_concurrent_connections,
            $this->os_buff_size['send'],
            $this->os_buff_size['recv'],
            $this->internal_recv_size,
            $this->internal_max_packets
        );

        /**
         * 初期化クラスの設定
         * 
         */
        $init = new InitPureUdpEchoServer($this->port);
        $manager->initManager($init);

        /**
         * プロトコルUNITの設定
         * 
         */
        $broadcast = config('pure_udp.broadcast', false);
        $protocol = new ProtocolPureUdpEchoServer($broadcast);
        $manager->setProtocolUnits($protocol);

        /**
         * コマンドUNITの設定
         * 
         * $manager->setCommandUnits()メソッドでコマンドUNITクラスを設定します
         */

        /***********************************************************************
         * ソケットマネージャーの実行
         * 
         * ポートの待ち受け処理や周期ドリブン処理を実行します
         **********************************************************************/

        // 待ち受けポートで待ち受ける
        $ret = $manager->listen();
        if($ret === false)
        {
            goto finish;   // リッスン失敗
        }

        // ノンブロッキングループ
        while(true)
        {
            // 周期ドリブン
            $ret = $manager->cycleDriven($this->cycle_interval, $this->alive_interval);
            if($ret === false)
            {
                goto finish;
            }
        }

finish:
        // 全接続クローズ
        $manager->shutdownAll();
    }
}
