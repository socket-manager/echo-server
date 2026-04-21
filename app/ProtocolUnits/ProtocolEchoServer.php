<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\SocketManagerParameter;

use App\ProtocolUnits\ProtocolEchoServerStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolEchoServer implements IEntryUnits
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
     * @param int $p_payload_len ペイロード長
     * @param bool $p_broadcast ブロードキャストフラグ
     */
    public function __construct(int $p_payload_len, bool $p_broadcast = false)
    {
        $this->payload_len = $p_payload_len;
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
                'status' => ProtocolEchoServerStatusEnum::START->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolEchoServerStatusEnum::RECEIVING->value,
                'unit' => $this->getReceiving()
            ];
            $ret[] = [
                'status' => ProtocolEchoServerStatusEnum::SENDING->value,
                'unit' => $this->getSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolEchoServerStatusEnum::START->value,
                'unit' => $this->getSendStart()
            ];
            $ret[] = [
                'status' => ProtocolEchoServerStatusEnum::SENDING->value,
                'unit' => $this->getSending()
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
    protected function getRecvStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->protocol()->setReceivingSize($this->payload_len);
            $fnc = $this->getReceiving();
            $sta = $fnc($p_param);
            return $sta;
        };
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
    protected function getSendStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $dat = $p_param->protocol()->getSendData();
            $p_param->protocol()->setSendingData($dat);
            $fnc = $this->getSending();
            $sta = $fnc($p_param);

            return $sta;
        };
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
    protected function getReceiving()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $dat = $p_param->protocol()->receiving();
            if($dat === null)
            {
                return ProtocolEchoServerStatusEnum::RECEIVING->value;
            }
            if($this->broadcast)
            {
                $p_param->setSendStackAll($dat);
            }
            else
            {
                $p_param->protocol()->setSendingData($dat);
                $fnc = $this->getSending();
                $sta = $fnc($p_param);
                return $sta;
            }

            return null;
        };
    }

    /**
     * 送信中の処理
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getSending()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $ret = $p_param->protocol()->sending();
            if($ret === true)
            {
                return null;
            }

            return ProtocolEchoServerStatusEnum::SENDING->value;
        };
    }
}
