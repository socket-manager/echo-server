<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * モジュール管理クラスのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\PureUdp\SocketManagerParameter;

use App\ProtocolUnits\ProtocolPureUdpEchoClientStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolPureUdpEchoClient implements IEntryUnits
{
    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::CONNECT->value,	// 接続を処理するキュー
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
    ];

    /**
     * @var bool 送信フラグ
     */
    private bool $send = false;

    /**
     * @var string 送信データ
     */
    private string $send_data;

    /**
     * @var int 送信データサイズ
     */
    private int $send_data_size;

    /**
     * コンストラクタ
     * 
     * @param bool $p_send 送信フラグ
     */
    public function __construct(bool $p_send = false)
    {
        $this->send = $p_send;
        $this->send_data = config('pure_udp.send_data', 'default data');
        $this->send_data_size = strlen($this->send_data);
    }

    /**
     * キューリストの取得
     * 
     * @return array キュー名のリスト
     */
    public function getQueueList(): array
    {
        return (array)static::QUEUE_LIST;
    }

    /**
     * ステータスUNITリストの取得
     * 
     * @param string $p_que キュー名
     * @return array キュー名に対応するUNITリスト
     */
    public function getUnitList(string $p_que): array
    {
        $ret = [];

        if($p_que === ProtocolQueueEnum::CONNECT->value)
        {
            $ret[] = [
                'status' => ProtocolPureUdpEchoClientStatusEnum::START->value,
                'unit' => $this->connectStart(...)
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolPureUdpEchoClientStatusEnum::START->value,
                'unit' => $this->recvStart(...)
            ];
        }

        return $ret;
    }


    /**
     * 以降はステータスUNITの定義（"CONNECT"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：接続開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function connectStart(SocketManagerParameter $p_param): ?string
    {
        if(!$this->send)
        {
            return null;
        }
        $size = $p_param->protocol()->sendto($this->send_data);
        if($size === null)
        {
            return ProtocolPureUdpEchoClientStatusEnum::START->value;
        }
        if($size < $this->send_data_size)
        {
            $p_param->emergencyShutdown();
        }

        return null;
    }


    /**
     * 以降はステータスUNITの定義（"RECV"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：受信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function recvStart(SocketManagerParameter $p_param): ?string
    {
        $data = '';
        $host = $port = null;
        $size = $p_param->protocol()->recvfrom($data, $host, $port);
        if($size === null)
        {
            return ProtocolPureUdpEchoClientStatusEnum::START->value;
        }

        $cid = $p_param->getConnectionId();
        $pid = getmypid();
        $p_param->logWriter('debug', ['receive data' => "payload[{$data}] cid[{$cid}]pid[{$pid}]"]);

        return null;
    }
}
