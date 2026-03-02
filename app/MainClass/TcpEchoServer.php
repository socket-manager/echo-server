<?php
/**
 * メイン処理クラスのファイル
 * 
 * SocketManagerの実行
 */

namespace App\MainClass;

use SocketManager\Library\SocketManager;
use SocketManager\Library\FrameWork\Console;

use App\InitClass\InitTcpEchoServer;
use App\ProtocolUnits\ProtocolEchoServer;


/**
 * メイン処理クラス
 * 
 * SocketManagerの初期化と実行
 */
class TcpEchoServer extends Console
{
    /**
     * @var string $identifer サーバー識別子
     */
    protected string $identifer = 'app:tcp-echo-server {port?}';

    /**
     * @var string $description コマンド説明
     */
    protected string $description = 'TCP エコーサーバー';

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
     * サーバー起動
     * 
     */
    public function exec()
    {
        //--------------------------------------------------------------------------
        // 設定値の反映
        //--------------------------------------------------------------------------

        // ホスト名の設定
        $this->host = config('const.host', $this->host);

        // ポート番号の設定
        $this->port = config('const.port', $this->port);

        // 周期インターバルの設定
        $this->cycle_interval = config('const.cycle_interval', $this->cycle_interval);

        // アライブチェックタイムアウト時間の設定
        $this->alive_interval = config('const.alive_interval', $this->alive_interval);

        //--------------------------------------------------------------------------
        // 引数の反映
        //--------------------------------------------------------------------------

        // ポート番号の取得
        $port = $this->getParameter('port');
        if($port !== null)
        {
            $this->port = $port;
        }

        //--------------------------------------------------------------------------
        // SocketManagerの初期化
        //--------------------------------------------------------------------------

        // ソケットマネージャーのインスタンス設定
        $manager = new SocketManager($this->host, $this->port);

        /**
         * 初期化クラスの設定
         * 
         */
        $init = new InitTcpEchoServer();
        $manager->setInitSocketManager($init);

        /**
         * プロトコルUNITの設定
         * 
         */
        $broadcast = config('const.broadcast_tcp', false);
        $protocol = new ProtocolEchoServer($broadcast);
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

        // リッスンポートで待ち受ける
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
