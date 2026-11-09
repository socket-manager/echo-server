<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * PureUdpManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\PureUdp\SocketManagerParameter;

use App\ProtocolUnits\ProtocolPureUdpEchoServerStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolPureUdpEchoServer implements IEntryUnits
{
    /**
     * @var array QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::RECV->value,	    // 受信処理のキュー
        ProtocolQueueEnum::SEND->value	    // 送信処理のキュー
    ];

    /**
     * @var bool ブロードキャストフラグ
     */
    private bool $broadcast = false;

    /**
     * コンストラクタ
     * 
     * @param bool $p_broadcast ブロードキャストフラグ
     */
    public function __construct(bool $p_broadcast = false)
    {
        $this->broadcast = $p_broadcast;
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

        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolPureUdpEchoServerStatusEnum::START->value,
                'unit' => $this->recvReceiving(...)
            ];
            $ret[] = [
                'status' => ProtocolPureUdpEchoServerStatusEnum::SENDING->value,
                'unit' => $this->reuseSending(...)
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolPureUdpEchoServerStatusEnum::START->value,
                'unit' => $this->sendPrepare(...)
            ];
            $ret[] = [
                'status' => ProtocolPureUdpEchoServerStatusEnum::SENDING->value,
                'unit' => $this->reuseSending(...)
            ];
        }

        return $ret;
    }


    /**
     * 以降はステータスUNITの定義（"RECV"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：受信
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function recvReceiving(SocketManagerParameter $p_param): ?string
    {
        $data = '';
        $host = $port = null;
        $size = $p_param->protocol()->recvfrom($data, $host, $port);
        if($size === null)
        {
            return null;
        }
        $tmp = [
            'data' => $data,
            'size' => $size
        ];
        $p_param->setTempBuff(['send_data' => $tmp]);
        if($this->broadcast)
        {
            $p_param->setSendStackAll($tmp);
            return null;
        }
        return $this->reuseSending($p_param);
    }


    /**
     * 以降はステータスUNITの定義（"SEND"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：送信準備
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function sendPrepare(SocketManagerParameter $p_param): ?string
    {
        $dat = $p_param->protocol()->getSendData();
        if($dat === null)
        {
            return null;
        }

        $p_param->setTempBuff(['send_data' => $dat]);

        return ProtocolPureUdpEchoServerStatusEnum::SENDING->value;
    }


    /**
     * 以降はステータスUNITの定義（再利用UNIT）
     */

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信中の処理
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function reuseSending(SocketManagerParameter $p_param): ?string
    {
        $tmp = $p_param->getTempBuff(['send_data'])['send_data'];
        $size = $p_param->protocol()->sendto($tmp['data']);
        if($size === null)
        {
            return ProtocolPureUdpEchoServerStatusEnum::SENDING->value;
        }
        if($size < $tmp['size'])
        {
            $p_param->emergencyShutdown();
        }

        return null;
    }

}
