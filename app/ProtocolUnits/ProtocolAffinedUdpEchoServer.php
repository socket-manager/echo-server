<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\AffinedUdp\SocketManagerParameter;

use App\ProtocolUnits\ProtocolAffinedUdpEchoServerStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolAffinedUdpEchoServer implements IEntryUnits
{
    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::RECV->value,	    // 受信処理のキュー
        ProtocolQueueEnum::SEND->value	    // 送信処理のキュー
    ];

    /**
     * @var int ペイロード長
     */
    private int $payload_len = 0;

    /**
     * @var bool ブロードキャストフラグ
     */
    private bool $broadcast = false;

    /**
     * コンストラクタ
     * 
     */
    public function __construct()
    {
        $send_data = config('affined_udp.send_data', 'default data');
        $this->payload_len = strlen($send_data);
        $this->broadcast = config('affined_udp.broadcast', false);
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
                'status' => ProtocolAffinedUdpEchoServerStatusEnum::START->value,
                'unit' => $this->recvStart(...)
            ];
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoServerStatusEnum::RECEIVING->value,
                'unit' => $this->receiving(...)
            ];
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoServerStatusEnum::SENDING->value,
                'unit' => $this->sending(...)
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoServerStatusEnum::START->value,
                'unit' => $this->sendStart(...)
            ];
            $ret[] = [
                'status' => ProtocolAffinedUdpEchoServerStatusEnum::SENDING->value,
                'unit' => $this->sending(...)
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
     * 処理名：受信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function recvStart(SocketManagerParameter $p_param): ?string
    {
        $p_param->protocol()->setReceivingSize($this->payload_len);
        return $this->receiving($p_param);
    }


    /**
     * 以降はステータスUNITの定義（"SEND"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：送信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function sendStart(SocketManagerParameter $p_param): ?string
    {
        $dat = $p_param->protocol()->getSendData();
        $p_param->protocol()->setSendingData($dat);
        return $this->sending($p_param);
    }


    /**
     * 再利用UNIT
     */

    /**
     * 受信中の処理
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function receiving(SocketManagerParameter $p_param): ?string
    {
        $dat = $p_param->protocol()->receiving();
        if($dat === null)
        {
            return ProtocolAffinedUdpEchoServerStatusEnum::RECEIVING->value;
        }
        if($this->broadcast)
        {
            $p_param->setSendStackAll($dat);
        }
        else
        {
            $p_param->protocol()->setSendingData($dat);
            return $this->sending($p_param);
        }

        return null;
    }

    /**
     * 送信中の処理
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function sending(SocketManagerParameter $p_param): ?string
    {
        $ret = $p_param->protocol()->sending();
        if($ret === true)
        {
            return null;
        }

        return ProtocolAffinedUdpEchoServerStatusEnum::SENDING->value;
    }
}
