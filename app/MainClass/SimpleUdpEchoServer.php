<?php
/**
 * メイン処理クラスのファイル
 * 
 * シンプルソケットの実行
 */

namespace App\MainClass;

use SocketManager\Library\ISimpleSocketUdp;
use SocketManager\Library\SimpleSocketGenerator;
use SocketManager\Library\SimpleSocketTypeEnum;
use SocketManager\Library\FrameWork\Console;


/**
 * メイン処理クラス
 * 
 * シンプルソケットの初期化と実行
 */
class SimpleUdpEchoServer extends Console
{
    /**
     * @var string $identifer アプリケーション識別子
     */
    protected string $identifer = 'app:simple-udp-echo-server {port?}';

    /**
     * @var string $description コマンド説明
     */
    protected string $description = 'シンプルソケット UDP エコーサーバー';

    /**
     * @var string $host ホスト名（待ち受け用）
     */
    private string $host = '127.0.0.1';

    /**
     * @var int $port ポート番号（待ち受け用）
     */
    private int $port = 10000;

    /**
     * @var int $cycle_interval 周期インターバル時間（μs）
     */
    private int $cycle_interval = 10;


    /**
     * アプリケーション起動
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

        //--------------------------------------------------------------------------
        // 引数の反映
        //--------------------------------------------------------------------------

        // ポート番号の取得
        $port = $this->getParameter('port');
        if($port !== null)
        {
            $this->port = $port;
        }

        /***********************************************************************
         * シンプルソケットジェネレータの初期設定
         * 
         * ジェネレータインスタンスの生成や各種設定をここで実行します
         **********************************************************************/
        $generator = new SimpleSocketGenerator(SimpleSocketTypeEnum::UDP, $this->host, $this->port);

        /**
         * ログライターの登録（任意）
         * 
         * ログライターが使いたい場合に$generator->setLogWriter()メソッドで登録します
         * SocketManager初期化クラスのログライターをそのままお使い頂けます
         */
        $generator->setLogWriter
        (
            /**
             * ログライター
             * 
             * @param string $p_level ログレベル（debug、info、notice、warning、errorなど）
             * @param array $p_param 連想配列形式のログ内容
             * @return void
             */
            function(string $p_level, array $p_param): void
            {
                $filename = date('Ymd');
                $now = date('Y-m-d H:i:s');
                $log = $now." {$p_level} ".print_r($p_param, true)."\n";
                error_log($log, 3, "./logs/socket-manager/{$filename}_SIMPLE_UDP_S.log");
            }
        );

        /**
         * SocketManagerとの連携（任意）
         * 
         * UNITパラメータインスタンスの"simple_socket"プロパティにシンプルソケットインスタンスが設定され
         * コマンドディスパッチャーやステータスUNIT内で使えるようになります
         * 
         * $generator->setUnitParameter()メソッドでUNITパラメータクラスを設定します
         */

        /**
         * 常時実行処理の登録（任意）
         * 
         * 常時実行処理がある場合に$generator->setKeepRunning()メソッドで登録します
         */
        $generator->setKeepRunning
        (
            /**
             * 常時実行処理
             * 
             * @param ISimpleSocketUdp $p_simple_socket シンプルソケットインスタンス
             * @param mixed[] $p_argv 可変引数（setKeepRunningメソッドの第二引数以降のものが渡される）
             * @return void
             */
            function(ISimpleSocketUdp $p_simple_socket): void
            {
                $addr = '';
                $port = 0;
                $dat = $p_simple_socket->recvfrom($addr, $port);
                if($dat === null)
                {
                    return;
                }
                $p_simple_socket->sendto($addr, $port, $dat);
            }
        );

        /**
         * シンプルソケットインスタンスの生成
         * 
         * この手続きが行われた時点でインスタンスが生成され有効になります
         */
        $w_ret = $generator->generate();
        if($w_ret === null)
        {
            goto finish;
        }

        /***********************************************************************
         * シンプルソケットの実行
         * 
         * 周期ドリブン処理を実行します
         **********************************************************************/

        // ノンブロッキングループ
        while(true)
        {
            // 周期ドリブン
            $ret = $generator->cycleDriven($this->cycle_interval);
            if($ret === false)
            {
                goto finish;
            }
        }

finish:
        $generator->shutdownAll();
        return;
    }
}
