<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * モジュール管理クラスのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\AffinedUdp\SocketManagerParameter;

use App\ProtocolUnits\ProtocolAffinedUdpEchoClientStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolAffinedUdpEchoClient implements IEntryUnits
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
        $this->send_data = config('affined_udp.send_data', 'default data');
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
                'status' => ProtocolAffinedUdpEchoClientStatusEnum::START->value,
                'unit' => $this->connectStart(...)
            ];
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoClientStatusEnum::SENDING->value,
                'unit' => $this->connectSending(...)
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoClientStatusEnum::START->value,
                'unit' => $this->recvStart(...)
            ];
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoClientStatusEnum::RECEIVING->value,
                'unit' => $this->recvReceiving(...)
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
        $p_param->protocol()->setSendingData($this->send_data);
        $sta = $this->connectSending($p_param);

        return $sta;
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信中
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function connectSending(SocketManagerParameter $p_param): ?string
    {
        $ret = $p_param->protocol()->sending();
        if($ret === true)
        {
            return null;
        }

        return ProtocolAffinedUdpEchoClientStatusEnum::SENDING->value;
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
        $p_param->protocol()->setReceivingSize($this->send_data_size);
        $sta = $this->recvReceiving($p_param);

        return $sta;
    }

    /**
     * ステータス名： RECEIVING
     * 
     * 処理名：受信中
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function recvReceiving(SocketManagerParameter $p_param): ?string
    {
        $dat = $p_param->protocol()->receiving();
        if($dat === null)
        {
            return ProtocolAffinedUdpEchoClientStatusEnum::RECEIVING->value;;
        }
        $cid = $p_param->getConnectionId();
        $pid = getmypid();
        $p_param->logWriter('debug', ['receive data' => "payload[{$dat}] cid[{$cid}]pid[{$pid}]"]);

        return null;
    }
}
