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
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
        ProtocolQueueEnum::SEND->value,		// 送信処理のキュー
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
                'status' => ProtocolEchoServerStatusEnum::START->value,
                'unit' => $this->getRecvStart()
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
                'unit' => $this->getSendSending()
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
            $dat = '';
            $p_param->protocol()->recv($dat);
            if($this->broadcast)
            {
                $p_param->setSendStackAll($dat);
            }
            else
            {
                $p_param->setSendStack($dat);
            }

            return null;
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
            $fnc = $this->getSendSending();
            $sta = $fnc($p_param);

            return $sta;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信中
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getSendSending()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $ret = $p_param->protocol()->sending();
            if($ret === true)
            {
                return null;
            }

            return ProtocolEchoServerStatusEnum::SENDING->values;
        };
    }
}
